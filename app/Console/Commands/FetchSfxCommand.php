<?php

namespace App\Console\Commands;

use App\Models\SoundEffect;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchSfxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sfx:fetch {url : The URL of the SFX file} {name? : Optional custom file name (without extension)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch a sound effect from a URL, store it locally (optionally with a custom name), and record its path in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $customName = $this->argument('name');

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL provided.');
            return 1;
        }

        $this->info('Downloading SFX from: ' . $url);

        try {


            $httpResponse = Http::timeout(90)
                ->retry(3, 1000)
                ->get($url);

            if (!$httpResponse->successful()) {
                $this->error('Failed to download file, status: ' . $httpResponse->getStatusCode());
                return 1;
            }

            // Determine file extension
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'mp3';

            // Determine filename
            if ($customName) {
                $filename = Str::slug(pathinfo($customName, PATHINFO_FILENAME));
            } else {
                $filename = Str::random(16);
            }

            $storagePath = "sfx/{$filename} . '.' . $extension";

            // Store file in local disk
            Storage::disk('local')->put($storagePath, $httpResponse->getBody());

            $this->info('Stored SFX at: storage/app/' . $storagePath);

            // Persist to database
            $sfx = SoundEffect::updateOrCreate(
                ['title' => $filename],
                [
                    'file_path' => $storagePath,
                ]
            );

            $this->info('SoundEffect record created with ID: ' . $sfx->id);

            return 0;

        } catch (Exception $e) {
            $this->error('Error fetching SFX: ' . $e->getMessage());
            return 1;
        }
    }
}
