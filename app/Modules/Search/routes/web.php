<?php

declare(strict_types=1);

use App\Modules\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/recherche', [SearchController::class, 'index'])->name('search');
Route::get('/recherche/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
