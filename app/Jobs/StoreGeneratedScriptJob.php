<?php

namespace App\Jobs;

use App\Enums\NewsStage;
use App\Events\ScriptStoredEvent;
use App\Models\Script;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StoreGeneratedScriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $scripts)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            foreach ($this->scripts as $script) {

                $newScript = Script::query()->create(Arr::only($script, [
                    'article_id',
                    'title',
                    'hook',
                    'payload',
                    'bg_music',
                    'metadata',
                ]));

                foreach ($script['scenes'] as $scene) {
                    $newScene = $newScript->scenes()->create($scene);

                    event(new ScriptStoredEvent($newScene));
                }

                $newScript->article->news->setStage(NewsStage::SCRIPT_GENERATED);

            }
        });
    }

}
