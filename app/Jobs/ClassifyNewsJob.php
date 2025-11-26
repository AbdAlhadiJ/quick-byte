<?php

namespace App\Jobs;

use App\Events\NewsClassfiedEvent;
use App\Models\News;
use App\Services\News\Classfier\ClassifierPromptBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;

class ClassifyNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    private int $batchSize = 30;

    /** @var string */
    private string $apiEndpoint;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $news)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $chunks = array_chunk($this->news, $this->batchSize);

        foreach ($chunks as $newsChunk) {

            $response = OpenAI::chat()->create($this->buildRequest($newsChunk));

            $results = $response->choices[0]->message->content;

            $decoded = json_decode($results, true);

            foreach ($decoded as $news) {
                News::create([
                    'title' => $news['title'],
                    'category' => $news['category'],
                    'description' => $news['description'],
                    'url' => $news['url'],
                    'source' => $news['source']
                ]);
            }

            event(new NewsClassfiedEvent());
        }

    }

    /**
     * Build the batch request payload for the given articles.
     *
     * @param array $news
     * @return array
     */
    private function buildRequest(array $news): array
    {
        return app(ClassifierPromptBuilder::class)->buildFor($news);
    }
}
