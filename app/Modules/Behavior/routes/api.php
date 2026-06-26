<?php

declare(strict_types=1);

use App\Modules\Behavior\Http\Controllers\BehaviorBeaconController;
use Illuminate\Support\Facades\Route;

Route::post('/behavior/beacon', [BehaviorBeaconController::class, 'store'])->name('behavior.beacon');
