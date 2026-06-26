<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Modules\Catalog\Domain\Models\Product;

try {
    echo Product::count();
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
