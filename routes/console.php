<?php

use App\Console\Commands\ProcessOpenaiBatches;
use App\Console\Commands\ProcessQueuedAssets;
use App\Console\Commands\ProcessReadyScripts;
use App\Console\Commands\ProcessScheduledUploads;
use App\Jobs\FetchNewsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new FetchNewsJob())
    ->daily();

Schedule::command(ProcessOpenaiBatches::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command(ProcessQueuedAssets::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command(ProcessReadyScripts::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command(ProcessScheduledUploads::class)
    ->everyMinute()
    ->withoutOverlapping();
