<?php

namespace App\Services\News;

use App\Contracts\NewsAdapterInterface;

class NewsFetcher
{
    /**
     * Create new instance
     *
     * @param array $cfg
     */
    public function __construct(protected array $cfg)
    {

    }

    /**
     * Fetch trends from all enabled platforms.
     *
     * @return array
     */
    public function fetchAll(): array
    {
        $news = [];

        foreach ($this->cfg as $key => $cfg) {
            if (!$cfg['enabled']) continue;
            /** @var NewsAdapterInterface $adapter */
            $adapter = new $cfg['driver']($this->cfg[$key]);
            $items = $adapter->fetchNews();
            $news = array_merge($news, $items);
        }

        return $news;
    }
}
