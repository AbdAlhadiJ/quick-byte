<?php

namespace App\Jobs;

use App\Enums\BatchAction;
use App\Events\NewsEmbeddedEvent;
use App\Events\ScriptGeneratedEvent;
use App\Exceptions\OpenAiBatchFailedException;
use App\Models\OpenaiBatch;
use App\Services\Embeddings\EmbeddingsProcessor;
use App\Services\News\Classfier\NewsClassifierProcessor;
use App\Services\OpenAi\BatchService;
use App\Services\Script\ScriptProcessor;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class ProcessOpenaiBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(protected OpenaiBatch $batch)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $batchOutput = app(BatchService::class)
                ->downloadResults($this->batch->openai_batch_id);

            $errors = Arr::get($batchOutput, 'errors', []);
            if (!empty($errors)) {
                throw new OpenAiBatchFailedException($this->formatErrorMessages($errors));
            }

            $results = Arr::get($batchOutput, 'results', []);

            $payload = $this->processByAction($results, $this->batch->action);

            $this->dispatchEvent($payload, $this->batch->action);

        } catch (Exception $exception) {
            logger("Failed to process batch ID: {$this->batch->openai_batch_id}", [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    private function formatErrorMessages(array $errors): string
    {
        return collect($errors)
            ->map(fn(array $err) => sprintf(
                'News %s: %s',
                Arr::get($err, 'custom_id', '(unknown)'),
                Arr::get($err, 'error.message', 'Unspecified batch error')
            ))
            ->join('; ');
    }

    /**
     * @throws InvalidArgumentException
     */
    private function processByAction(array $results, BatchAction $action): mixed
    {
        return match ($action) {
            BatchAction::EMBEDDING => app(EmbeddingsProcessor::class)->process($results),
            BatchAction::SCRIPT_GENERATING => app(ScriptProcessor::class)->process($results),
            default => throw new InvalidArgumentException("Unknown batch action: {$action->value}"),
        };
    }

    private function dispatchEvent(mixed $payload, BatchAction $action): void
    {
        $event = match ($action) {
            BatchAction::EMBEDDING => new NewsEmbeddedEvent($payload),
            BatchAction::SCRIPT_GENERATING => new ScriptGeneratedEvent($payload),
            default => throw new InvalidArgumentException("Unknown batch action: {$action->value}"),
        };

        event($event);
    }


}
