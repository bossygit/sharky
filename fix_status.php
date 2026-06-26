<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

DB::table('products')->update(['status' => 'published']);
echo "All products set to published.\n";
