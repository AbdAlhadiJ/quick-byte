<?php

namespace App\Jobs;

use App\Enums\NewsStage;
use App\Enums\ScheduleStatus;
use App\Models\Platform;
use App\Models\ScheduledUpload;
use App\Models\Script;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScheduleVideoUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $mode;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Script $script)
    {
        $this->mode = config('settings.schedule_mode', 'weekly');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $platforms = Platform::query()
            ->get();

        foreach ($platforms as $platform) {

            [$scheduledAt, $timeZone] = $this->findNextUploadSlot($platform);

            $schedule = ScheduledUpload::create([
                'title' => $this->script->hook,
                'description' => $this->script->metadata['description'],
                'file_path' => $this->script->video_path,
                'thumbnail_url' => $this->script->thumbnail_url ?? null,
                'platform_id' => $platform->id,
                'scheduled_at' => $scheduledAt,
                'timezone' => $timeZone,
                'tags' => $this->script->metadata['hashtags'],
                'status' => ScheduleStatus::PENDING->value,
            ]);

            $this->script->article->news->setStage(NewsStage::SCHEDULED);

        }

    }

    protected function findNextUploadSlot(Platform $platform): array
    {
        return $this->mode === 'daily'
            ? $this->findNextDailySlot($platform)
            : $this->findNextBestSlot($platform);
    }

    protected function findNextDailySlot(Platform $platform): array
    {
        $tz = $platform->timezone ?? config('app.timezone');
        $now = Carbon::now($tz);

        // pick the platform's first recommended time slot
        $firstRange = $platform->best_times[0] ?? '00:00-00:00';
        [$startTime] = explode('-', $firstRange) + [1 => null];
        [$hour, $minute] = array_map('intval', explode(':', trim($startTime)));

        // iterate days until we find a free slot
        $date = CarbonImmutable::instance($now)->startOfDay();
        do {
            $candidate = $date->setTime($hour, $minute, 0);

            // skip past or already scheduled dates
            $exists = ScheduledUpload::where('platform_id', $platform->id)
                ->whereBetween('scheduled_at', [
                    $candidate->startOfDay()->setTimezone('UTC')->toDateTimeString(),
                    $candidate->endOfDay()->setTimezone('UTC')->toDateTimeString(),
                ])->exists();

            if (!$exists && $candidate->greaterThan($now)) {
                return [$candidate, $tz];
            }

            $date = $date->addDay();
        } while (true);
    }


    protected function findNextBestSlot(Platform $platform): array
    {
        $weeklyLimit = $platform->recommended_uploads_per_week
            ?? $this->getFallbackWeeklyLimit($platform->slug);

        $platformTz = $platform->timezone ?? config('app.timezone');

        $nowInPlatformTz = Carbon::now($platformTz);

        $dayTimePairs = [];
        foreach ($platform->best_days as $dayName) {
            foreach ($platform->best_times as $range) {
                [$startTime, $_endTime] = explode('-', $range) + [1 => null];
                $dayTimePairs[] = [
                    'day' => $dayName,
                    'startTime' => trim($startTime),
                ];
            }
        }

        $weekOffset = 0;

        while (true) {
            $weekCandidate = $nowInPlatformTz->copy()->addWeeks($weekOffset);
            $weekStartInPlatformTz = $weekCandidate->copy()->startOfWeek();
            $weekEndInPlatformTz = $weekCandidate->copy()->endOfWeek();

            $weekStartUtc = $weekStartInPlatformTz->copy()->setTimezone($platformTz);
            $weekEndUtc = $weekEndInPlatformTz->copy()->setTimezone($platformTz);

            $countThisWeek = ScheduledUpload::where('platform_id', $platform->id)
                ->whereBetween('scheduled_at', [
                    $weekStartUtc->toDateTimeString(),
                    $weekEndUtc->toDateTimeString(),
                ])->count();

            if ($countThisWeek >= $weeklyLimit) {
                $weekOffset++;
                continue;
            }

            foreach ($dayTimePairs as $pair) {
                $dayName = $pair['day'];
                $startTime = $pair['startTime'];

                $candidateInPlatformTz = $this->makeCandidateDateTime(
                    $dayName,
                    $startTime,
                    $nowInPlatformTz,
                    $weekOffset,
                    $platformTz
                );

                // Skip if candidate is in the past
                if ($candidateInPlatformTz->lessThanOrEqualTo($nowInPlatformTz)) {
                    continue;
                }

                // Calculate UTC boundaries for the candidate's day
                $startOfDay = $candidateInPlatformTz->copy()->startOfDay();
                $endOfDay = $candidateInPlatformTz->copy()->endOfDay();
                $startOfDayUtc = $startOfDay->setTimezone('UTC');
                $endOfDayUtc = $endOfDay->setTimezone('UTC');

                // Check for existing upload on the same platform/day
                $existsSameDay = ScheduledUpload::where('platform_id', $platform->id)
                    ->whereBetween('scheduled_at', [
                        $startOfDayUtc->toDateTimeString(),
                        $endOfDayUtc->toDateTimeString()
                    ])
                    ->exists();

                if (!$existsSameDay) {
                    return [$candidateInPlatformTz, $platformTz];
                }
            }

            $weekOffset++;
        }
    }

    protected function makeCandidateDateTime(
        string $dayName,
        string $startTime,
        Carbon $nowInPlatformTz,
        int    $weekOffset,
        string $platformTz
    ): CarbonImmutable
    {
        // Calculate start of target week
        $weekStart = $nowInPlatformTz->copy()
            ->startOfWeek()
            ->addWeeks($weekOffset);

        // Create candidate in target week
        $candidate = $weekStart->modify("{$dayName} this week");

        // Set time from startTime
        [$hour, $minute] = explode(':', $startTime);
        $candidate = $candidate->setTime((int)$hour, (int)$minute, 0);

        return CarbonImmutable::instance($candidate);
    }

    protected function getFallbackWeeklyLimit(string $slug): int
    {
        return match ($slug) {
            'tiktok' => 7,
            default => 4,
        };
    }
}
