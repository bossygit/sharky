<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Modules\Behavior\Domain\Models\BehaviorEvent;
use Illuminate\Support\Facades\DB;

$uuid = '546bcadb-1eda-4c39-898a-ae7a1b6fd9bc';
$count = DB::table('behavior_events')->where('session_id', $uuid)->count();

echo "Events found for $uuid: $count\n";
