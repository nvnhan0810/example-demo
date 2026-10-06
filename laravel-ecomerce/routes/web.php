<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\ProductController;

Route::get('/up', function () {
    return response()->json([]);
});

Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
Route::post('/add-to-cart', [StorefrontController::class, 'addToCart'])->name('storefront.cart.add');

Route::prefix('products')->name('products.')->group(function () {
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit')->whereNumber('product');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update')->whereNumber('product');
});

Route::get('/products/{product}', [StorefrontController::class, 'show'])
    ->name('storefront.show')
    ->whereNumber('product');
