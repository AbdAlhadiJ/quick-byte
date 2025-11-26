<?php

namespace App\Listeners;

use App\Events\NewsNoveltyFilterCompletedEvent;
use App\Jobs\FetchNewsArticlesJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleNewsNoveltyFilterCompletedEvent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NewsNoveltyFilterCompletedEvent $event): void
    {
        FetchNewsArticlesJob::dispatch();
    }
}
