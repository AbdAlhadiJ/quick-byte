<?php

namespace App\Listeners;

use App\Events\NewsClassfiedEvent;
use App\Jobs\FetchNewsArticlesJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleNewsClassfiedEvent
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
    public function handle(NewsClassfiedEvent $event): void
    {
        FetchNewsArticlesJob::dispatch();
    }
}
