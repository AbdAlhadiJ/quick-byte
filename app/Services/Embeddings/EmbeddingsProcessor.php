<?php

namespace App\Services\Embeddings;

use Illuminate\Support\Arr;

class EmbeddingsProcessor
{
    /**
     * Process embedding to vector db
     *
     * @param array $newsBatch
     * @return array
     */
    public function process(array $newsBatch): array
    {
        $news = [];


        foreach ($newsBatch as $itemWrapper) {

            $newsId = Arr::get($itemWrapper, 'custom_id');

            $rawEmbedding = Arr::get(
                $itemWrapper,
                'response.body.data.0.embedding',
                []
            );

            $item = [
                'news_id' => $newsId,
                'embedding' => $rawEmbedding
            ];

            $news[] = $item;

        }

        return $news;

    }
}
