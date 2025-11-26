<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOpenaiBatchJob;
use App\Models\OpenaiBatch;
use App\Services\OpenAi\BatchService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessOpenaiBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:process-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll OpenAI for pending/running batches and dispatch a job if any have completed';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $batches = OpenaiBatch::whereIn('status', ['validating', 'in_progress'])
            ->get();

        if ($batches->isEmpty()) {
            return 0;
        }
        foreach ($batches as $batch) {
            try {

                $response = app(BatchService::class)->getStatus($batch->openai_batch_id);

                $status = strtolower($response['status'] ?? '');
                $processed = data_get($response, 'request_counts.completed', 0);
                $errorCount = data_get($response, 'request_counts.failed', 0);
                $outputFileId = $response['output_file_id'];
                $errorFileId = $response['error_file_id'];

                $batch->status = $status;
                $batch->processed_items = $processed;
                $batch->error_items = $errorCount;

                if ($status === 'in_progress' && is_null($batch->started_at)) {
                    $batch->started_at = Carbon::createFromTimestamp($response['in_progress_at']);
                }

                if ($status === 'completed') {
                    $batch->output_file_id = $outputFileId;
                    $batch->error_file_id = $errorFileId;
                    $batch->completed_at = Carbon::createFromTimestamp($response['completed_at']);
                }

                if (in_array($status, ['failed', 'cancelled', 'expired'], true) && is_null($batch->completed_at)) {
                    $batch->completed_at = now();
                }

                $batch->save();

                $this->info("Polled batch {$batch->openai_batch_id}: status={$status}, processed={$processed}, errors={$errorCount}");

                if ($status === 'completed') {
                    ProcessOpenaiBatchJob::dispatch($batch);
                    $this->info("Dispatched ProcessOpenaiBatchJob for batch {$batch->openai_batch_id}");
                }
            } catch (Exception $e) {
                Log::error("Failed to poll OpenAI batch {$batch->openai_batch_id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Error polling batch {$batch->openai_batch_id}: " . $e->getMessage());
            }
        }
        return 0;
    }
}
