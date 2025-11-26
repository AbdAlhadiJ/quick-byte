<?php

namespace App\Console\Commands;

use App\Enums\AssetStatus;
use App\Enums\NewsStage;
use App\Jobs\ComposeVideoJob;
use App\Models\Script;
use Illuminate\Console\Command;

class ProcessReadyScripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scripts:query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds scripts with completed assets for all scenes and dispatches video composition jobs.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scripts = Script::with(['article', 'scenes.assets' => function ($q) {
            $q->where('status', AssetStatus::COMPLETED->value);
        }])
            ->whereRelation('article.news', 'current_stage', '=', NewsStage::SCRIPT_GENERATED->value)
            ->get();

        $readyScripts = $scripts->filter(function ($script) {
            $sceneCount = $script->scenes->count();
            $completedAssets = $script->scenes->flatMap->assets->count();

            return $sceneCount > 0 && $completedAssets === ($sceneCount * 2);
        });
        if($readyScripts->isNotEmpty()){
            foreach ($readyScripts as $script){
                ComposeVideoJob::dispatch($script);
            }

        }
    }
}
