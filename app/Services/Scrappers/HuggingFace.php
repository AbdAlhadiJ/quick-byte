<?php

namespace App\Services\Scrappers;

use App\Contracts\ArticleSummarizerInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HuggingFace implements ArticleSummarizerInterface
{
    private const TIMEOUT = 60;
    private const CONNECT_TIMEOUT = 10;
    private const MAX_RETRIES = 3;
    private const BACKOFF_BASE = 1;
    private const MAX_RECURSION = 3;

    private int $maxTokens;
    private int $charsPerToken = 4;
    private int $maxChunkSizeChars;

    public function __construct(protected array $cfg)
    {


        $this->maxTokens = (int)$cfg['max_tokens'];
        $this->maxChunkSizeChars = $this->maxTokens * $this->charsPerToken;
    }

    /**
     * @inheritDoc
     * @throws ConnectionException
     */
    public function summarize(string $text, int $depth = 0): string
    {
        if ($depth > self::MAX_RECURSION) {
            return trim($text);
        }

        if ($this->approxTokenCount($text) > $this->maxTokens) {
            $chunks = $this->chunkText($text, $this->maxChunkSizeChars);
            $summaries = [];

            foreach ($chunks as $chunk) {
                $summaries[] = $this->summarize($chunk, $depth + 1);
            }

            $combined = implode("\n\n", $summaries);
            if ($this->approxTokenCount($combined) >= $this->approxTokenCount($text) * 0.8) {
                return $combined;
            }

            return $this->summarize($combined, $depth + 1);
        }

        $attempt = 0;
        do {
            if ($attempt > 0) {
                sleep(self::BACKOFF_BASE * (2 ** ($attempt - 1)));
            }
            $response = Http::retry(0, 0)
                ->timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->withToken($this->cfg['api_key'])
                ->acceptJson()
                ->post(rtrim($this->cfg['base_uri'], '/') . '/models/' . $this->cfg['model'], [
                    'inputs' => $text,
                    'parameters' => [
                        'max_length' => $this->cfg['max_length'],
                        'min_length' => $this->cfg['min_length'],
                        'do_sample' => false,
                        'max_new_tokens' => $this->maxTokens,
                    ],
                ]);
            $attempt++;
        } while ($response->status() === 429 && $attempt < self::MAX_RETRIES);

        if ($response->failed()) {
            $error = $response->json('error', $response->body());
            throw new RuntimeException('Hugging Face API error: ' . $error);
        }

        $data = $response->json();
        if (!is_array($data) || empty($data[0]['summary_text'])) {
            throw new RuntimeException('Empty or invalid summary received.');
        }

        return $data[0]['summary_text'];
    }

    /**
     * Approximate token count by character length.
     */
    private function approxTokenCount(string $text): int
    {
        return (int)ceil(mb_strlen($text) / $this->charsPerToken);
    }

    /**
     * Break a large string into an array of substrings, each no longer
     * than $maxChars, splitting on sentence boundaries with basic abbreviation handling.
     */
    private function chunkText(string $text, int $maxChars): array
    {
        $abbreviations = ['Mr', 'Mrs', 'Dr', 'Ms', 'Prof', 'Sr', 'Jr', 'e.g', 'i.e'];
        foreach ($abbreviations as $abbr) {
            $text = preg_replace('/\b' . preg_quote($abbr, '/') . '\./u', $abbr . '<ABBR>', $text);
        }

        $sentences = preg_split('/(?<=[\.\?!])\s+/u', $text);

        foreach ($sentences as &$sentence) {
            $sentence = str_replace('<ABBR>', '.', $sentence);
        }
        unset($sentence);

        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') continue;

            if (mb_strlen($current) + mb_strlen($sentence) + 1 <= $maxChars) {
                $current .= ($current === '' ? '' : ' ') . $sentence;
            } else {
                if ($current !== '') {
                    $chunks[] = $current;
                }
                if (mb_strlen($sentence) > $maxChars) {
                    $offset = 0;
                    $length = mb_strlen($sentence);
                    while ($offset < $length) {
                        $chunks[] = mb_substr($sentence, $offset, $maxChars);
                        $offset += $maxChars;
                    }
                    $current = '';
                } else {
                    $current = $sentence;
                }
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
