<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Infrastructure\LLM;

use App\Modules\Enrichment\Domain\Contracts\LLMClient;
use App\Modules\Enrichment\Domain\Contracts\LLMRequest;
use App\Modules\Enrichment\Domain\Contracts\LLMResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NvidiaClient implements LLMClient
{
    public function complete(LLMRequest $request): LLMResponse
    {
        $apiKey = config('enrichment.llm.nvidia.api_key');

        if (! $apiKey) {
            throw new RuntimeException('NVIDIA_API_KEY manquante.');
        }

        $payload = [
            'model' => config('enrichment.llm.nvidia.model', 'google/gemma-4-31b-it'),
            'messages' => $request->messages,
            'temperature' => $request->temperature ?? 0.2,
        ];

        if ($request->topP !== null) {
            $payload['top_p'] = $request->topP;
        }

        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        if ($request->jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $baseUrl = rtrim((string) config('enrichment.llm.nvidia.base_url', 'https://integrate.api.nvidia.com/v1'), '/').'/';

        $response = Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->timeout((int) config('enrichment.llm.nvidia.timeout', 60))
            ->retry(
                (int) config('enrichment.llm.deepseek.max_retries', 5),
                fn (int $attempt) => (int) min(30000, (100 * (2 ** ($attempt - 1))) + random_int(0, 1000)),
                fn (\Throwable $e) => $this->shouldRetry($e),
            )
            ->post('chat/completions', $payload)
            ->throw();

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Réponse NVIDIA vide.');
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
