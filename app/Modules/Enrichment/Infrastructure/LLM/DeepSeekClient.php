<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Infrastructure\LLM;

use App\Modules\Enrichment\Domain\Contracts\LLMClient;
use App\Modules\Enrichment\Domain\Contracts\LLMRequest;
use App\Modules\Enrichment\Domain\Contracts\LLMResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeepSeekClient implements LLMClient
{
    public function complete(LLMRequest $request): LLMResponse
    {
        $apiKey = config('enrichment.llm.deepseek.api_key');
        if (! $apiKey) {
            throw new RuntimeException('DEEPSEEK_API_KEY manquante.');
        }

        $maxRetries = (int) config('enrichment.llm.deepseek.max_retries', 5);
        $payload = [
            'model' => config('enrichment.llm.deepseek.model'),
            'messages' => $request->messages,
            'temperature' => $request->temperature,
        ];

        if ($request->jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::baseUrl(rtrim((string) config('enrichment.llm.deepseek.base_url'), '/').'/')
            ->withToken($apiKey)
            ->timeout((int) config('enrichment.llm.deepseek.timeout', 60))
            ->retry(
                $maxRetries,
                fn (int $attempt) => (int) min(30000, (100 * (2 ** ($attempt - 1))) + random_int(0, 1000)),
                fn (\Throwable $e) => $this->shouldRetry($e),
            )
            ->post('chat/completions', $payload)
            ->throw();

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Réponse DeepSeek vide.');
        }

        return new LLMResponse(
            content: $content,
            usage: $response->json('usage'),
        );
    }

    private function shouldRetry(\Throwable $e): bool
    {
        if ($e instanceof RequestException) {
            $status = $e->response?->status();

            return $status === 429 || ($status !== null && $status >= 500);
        }

        return false;
    }
}
