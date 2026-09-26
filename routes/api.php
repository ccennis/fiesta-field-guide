<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\HoldingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('lines', [CatalogController::class, 'lines']);
    Route::get('products', [CatalogController::class, 'products']);
    Route::get('colors', [CatalogController::class, 'colors']);
    Route::get('decorations', [CatalogController::class, 'decorations']);
    Route::get('collection/summary', [CatalogController::class, 'summary']);

    Route::get('variants', [VariantController::class, 'index']);
    Route::get('variants/{variant}', [VariantController::class, 'show']);

    Route::post('products', [ProductController::class, 'store']);
    Route::patch('products/{product}', [ProductController::class, 'update']);
    Route::post('products/{product}/merge', [ProductController::class, 'merge']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    Route::patch('colors/{color}', [ColorController::class, 'update']);

    Route::post('holdings', [HoldingController::class, 'store']);
    Route::patch('holdings/{holding}', [HoldingController::class, 'update']);

    Route::get('wishlist', [WishlistController::class, 'index']);
    Route::post('wishlist', [WishlistController::class, 'store']);
    Route::patch('wishlist/{wishlistItem}', [WishlistController::class, 'update']);
    Route::delete('wishlist/{wishlistItem}', [WishlistController::class, 'destroy']);
});
