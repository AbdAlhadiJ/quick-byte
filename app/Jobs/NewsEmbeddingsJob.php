<?php

namespace App\Jobs;

use App\Enums\BatchAction;
use App\Models\OpenaiBatch;
use App\Services\OpenAi\BatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenAI\Responses\Batches\BatchResponse;
use Throwable;

class NewsEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    /** @var int */
    private int $batchSize;

    /** @var string */
    private string $apiEndpoint;

    /** @var string */
    private const MODEL = 'text-embedding-ada-002';

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $news,
    )
    {
        $this->batchSize = (int)config('openai.batch_size');
        $this->apiEndpoint = config('openai.embedding_endpoint');
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $newsCollection = collect($this->news);

        foreach ($newsCollection->chunk($this->batchSize) as $newsChunk) {

            $batchRequests = $this->buildBatchRequest($newsChunk);

            try {
                /** @var BatchResponse $batchOutput */
                $batchOutput = app(BatchService::class)->createBatch($batchRequests, $this->apiEndpoint);

                OpenaiBatch::create([
                    'openai_batch_id' => $batchOutput->id,
                    'endpoint' => $batchOutput->endpoint,
                    'input_file_id' => $batchOutput->inputFileId,
                    'action' => BatchAction::EMBEDDING,
                    'status' => $batchOutput->status,
                    'total_items' => count($batchRequests),
                ]);

                Log::info(
                    sprintf(
                        '%s: Successfully created OpenAI batch (%s) with %d items.',
                        __CLASS__,
                        $batchOutput->id,
                        count($batchRequests)
                    )
                );
            } catch (Throwable $e) {
                Log::error(
                    sprintf(
                        '%s: Failed to create OpenAI batch. Error: %s',
                        __CLASS__,
                        $e->getMessage()
                    ),
                    ['trace' => $e->getTraceAsString()]
                );

                continue;
            }
        }

    }

    /**
     * Build the batch request payload for the given news.
     *
     * @param Collection $newsChunk
     * @return array
     */
    private function buildBatchRequest(Collection $newsChunk): array
    {
        $batchRequests = [];

        foreach ($newsChunk as $news) {
            $textToEmbed = implode("\n\n", [
                $news->title,
                $news->description
            ]);

            $body = [
                'model' => self::MODEL,
                'input' => $textToEmbed,
            ];

            $batchRequests[] = [
                'custom_id' => (string)$news->id,
                'method' => 'POST',
                'url' => $this->apiEndpoint,
                'body' => $body,
            ];
        }

        return $batchRequests;
    }
}
