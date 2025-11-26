<?php

namespace App\Jobs;

use App\Contracts\ArticleSummarizerInterface;
use App\Enums\NewsStage;
use App\Events\ArticleFetchedEvent;
use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Throwable;

class FetchArticleSummaryJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 4;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 1800;

    private int $batchSize = 10;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Article::query()
            ->whereRelation('news', 'current_stage', '=', NewsStage::ARTICLE_FETCHED->value)
            ->chunkById($this->batchSize, function (Collection $newsChunk) {
                foreach ($newsChunk as $news) {
                    $this->processNews($news);
                }
            });

        event(new ArticleFetchedEvent());
    }

    private function processNews(Article $article): void
    {
        try {

            $summary = app(ArticleSummarizerInterface::class)->summarize($article->content);

            $article->update([
                'summary' => $summary
            ]);

            $article->news->setStage(NewsStage::SUMMARY_FETCHED);

        } catch (Throwable $e) {
            $article->news->update([
                'current_stage' => NewsStage::FAILED,
                'rejection_reason' => 'processing_failed:' . $e->getMessage(),
            ]);
            return;
        }
    }
}
