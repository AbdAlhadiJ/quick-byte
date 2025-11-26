<?php

namespace App\Contracts;

interface ArticleScraperInterface
{
    /**
     * Scrape article.
     *
     * @param string $url
     * @return array{title: string, text: string}
     */
    public function scrape(string $url): array;
}
