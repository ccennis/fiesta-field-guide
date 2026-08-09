<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\HoldingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VariantController;
use Illuminate\Support\Facades\Route;

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
