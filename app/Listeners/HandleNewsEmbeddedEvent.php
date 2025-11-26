<?php

namespace App\Listeners;

use App\Events\NewsEmbeddedEvent;
use App\Jobs\NewsNoveltyFilterJob;
use App\Models\News;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;

class HandleNewsEmbeddedEvent
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
    public function handle(NewsEmbeddedEvent $event): void
    {
        NewsNoveltyFilterJob::dispatch($event->news);
    }
}
