<?php

namespace App\Listeners;

use App\Enums\GeneratorType;
use App\Events\ScriptStoredEvent;
use App\Jobs\GenerateAssetsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleScriptStoredEvent
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
    public function handle(ScriptStoredEvent $event): void
    {
        GenerateAssetsJob::dispatch($event->scene, GeneratorType::VISUAL->value);
        GenerateAssetsJob::dispatch($event->scene, GeneratorType::AUDIO->value);
    }
}
