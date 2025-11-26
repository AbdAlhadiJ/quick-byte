<?php

namespace App\Providers;

use App\Console\Commands\ProcessQueuedAssets;
use App\Contracts\ArticleParserInterface;
use App\Contracts\ArticleScraperInterface;
use App\Contracts\ArticleSummarizerInterface;
use App\Contracts\AssetsGeneratorInterface;
use App\Contracts\VectorDbInterface;
use App\Contracts\VideoAssemblerInterface;
use App\Jobs\GenerateAssetsJob;
use App\Services\Embeddings\PineconeService;
use App\Services\Media\ElevenLabsService;
use App\Services\Media\VeoVideoGenerator;
use App\Services\News\NewsFetcher;
use App\Services\Scrappers\HuggingFace;
use App\Services\Scrappers\ReadabilityParser;
use App\Services\Scrappers\ScrapDo;
use App\Services\Script\ScriptProcessor;
use App\Services\Script\ScriptVisualPromptFormatter;
use App\Services\Script\ScriptVoiceoverFormatter;
use App\Services\VideoAssembler\Components\AudioMixer;
use App\Services\VideoAssembler\Components\MediaProbe;
use App\Services\VideoAssembler\Components\SceneProcessor;
use App\Services\VideoAssembler\Components\TransitionBuilder;
use App\Services\VideoAssembler\FFmpegService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->bindCoreServices();
        $this->bindScriptComponents();
        $this->bindMediaServices();

    }

    protected function bindCoreServices(): void
    {
        $this->app->singleton(NewsFetcher::class, function ($app) {
            return new NewsFetcher(config('news_adapters'));
        });

        $this->app->singleton(VectorDbInterface::class, function ($app) {
            return new PineconeService(config('services.pinecone'));
        });

        $this->app->singleton(ArticleParserInterface::class, function ($app) {
            return new ReadabilityParser();
        });

        $this->app->singleton(ArticleScraperInterface::class, function ($app) {
            return new ScrapDo(app(ArticleParserInterface::class));
        });


        $this->app->singleton(ArticleSummarizerInterface::class, function ($app) {
            return new HuggingFace(config('services.huggingface'));
        });

        $this->app->singleton(VideoAssemblerInterface::class, function ($app) {
            return new FFmpegService(
                $app->make(SceneProcessor::class),
                $app->make(TransitionBuilder::class),
                $app->make(AudioMixer::class),
                $app->make(MediaProbe::class)
            );
        });
    }

    /**
     * Bind script generation components
     */
    protected function bindScriptComponents(): void
    {

        $this->app->singleton(ScriptProcessor::class, fn($app) => new ScriptProcessor(
            $app->make(ScriptVoiceoverFormatter::class),
        )
        );
    }

    /**
     * Bind media processing services
     */
    protected function bindMediaServices(): void
    {

        $this->app->bind('audio_generator', function ($app) {
            return new ElevenLabsService(config('elevenlabs'));
        });

        $this->app->bind('visual_generator', function ($app) {
            return app(VeoVideoGenerator::class);
        });


    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
//        URL::forceScheme('https');

        $this->app->when([GenerateAssetsJob::class, ProcessQueuedAssets::class])
            ->needs(AssetsGeneratorInterface::class)
            ->give(function () {
                return app('audio_generator');
            });

        $this->app->when([GenerateAssetsJob::class, ProcessQueuedAssets::class])
            ->needs(AssetsGeneratorInterface::class)
            ->give(function () {
                return app('visual_generator');
            });
    }

}
