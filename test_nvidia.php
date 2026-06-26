#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$client = new App\Modules\Enrichment\Infrastructure\LLM\NvidiaClient;
$request = new App\Modules\Enrichment\Domain\Contracts\LLMRequest(
    messages: [['role' => 'user', 'content' => 'Dis juste : test ok']],
    temperature: 0.2,
    maxTokens: 50
);

try {
    $response = $client->complete($request);
    echo "OK\n";
    echo $response->content;
} catch (Throwable $e) {
    echo "ERROR\n";
    echo get_class($e).': '.$e->getMessage()."\n";
}
