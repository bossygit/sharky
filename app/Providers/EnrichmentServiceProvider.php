<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Enrichment\Domain\Contracts\LLMClient;
use App\Modules\Enrichment\Domain\Contracts\WebSearchClient;
use App\Modules\Enrichment\Infrastructure\LLM\DeepSeekClient;
use App\Modules\Enrichment\Infrastructure\LLM\NvidiaClient;
use App\Modules\Enrichment\Infrastructure\LLM\StubLLMClient;
use App\Modules\Enrichment\Infrastructure\WebSearch\BraveSearchClient;
use App\Modules\Enrichment\Infrastructure\WebSearch\StubWebSearchClient;
use Illuminate\Support\ServiceProvider;

class EnrichmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LLMClient::class, function () {
            if ($this->app->environment('testing')) {
                return new StubLLMClient;
            }

            $provider = config('enrichment.llm.provider');

            return match ($provider) {
                'nvidia' => new NvidiaClient,
                'deepseek' => new DeepSeekClient,
                default => $this->app->environment('testing') || ! config('enrichment.llm.deepseek.api_key')
                    ? new StubLLMClient
                    : new DeepSeekClient,
            };
        });

        $this->app->singleton(WebSearchClient::class, function () {
            if ($this->app->environment('testing') || ! config('enrichment.websearch.brave.api_key')) {
                return new StubWebSearchClient;
            }

            return new BraveSearchClient;
        });
    }
}
