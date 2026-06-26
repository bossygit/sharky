<?php

declare(strict_types=1);

use App\Modules\Admin\Http\Controllers\AuthController;
use App\Modules\Admin\Http\Controllers\BrandController;
use App\Modules\Admin\Http\Controllers\CategoryController;
use App\Modules\Admin\Http\Controllers\CommerceSettingsController;
use App\Modules\Admin\Http\Controllers\DashboardController;
use App\Modules\Admin\Http\Controllers\DemandAdminController;
use App\Modules\Admin\Http\Controllers\EnrichmentReviewController;
use App\Modules\Admin\Http\Controllers\OrderAdminController;
use App\Modules\Admin\Http\Controllers\ProductController;
use App\Modules\Admin\Http\Controllers\SearchSynonymController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
        Route::post('products/{product}/enrich', [ProductController::class, 'enrich'])->name('products.enrich');
        Route::post('products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
        Route::delete('products/{product}/images/{media}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');

        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('brands/create', [BrandController::class, 'create'])->name('brands.create');
        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
        Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
        Route::put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');

        Route::get('orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderAdminController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/status', [OrderAdminController::class, 'updateStatus'])->name('orders.status');

        Route::get('commerce/settings', [CommerceSettingsController::class, 'edit'])->name('commerce.settings');
        Route::put('commerce/settings', [CommerceSettingsController::class, 'update'])->name('commerce.settings.update');

        Route::get('search/synonyms', [SearchSynonymController::class, 'index'])->name('search.synonyms');
        Route::post('search/synonyms', [SearchSynonymController::class, 'store'])->name('search.synonyms.store');
        Route::delete('search/synonyms/{synonym}', [SearchSynonymController::class, 'destroy'])->name('search.synonyms.destroy');

        Route::get('demand', [DemandAdminController::class, 'index'])->name('demand.index');
        Route::post('demand/refresh', [DemandAdminController::class, 'refresh'])->name('demand.refresh');
        Route::get('demand/clusters/{cluster}', [DemandAdminController::class, 'show'])->name('demand.clusters.show');
        Route::post('demand/clusters/{cluster}/source', [DemandAdminController::class, 'source'])->name('demand.clusters.source');
        Route::post('demand/clusters/{cluster}/reject', [DemandAdminController::class, 'reject'])->name('demand.clusters.reject');

        Route::get('enrichment', [EnrichmentReviewController::class, 'index'])->name('enrichment.index');
        Route::get('enrichment/products/{product}', [EnrichmentReviewController::class, 'show'])->name('enrichment.show');
        Route::post('enrichment/products/{product}/approve', [EnrichmentReviewController::class, 'approve'])->name('enrichment.approve');
        Route::post('enrichment/products/{product}/reject', [EnrichmentReviewController::class, 'reject'])->name('enrichment.reject');
        Route::put('enrichment/drafts/{draft}', [EnrichmentReviewController::class, 'updateDraft'])->name('enrichment.drafts.update');
    });
});
