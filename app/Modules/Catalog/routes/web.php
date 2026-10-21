<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\BrandController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\CollectionController;
use App\Modules\Catalog\Http\Controllers\HomeController;
use App\Modules\Catalog\Http\Controllers\LegacyProductController;
use App\Modules\Catalog\Http\Controllers\NewProductController;
use App\Modules\Catalog\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module Catalog — routes web
|--------------------------------------------------------------------------
|
| L'ordre est CRITIQUE. On déclare d'abord les routes les plus spécifiques
| (chemins littéraux), puis les routes paramétrées génériques en dernier
| pour ne pas intercepter les pages fonctionnelles (panier, compte, etc.).
|
| Patterns d'URLs :
|   /                           HomeController
|   /produits/{slug}            NewProductController     (nouveaux produits)
|   /product/{slug}             CategoryController       (catégories Drupal)
|   /brand/{slug}               BrandController          (marques)
|   /{drupalId}/{slug}          LegacyProductController  (URL Drupal courte)
|   /{drupalId}/{brandId}/{slug} LegacyProductController (URL Drupal longue)
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/a-propos', [PageController::class, 'about'])->name('pages.about');
Route::get('/cgv', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/retours', [PageController::class, 'returns'])->name('pages.returns');
Route::get('/cadeaux', [PageController::class, 'gifts'])->name('pages.gifts');
Route::get('/livraison', [PageController::class, 'delivery'])->name('pages.delivery');

// Cave de vins : collection complète et listes par catégorie.
Route::get('/vins', [CollectionController::class, 'index'])->name('wines.index');
Route::get('/vins/{slug}', [CategoryController::class, 'show'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('wines.category');

Route::get('/produits/{slug}', [NewProductController::class, 'show'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('product.canonical');

Route::get('/product/{slug}', [CategoryController::class, 'show'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('category.show');

Route::get('/brand/{slug}', [BrandController::class, 'show'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('brand.show');

Route::get('/{drupalId}/{brandId}/{slug}', [LegacyProductController::class, 'show'])
    ->where('drupalId', '[0-9]+')
    ->where('brandId', '[0-9]+')
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('product.legacy.full');

Route::get('/{drupalId}/{slug}', [LegacyProductController::class, 'show'])
    ->where('drupalId', '[0-9]+')
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('product.legacy.short');
