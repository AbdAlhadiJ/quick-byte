<?php

namespace App\Jobs;

use App\Events\NewsFetchedEvent;
use App\Services\News\NewsFetcher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchNewsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Execute the job.
     */
    public function handle(NewsFetcher $fetcher): void
    {
        $news = $fetcher->fetchAll();

        event(new NewsFetchedEvent($news));
    }
}
