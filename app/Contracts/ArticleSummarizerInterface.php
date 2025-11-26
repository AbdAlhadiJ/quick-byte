<?php

namespace App\Contracts;

interface ArticleSummarizerInterface
{
    /**
     * Summarize the given text.
     *
     * @param string $text
     * @return string
     */
    public function summarize(string $text): string;

}
