<?php

namespace App\Jobs;

use App\Enums\ScheduleStatus;
use App\Models\ScheduledUpload;
use App\Services\Uploader\InstagramUploader;
use App\Services\Uploader\TikTokUploader;
use App\Services\Uploader\YouTubeUploader;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 4;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct(protected ScheduledUpload $upload)
    {
        //
    }

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(): void
    {
        $platform = $this->upload->platform;
        $slug = $platform->slug;
        $enabled = $platform->is_enabled;

        $uploader = match ($slug) {
            'youtube' => YouTubeUploader::class,
            'tiktok' => TikTokUploader::class,
            'instagram_reels' => InstagramUploader::class,
            default => null,
        };

        if (!$uploader || !$enabled) {
            return;
        }

        try {
            /** @var mixed $response */
            $response = app($uploader)->upload($this->upload);

            $this->upload->update([
                'status' => ScheduleStatus::UPLOADED->value,
                'upload_response' => $response,
            ]);
        } catch (Exception $e) {
            $this->upload->update([
                'status' => ScheduleStatus::FAILED->value,
                'upload_response' => 'Error: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

}
