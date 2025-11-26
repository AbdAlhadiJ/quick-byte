<?php

namespace App\Listeners;

use App\Events\ArticleFetchedEvent;
use App\Jobs\GenerateScriptJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleArticleFetchedEvent
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
    public function handle(ArticleFetchedEvent $event): void
    {
       GenerateScriptJob::dispatch();
    }
}
