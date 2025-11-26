<?php

namespace App\Contracts;

interface VectorDbInterface
{
    /**
     * Upsert a single vector embedding into the index.
     *
     * @param $vectors
     * @return array
     */
    public function upsert($vectors): array;

    /**
     * Query the index for nearest neighbor embeddings.
     *
     * @param float[] $vector
     * @param array $options
     * @return array
     */
    public function query(array $vector, array $options = []): array;
}
