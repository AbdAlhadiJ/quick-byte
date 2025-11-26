<?php

namespace App\Contracts;

interface ArticleParserInterface
{
    /**
     * Parse the HTML content of an article and return its title and text.
     *
     * @param string $url The URL of the article.
     * @param string $html The HTML content of the article.
     * @return array An associative array containing 'title' and 'text'.
     */
    public function parse(string $url, string $html): array;

}
