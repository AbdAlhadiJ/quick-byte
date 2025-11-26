<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TempLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:link {path : file_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Temp Link from private storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath= $this->argument('path');

        $expiry = now()->addMinutes(5);

        if(!Storage::disk('local')->exists($filePath)){
            $this->error("File does not exist");
            return 0;
        }

        $url =  Storage::disk('local')->temporaryUrl($filePath, $expiry);

        $this->info($url);
    }
}
