<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
use App\Modules\Catalog\Domain\Models\ProductVariant;
$v = ProductVariant::first();
echo $v ? $v->id : 'NONE';
