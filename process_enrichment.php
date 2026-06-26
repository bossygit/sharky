<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Enrichment\Domain\Models\EnrichmentJob;

$jobs = EnrichmentJob::where('status', 'queued')->get();
$processed = 0;
foreach ($jobs as $job) {
    try {
        app(App\Modules\Enrichment\Application\EnrichmentOrchestrator::class)->process($job->id);
        $processed++;
        echo "Job #{$job->id} OK\n";
    } catch (\Exception $e) {
        echo "Job #{$job->id} FAIL: {$e->getMessage()}\n";
    }
}
echo "Total processed: {$processed}\n";
