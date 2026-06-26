<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Application;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $jobId) {}

    public function handle(EnrichmentOrchestrator $orchestrator): void
    {
        $orchestrator->process($this->jobId);
    }
}
