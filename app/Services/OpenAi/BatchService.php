<?php

namespace App\Services\OpenAi;

use App\Exceptions\OpenAiBatchFailedException;
use App\Helpers\FileHelpers;
use Exception;
use Illuminate\Support\Facades\Log;
use JsonException;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Batches\BatchResponse;
use RuntimeException;

class BatchService
{
    /**
     * Create a new batch job.
     *
     * @param array $batch
     * @param string $endpoint
     * @param int $timeoutHours
     * @return BatchResponse
     *
     */
    public function createBatch(
        array  $batch,
        string $endpoint,
        int    $timeoutHours = 24
    ): BatchResponse
    {

        $tempPath = FileHelpers::createTempFilePath(
            prefix: 'openai_batch_',
            extension: 'jsonl',
            useStorageInstance: true
        );

        $this->createJsonlFile($tempPath, $batch);

        $fileId = $this->uploadJsonlFile($tempPath);

        return $this->createBatchJob($fileId, $endpoint, $timeoutHours);
    }

    /**
     * Write an array of payloads to a JSONL file at the given path.
     *
     * Each line in the file will be a JSON object with:
     *   - custom_id: (string) index of the payload
     *   - method:    "POST"
     *   - url:       The endpoint path
     *   - body:      The actual request body (array)
     *
     * @param string $path
     * @param array $batch
     * @return void
     *
     */
    private function createJsonlFile(string $path, array $batch): void
    {
        $handle = fopen($path, 'w');

        if (!$handle) {
            throw new RuntimeException("Failed to create temp file at {$path}");
        }

        try {
            foreach ($batch as $item) {
                $line = json_encode($item, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                if (fwrite($handle, $line . PHP_EOL) === false) {
                    throw new RuntimeException("Failed to write to file at {$path}");
                }
            }
        } catch (JsonException $e) {
            throw new RuntimeException("JSON encoding error: " . $e->getMessage());
        } finally {
            fclose($handle);
        }
    }

    /**
     * Upload the JSONL file at the given path to OpenAI for batch processing.
     *
     * @param string $path
     * @return string
     *
     * @throws RuntimeException
     */
    private function uploadJsonlFile(string $path): string
    {
        $fileResource = null;

        try {
            $fileResource = fopen($path, 'r');
            if (!$fileResource) {
                throw new RuntimeException("Failed to open file for reading: {$path}");
            }

            $response = OpenAI::files()->upload([
                'purpose' => 'batch',
                'file' => $fileResource,
            ]);

            return $response->id;
        } finally {
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }
            if (file_exists($path)) {
//                unlink($path);
            }
        }
    }

    /**
     * Create an OpenAI batch job given the uploaded file ID.
     *
     * @param string $fileId
     * @param string $endpoint
     * @param int $timeoutHours
     *
     * @return BatchResponse
     */
    private function createBatchJob(
        string $fileId,
        string $endpoint,
        int    $timeoutHours
    ): BatchResponse
    {
        try {
            return OpenAI::batches()->create([
                'input_file_id' => $fileId,
                'endpoint' => $endpoint,
                'completion_window' => "{$timeoutHours}h",
            ]);
        } catch (Exception $e) {

            $code = method_exists($e, 'getErrorCode')
                ? $e->getErrorCode()
                : 'unknown';

            Log::error("Batch creation failed", [
                'code' => $code,
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            throw new RuntimeException("Batch creation failed: " . $e->getMessage());
        }
    }

    /**
     * Retrieve the status of a given batch job.
     *
     * @param string $batchId
     * @return array
     */
    public function getStatus(string $batchId): array
    {
        return OpenAI::batches()
            ->retrieve($batchId)
            ->toArray();
    }

    /**
     * Cancel a running batch job.
     *
     * @param string $batchId
     * @return array
     */
    public function cancelBatch(string $batchId): array
    {
        return OpenAI::batches()
            ->cancel($batchId)
            ->toArray();
    }

    /**
     * Poll the batch status until it is completed (or fails/errors/cancels/timeout).
     *
     * This will loop until either:
     *   - status becomes “completed” (returns the full batch array), or
     *   - status becomes “failed”, “cancelled”, or “expired” (throws RuntimeException), or
     *   - polling exceeds $timeoutSeconds (throws RuntimeException).
     *
     * @param string $batchId
     * @param int $pollIntervalSeconds
     * @param int $timeoutSeconds
     * @return array
     *
     * @throws RuntimeException
     * @throws OpenAiBatchFailedException
     */
    public function pollUntilCompleted(
        string $batchId,
        int    $pollIntervalSeconds = 15,
        int    $timeoutSeconds = 3600
    ): array
    {
        $start = time();
        $timeoutAt = $start + $timeoutSeconds;

        while (time() < $timeoutAt) {
            $batch = $this->getStatus($batchId);
            $status = strtolower($batch['status'] ?? '');

            switch ($status) {
                case 'completed':
                    return $batch;

                case 'failed':
                case 'cancelled':
                case 'expired':
                    throw new OpenAiBatchFailedException("Batch {$batchId} terminated with status: {$status}");
            }

            sleep($pollIntervalSeconds);
        }

        throw new OpenAiBatchFailedException("Polling timeout reached for batch {$batchId}");
    }

    /**
     * Download the results (and errors) for a completed batch job.
     *
     * Returns an array with keys:
     *   - 'results': array of parsed JSON objects from the output file (if any)
     *   - 'errors' : array of parsed JSON objects from the error file (if any)
     *
     * @param string $batchId
     * @return array
     *
     * @throws RuntimeException
     */
    public function downloadResults(string $batchId): array
    {
        $batch = $this->getStatus($batchId);

        if (strtolower($batch['status'] ?? '') !== 'completed') {
            throw new RuntimeException("Batch {$batchId} is not completed (status: {$batch['status']})");
        }

        return [
            'results' => $this->processOutputFile($batch['output_file_id'] ?? null),
            'errors' => $this->processOutputFile($batch['error_file_id'] ?? null),
        ];
    }

    /**
     * Given a file ID, download its contents and parse them as JSONL.
     *
     * @param string|null $fileId
     * @return array
     */
    private function processOutputFile(?string $fileId): array
    {
        if (!$fileId) {
            return [];
        }

        try {
            $content = OpenAI::files()->download($fileId);
            return $this->parseJsonlContent($content);
        } catch (Exception $e) {
            Log::error("Failed to process output file", ['file_id' => $fileId, 'error' => $e]);
            return [];
        }
    }

    /**
     * Parse a JSONL-formatted string into an array of associative arrays.
     *
     * @param string $content
     * @return array
     */
    private function parseJsonlContent(string $content): array
    {

        return array_reduce(
            explode(PHP_EOL, $content),
            function (array $carry, string $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    return $carry;
                }

                try {
                    $carry[] = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    Log::warning("Invalid JSON line in batch output", ['line' => $line]);
                }

                return $carry;
            },
            []
        );
    }
}
