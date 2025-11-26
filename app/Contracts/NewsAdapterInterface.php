<?php

namespace App\Contracts;

interface NewsAdapterInterface
{
    /**
     * Fetch top N trends/posts.
     *
     * @return array
     */
    public function fetchNews(): array;

}
