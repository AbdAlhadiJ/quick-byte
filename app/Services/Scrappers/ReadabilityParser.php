<?php

namespace App\Services\Scrappers;

use App\Contracts\ArticleParserInterface;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Readability;

class ReadabilityParser implements ArticleParserInterface
{

    /**
     *
     *
     * @param string $url
     * @return Configuration
     */
    private function configureReadability(string $url): Configuration
    {
        return new Configuration([
            'fix_relative_urls' => true,
            'original_url' => $url,
            'max_chars' => 1_000_000,
        ]);

    }

    /**
     * Parse the HTML content of an article and return its title and text.
     *
     * @param string $url
     * @param string $html
     * @return array
     * @throws ParseException
     */
    public function parse(string $url, string $html): array
    {
        $readability = new Readability($this->configureReadability($url));

        $readability->parse($html);

        $title = $readability->getTitle() ?? '';
        $content = $readability->getContent() ?? '';

        return ['title' => $title, 'text' => $this->cleanText($content)];
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = preg_replace('/[\p{So}\p{C}]+/u', '', $text);

        $text = preg_replace('/[^\p{L}\p{N}\s\.\,\-\:\;\(\)\'\"]+/u', '', $text);

        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }


}
