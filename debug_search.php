<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Modules\Catalog\Domain\Models\Product;

$count = Product::where('status', 'published')->count();
$septivon = Product::where('status', 'published')->where('title', 'ilike', '%septivon%')->count();

echo "Total Published: $count\n";
echo "Septivon Matches: $septivon\n";
