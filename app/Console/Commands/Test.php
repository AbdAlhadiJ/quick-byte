<?php

namespace App\Console\Commands;


use App\Jobs\GenerateScriptJob;
use App\Jobs\StoreGeneratedScriptJob;
use App\Jobs\UploadVideoJob;
use App\Models\ScheduledUpload;
use App\Services\OpenAi\BatchService;
use App\Services\PlatformAuth\InstagramAuthService;
use App\Services\Script\ScriptProcessor;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws ConnectionException
     */

    public function handle()
    {

        GenerateScriptJob::dispatch();

    }
}
