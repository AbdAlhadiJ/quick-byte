<?php

namespace App\Services\News\Classfier;

class ClassifierPromptBuilder
{
    private const MODEL = 'gpt-4o-mini';
    private const TEMPERATURE = 0.2;

    /**
     * Build the full chat payload for classifying and selecting top news.
     *
     * @param array $news
     * @param int $selectCount
     * @return array
     */
    public function buildFor(array $news, int $selectCount = 2): array
    {
        return [
            'model' => self::MODEL,
            'temperature' => self::TEMPERATURE,
            'messages' => [
                $this->buildSystemMessage($selectCount),
                $this->buildUserMessage($news, $selectCount),
            ],
        ];
    }

    /**
     * System prompt guiding the model to smartly classify articles.
     * @param int $selectCount
     * @return array
     */
    private function buildSystemMessage(int $selectCount): array
    {

        $message = "You are a tech news classification assistant (evaluator).\n";
        $message .= "🚨 **Strict Rules:**\n";
        $message .= " - **JSON Only:** Output MUST be valid JSON; no markdown or extra text.\n";
        $message .= " - **Exact Content:** Preserve all fields ('source','title','description','url','category') exactly as provided.\n";
        $message .= " - **No Commentary:** Do NOT add any explanations, reasoning, or commentary.\n";
        $message .= " - **Fixed Selection:** Select exactly {$selectCount} articles (no more, no less).\n";
        $message .= " - **Renderable Subjects Only:** Skip any story featuring a recognizable person, trademarked logo, fictional character, brand-specific UI, or anything our engine can’t safely depict generically.\n";
        $message .= "\nEnsure the output is a JSON array of objects with exactly these five keys in this order: 'source', 'title', 'description', 'url', 'category'.\n"
            . "Do not alter, truncate, normalize, or append anything to the field values.";

        return [
            'role' => 'system',
            'content' => $message,
        ];

    }

    /**
     * User prompt listing the articles to classify.
     * @param array $news
     * @param int $selectCount
     * @return array
     */
    private function buildUserMessage(array $news, int $selectCount): array
    {
        $content = "Below is a list of tech news articles with full metadata.\n";
        $content .= "### Instructions:\n";
        $content .= "- Select exactly {$selectCount} articles from the list below.\n";
        $content .= "- Preserve all provided fields exactly as is.\n\n";

        foreach ($news as $index => $article) {
            // Sanitize to remove any HTML or special content
            $source = strip_tags($article['source'] ?? '');
            $title = strip_tags($article['title'] ?? '');
            $description = strip_tags($article['description'] ?? '');
            $url = filter_var($article['url'] ?? '', FILTER_SANITIZE_URL);
            $category = strip_tags($article['category'] ?? '');

            // Truncate long descriptions for safety
            if (strlen($description) > 1000) {
                $description = substr($description, 0, 1000) . '...';
            }

            $content .= "### Article {$index}\n";
            $content .= "source: \"{$source}\"\n";
            $content .= "title: \"{$title}\"\n";
            $content .= "description: \"{$description}\"\n";
            $content .= "url: \"{$url}\"\n";
            $content .= "category: \"{$category}\"\n\n";
        }

        $content .= "Please output ONLY a JSON array of objects with the keys 'source', 'title', 'description', 'url', and 'category'.";
        $content .= " Do not include any extra text or explanation.";


        return [
            'role' => 'user',
            'content' => $content,
        ];

    }
}
