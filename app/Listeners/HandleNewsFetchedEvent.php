<?php

namespace App\Listeners;

use App\Events\NewsFetchedEvent;
use App\Jobs\ClassifyNewsJob;

class HandleNewsFetchedEvent
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
    public function handle(NewsFetchedEvent $event): void
    {
        ClassifyNewsJob::dispatch($event->news);
    }
}
