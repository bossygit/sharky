<?php

declare(strict_types=1);

use App\Modules\Commerce\Http\Controllers\CartController;
use App\Modules\Commerce\Http\Controllers\CheckoutController;
use App\Modules\Commerce\Http\Controllers\OrderController;
use App\Modules\Commerce\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/panier', [CartController::class, 'show'])->name('cart.show');
Route::post('/panier/ajouter', [CartController::class, 'add'])->name('cart.add');
Route::patch('/panier/{variantId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/panier/{variantId}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/otp', [CheckoutController::class, 'sendOtp'])->name('checkout.otp');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/commande/{uuid}', [OrderController::class, 'show'])->name('order.show');

Route::post('/webhooks/payments/airtel', [PaymentWebhookController::class, 'airtel'])->name('webhooks.payments.airtel');
Route::post('/webhooks/payments/mtn', [PaymentWebhookController::class, 'mtn'])->name('webhooks.payments.mtn');
