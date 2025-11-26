<?php

namespace App\Listeners;

use App\Events\VideoAssembledEvent;
use App\Jobs\ScheduleVideoUploadJob;

class HandleVideoAssembledEvent
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
    public function handle(VideoAssembledEvent $event): void
    {
        ScheduleVideoUploadJob::dispatch($event->script);
    }
}
