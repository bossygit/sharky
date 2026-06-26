<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Contracts;

final readonly class LLMRequest
{
    /** @param  list<array{role: string, content: string}>  $messages */
    public function __construct(
        public array $messages,
        public bool $jsonMode = true,
        public ?float $temperature = 0.2,
        public ?float $topP = null,
        public ?int $maxTokens = null,
    ) {}
}

final readonly class LLMResponse
{
    public function __construct(
        public string $content,
        public ?array $usage = null,
    ) {}
}

interface LLMClient
{
    public function complete(LLMRequest $request): LLMResponse;
}
