<?php

namespace App\Jobs;

use App\Contracts\VectorDbInterface;
use App\Enums\NewsStage;
use App\Events\NewsNoveltyFilterCompletedEvent;
use App\Models\News;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;


class NewsNoveltyFilterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected array $news, protected float $threshold = 0.85)
    {

    }

    public function handle(VectorDbInterface $vectorDb): void
    {
        $vectorsToUpsert = [];

        foreach ($this->news as $item) {
            if ($vector = $this->processItem($item, $vectorDb)) {
                $vectorsToUpsert[] = $vector;
            }
        }

        $this->upsertVectors($vectorDb, $vectorsToUpsert);

        event(new NewsNoveltyFilterCompletedEvent());
    }

    /**
     * Process each news item to determine novelty and prepare for upsert.
     *
     * @param array $item
     * @param VectorDbInterface $vectorDb
     * @return array|null
     */
    private function processItem(array $item, VectorDbInterface $vectorDb): ?array
    {
        $newsId = Arr::get($item, 'news_id');
        $embedding = Arr::get($item, 'embedding');

        try {
            $neighbors = $vectorDb->query($embedding);
            $bestScore = collect($neighbors)->pluck('score')->max() ?? 0;
            $isNovel = $bestScore < $this->threshold;

            $this->updateNewsRecord($newsId, $bestScore, $isNovel);

            return $isNovel
                ? ['id' => (string)$newsId, 'values' => $embedding]
                : null;

        } catch (Exception $e) {
            Log::error('Novelty filter failed for news item', [
                'news_id' => $newsId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update News database record with novelty filter results.
     *
     * @param int|string $newsId
     * @param float $score
     * @param bool $passed
     */
    private function updateNewsRecord(int|string $newsId, float $score, bool $passed): void
    {
        News::where('id', $newsId)
            ->update([
                'novelty_passed' => $passed,
                'meta->similarity_score' => $score,
                'rejection_reason' => $passed ? null : 'duplicate',
                'current_stage' => NewsStage::NOVELTY_FILTERED,
            ]);
    }

    /**
     * Upsert vectors into the vector database.
     *
     * @param VectorDbInterface $vectorDb
     * @param array $vectors
     */
    private function upsertVectors(VectorDbInterface $vectorDb, array $vectors): void
    {
        if (empty($vectors)) {
            return;
        }

        $vectorDb->upsert($vectors);
    }


}
