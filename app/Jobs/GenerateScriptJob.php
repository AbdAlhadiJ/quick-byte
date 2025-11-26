<?php

namespace App\Jobs;

use App\Enums\BatchAction;
use App\Enums\NewsStage;
use App\Models\Article;
use App\Models\MusicCategory;
use App\Models\OpenaiBatch;
use App\Models\SoundEffect;
use App\Services\OpenAi\BatchService;
use App\Services\Script\ScriptPromptBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use OpenAI\Responses\Batches\BatchResponse;

class GenerateScriptJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    /** @var int */
    private int $batchSize;

    /** @var string */
    private string $apiEndpoint;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->batchSize = (int)config('openai.batch_size', 50);
        $this->apiEndpoint = config('openai.chat_endpoint', '/v1/chat/completions');
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $articles = Article::query()
            ->whereRelation('news', 'current_stage', '=', NewsStage::SUMMARY_FETCHED->value)
            ->whereDate('created_at', now())
            ->limit(2)
            ->get();

        if ($articles->isEmpty())
            $articles = Article::query()
                ->whereRelation('news', 'current_stage', '=', NewsStage::SUMMARY_FETCHED->value)
                ->limit(2)
                ->get();

        if ($articles->isNotEmpty()) {
            $batchRequests = $this->buildBatchRequest($articles);

            /** @var BatchResponse $batchOutput */
            $batchOutput = app(BatchService::class)->createBatch($batchRequests, $this->apiEndpoint);

            OpenaiBatch::create([
                'openai_batch_id' => $batchOutput->id,
                'endpoint' => $batchOutput->endpoint,
                'input_file_id' => $batchOutput->inputFileId,
                'action' => BatchAction::SCRIPT_GENERATING,
                'status' => $batchOutput->status,
                'total_items' => count($batchRequests),
            ]);
        }
    }

    /**
     * Build the batch request payload for the given articles.
     *
     * @param Collection $articles
     * @return array
     */
    private function buildBatchRequest(Collection $articles): array
    {
        $batchRequests = [];

        $musicCategories = MusicCategory::query()
            ->pluck('name')
            ->implode('|');

        $sfx = SoundEffect::query()
            ->pluck('title')
            ->implode('|');

        foreach ($articles as $article) {

            $body = app(ScriptPromptBuilder::class)->buildFor($article, $musicCategories, $sfx);

            $batchRequests[] = [
                'custom_id' => (string)$article->id,
                'method' => 'POST',
                'url' => $this->apiEndpoint,
                'body' => $body,
            ];

        }

        return $batchRequests;
    }
}
