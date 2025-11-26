<?php

namespace App\Services\Embeddings;

use App\Contracts\VectorDbInterface;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class PineconeService implements VectorDbInterface
{
    /** @var string|mixed  */
    protected string $baseUrl;

    /** @var array  */
    protected array $headers;

    public function __construct(protected array $config)
    {
        $this->baseUrl = $config['index_host'];
        $this->headers = [
            'Api-Key' => $config['api_key'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function upsert($vectors): array
    {

        $payload = [
            'vectors' => [$vectors],
        ];

        $response = Http::withHeaders($this->headers)
            ->post("{$this->baseUrl}/vectors/upsert", $payload);

        return $this->handleResponse($response);
    }

    /**
     * Handle API response
     *
     * @param $response
     * @return array
     * @throws Exception
     */
    protected function handleResponse($response): array
    {
        if ($response->successful()) {
            return $response->json();
        }


        throw new Exception("Pinecone API Error: {$response->status()} - {$response->body()}");
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function query(array $vector, array $options = []): array
    {
        $payload = [
            'vector' => $vector,
            'topK' => Arr::get($options, 'top_k', Arr::get($this->config, 'default_top_k', 5)),
            'namespace' => Arr::get($options, 'namespace', ''),
            'includeMetadata' => Arr::get($options, 'include_metadata', true),
            'includeValues' => Arr::get($options, 'include_values', false),
        ];

        $response = Http::withHeaders($this->headers)
            ->post("{$this->baseUrl}/query", $payload);


        $json = $this->handleResponse($response);

        return $this->mapMatches(Arr::get($json, 'matches', []));

    }

    private function mapMatches(array $matches): array
    {
        return collect($matches)
            ->map(fn($m) => [
                'id' => Arr::get($m, 'id'),
                'score' => Arr::get($m, 'score'),
            ])
            ->toArray();
    }

}
