<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Modules\Search\Domain\Models\SearchLog;
use App\Modules\Demand\Application\DemandCaptureService;
use App\Modules\Demand\Domain\Models\DemandSignal;
use Illuminate\Support\Facades\DB;

// Clean start
DB::table('demand_signals')->truncate();
DB::table('search_logs')->truncate();

$log = SearchLog::create([
    'query' => 'test la demande 123',
    'query_normalized' => 'test la demande 123',
    'hits_count' => 0,
    'duration_ms' => 10,
    'session_id' => '546bcadb-1eda-4c39-898a-ae7a1b6fd9bc'
]);

$service = app(DemandCaptureService::class);
$service->captureFromSearchLog($log);

echo "Demand Signals Count: " . DemandSignal::count() . "\n";
