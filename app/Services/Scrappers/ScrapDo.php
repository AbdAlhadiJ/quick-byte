<?php

namespace App\Services\Scrappers;

use App\Contracts\ArticleParserInterface;
use App\Contracts\ArticleScraperInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ScrapDo implements ArticleScraperInterface
{

    public function __construct(protected ArticleParserInterface $parser)
    {
    }

    /**
     * @inheritDoc
     * @throws RequestException
     */
    public function scrape(string $url): array
    {
        $config = config('services.scrapdo');

        $html = Http::timeout(60)
        ->connectTimeout(10)
        ->get($config['base_url'], [
            'url'   => $url,
            'token' => $config['api_key'],
        ])
            ->throw()
            ->body();


        return $this->parser->parse($url, $html);
    }
}
