<?php

namespace App\Console\Commands;

use App\Contracts\AssetsGeneratorInterface;
use App\Enums\AssetStatus;
use App\Enums\GeneratorType;
use App\Helpers\FileHelpers;
use App\Jobs\GenerateAssetsJob;
use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProcessQueuedAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:queued';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queued assets status and store them locally if ready';


    protected ?string $scriptId = null;

    /**
     * Execute the console command.
     * @throws ConnectionException
     */
    public function handle()
    {

        $assets = Asset::query()
            ->with('scene')
            ->where('type', GeneratorType::VISUAL->value)
            ->where('status', AssetStatus::QUEUED->value)
            ->get();

        if ($assets->isEmpty()) {
            $this->info('No queued assets found.');
            return;
        }

        $this->scriptId = $assets->first()->scene->script_id;

        foreach ($assets as $asset) {

            $response = app(AssetsGeneratorInterface::class)
                ->checkJobStatus(['external_id' => $asset->external_id]);

            if (isset($response['status']) && $response['status'] === 'completed') {
                if (empty($response['asset'])) {
                    $this->warn("Asset for scene_id {$asset->scene->id} returned no payload. Re-queuing.");
                    $asset->update(['status' => AssetStatus::FAILED->value]);
                    GenerateAssetsJob::dispatch($asset->scene, GeneratorType::VISUAL->value);
                    continue;
                }

                $localPath = $this->storeFileLocally($asset, $response);

                $asset->update([
                    'local_path' => $localPath,
                    'remote_path' => $response['asset']['gcsUri'],
                    'status' => AssetStatus::COMPLETED->value,
                    'raw_response' => $response,
                ]);
                $this->info("Asset (ID: {$asset->id}) saved to local path: {$localPath}");
            } else {
                $this->line("Asset (ID: {$asset->id}) is still not completed (status: {$response['status']}).");
            }

        }

    }

    /**
     * @throws ConnectionException
     */
    private function storeFileLocally($asset, $response): string
    {

        if (!isset($response['asset']['gcsUri'])) {
            throw new RuntimeException('GCS URI not present in response payload.');
        }

        $diskLocal = Storage::disk('local');
        $videoContents = '';
        $filename = '';
        $ext = '';

        if ($asset->source === 'veo') {
            $diskGcs = Storage::disk('gcs');

            $objectKey = preg_replace('#^gs://[^/]+/#', '', $response['asset']['gcsUri']);
            $signedUrl = $diskGcs->temporaryUrl($objectKey, now()->addMinutes(30));

            $httpResponse = Http::timeout(90)
                ->retry(3, 1000)
                ->get($signedUrl);

            if (!$httpResponse->successful()) {
                throw new RuntimeException("Failed to download GCS object: {$objectKey}");
            }

            $videoContents = $httpResponse->body();
            $ext = pathinfo($objectKey, PATHINFO_EXTENSION);
            $filename = Str::uuid();
        }

        $subDir = "{$this->scriptId}/generated_videos";

        $localPath = FileHelpers::createScriptAssetFilePath($filename, $ext, $subDir);

        $diskLocal->put($localPath, $videoContents);

        return $localPath;
    }
}
