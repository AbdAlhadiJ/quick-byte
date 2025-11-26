<?php

namespace App\Jobs;

use App\Contracts\VideoAssemblerInterface;
use App\Enums\NewsStage;
use App\Events\VideoAssembledEvent;
use App\Helpers\FileHelpers;
use App\Models\MusicTrack;
use App\Models\Script;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComposeVideoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public $uniqueFor = 3600;

    public $tries = 4;

    public function __construct(protected Script $script)
    {
    }

    public function handle(VideoAssemblerInterface $assembler): void
    {
        $musicCategory = $this->script->bg_music;

        $backgroundMusic = MusicTrack::query()
            ->whereRelation('category', 'name', '=', $musicCategory)
            ->orderBy('no_of_uses', 'asc')
            ->orderByRaw('RAND()')
            ->first();

        $path = $assembler->render($this->script, $backgroundMusic->file_path);

        $destination = FileHelpers::createScriptAssetFilePath(Str::uuid(), 'mp4', $this->script->id);

        Storage::disk('local')->move($path, $destination);

        $this->script->update([
            'video_path' => $destination
        ]);

        $backgroundMusic->increment('no_of_uses');

        $this->script->article->news->setStage(NewsStage::VIDEO_ASSEMBLED);

        event(new VideoAssembledEvent($this->script));
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->script->id;
    }
}
