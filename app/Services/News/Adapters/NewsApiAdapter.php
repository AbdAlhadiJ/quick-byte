<?php

namespace App\Services\News\Adapters;

use App\Contracts\NewsAdapterInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class NewsApiAdapter implements NewsAdapterInterface
{

    public function __construct(protected array $cfg)
    {
    }

    /**
     * @inheritDoc
     * @throws RequestException
     */
    public function fetchNews(): array
    {
        $response = Http::get($this->cfg['base_api'], [
            'category' => $this->cfg['category'],
            'language' => 'en',
            'apiKey' => $this->cfg['key'],
            'pageSize' => $this->cfg['limit'],
        ])->throw();

        return collect($response->json('articles'))
            ->map(fn($item) => $this->formatItem($item))
            ->all();
    }

    /**
     * Standardize item structure.
     */
    protected function formatItem(array $item): array
    {
        return [
            'source' => data_get($item, 'source.name', 'Unknown Source'),
            'title' => $item['title'],
            'description' => $item['description'] ?? null,
            'summary' => trim($item['content']),
            'url' => $item['url'],
            'category' => $this->cfg['category'],
        ];
    }

}
