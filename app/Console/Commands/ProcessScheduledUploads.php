<?php

namespace App\Console\Commands;

use App\Enums\ScheduleStatus;
use App\Jobs\UploadVideoJob;
use App\Models\ScheduledUpload;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessScheduledUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:query
                            {--force : Bypass scheduled date check and force upload}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending scheduled uploads. Dispatches UploadVideoJob for each upload whose scheduled_at matches current time. Use --force to bypass scheduling and dispatch all pending uploads.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uploads = ScheduledUpload::query()
            ->where('status', ScheduleStatus::PENDING->value)
            ->get();
        $force   = $this->option('force');
        $toPublish = $uploads->filter(function (ScheduledUpload $upload) use ($force) {
            if ($force) {
                return true;
            }

            $localNow = Carbon::now($upload->timezone);

            $localScheduled = $upload->scheduled_at->copy()->setTimezone($upload->timezone);

            return $localScheduled->format('Y-m-d H:i') === $localNow->format('Y-m-d H:i');
        });

        foreach ($toPublish as $upload) {
            UploadVideoJob::dispatch($upload);
        }

    }
}
