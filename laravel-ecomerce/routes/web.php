<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\ProductController;

// Hiển thị giao diện Storefront
Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');

// Xử lý action thêm vào giỏ hàng từ Inertia
Route::post('/add-to-cart', [StorefrontController::class, 'addToCart'])->name('storefront.cart.add');

// Group Route cho phần quản lý sản phẩm
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
});