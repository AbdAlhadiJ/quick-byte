<?php

namespace App\Listeners;

use App\Events\ScriptGeneratedEvent;
use App\Jobs\StoreGeneratedScriptJob;

class HandleScriptGeneratedEvent
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
    public function handle(ScriptGeneratedEvent $event): void
    {
        StoreGeneratedScriptJob::dispatch($event->scripts);
    }
}
