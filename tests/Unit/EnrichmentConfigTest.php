<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Enrichment\Domain\Contracts\LLMClient;
use App\Modules\Enrichment\Infrastructure\LLM\DeepSeekClient;
use Tests\TestCase;

class EnrichmentConfigTest extends TestCase
{
    public function test_llm_config_is_nested_under_llm_key(): void
    {
        $this->assertSame('deepseek', config('enrichment.llm.provider'));
        $this->assertArrayHasKey('api_key', config('enrichment.llm.deepseek'));
        $this->assertArrayHasKey('api_key', config('enrichment.llm.nvidia'));
        $this->assertArrayHasKey('api_key', config('enrichment.websearch.brave'));
    }

    public function test_deepseek_is_bound_when_api_key_present_outside_testing(): void
    {
        $this->app['env'] = 'production';
        config([
            'enrichment.llm.provider' => 'deepseek',
            'enrichment.llm.deepseek.api_key' => 'sk-test',
        ]);

        $this->app->forgetInstance(LLMClient::class);

        $this->assertInstanceOf(DeepSeekClient::class, $this->app->make(LLMClient::class));
    }
}
