<?php

namespace App\Jobs;

use App\Contracts\ArticleScraperInterface;
use App\Enums\NewsStage;
use App\Models\News;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Throwable;

class FetchNewsArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public $tries = 4;
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
        News::query()
            ->where('current_stage', NewsStage::NEW)
            ->select(['id', 'url'])
            ->chunkById($this->batchSize, function (Collection $newsChunk) {
                foreach ($newsChunk as $news) {
                    $this->processNews($news);
                }
            });

        FetchArticleSummaryJob::dispatch();
    }

    private function processNews(News $news): void
    {
        try {

            $payload = app(ArticleScraperInterface::class)->scrape($news->url);

            $news->article()->create([
                'title' => $payload['title'],
                'content' => $payload['text'],
            ]);

            $news->update([
                'current_stage' => NewsStage::ARTICLE_FETCHED
            ]);

        } catch (Throwable $e) {
            $news->update([
                'current_stage' => NewsStage::FAILED,
                'rejection_reason' => 'processing_failed:' . $e->getMessage(),
            ]);
            return;
        }
    }
}
