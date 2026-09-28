<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\HoldingController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InviteAcceptController;
use App\Http\Controllers\ListingReviewController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SwatchSuggestionController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');

// Joining with an invite link happens before there is an account to sign in with.
Route::middleware('throttle:10,1')->group(function () {
    Route::get('invites/{token}', [InviteAcceptController::class, 'show']);
    Route::post('invites/{token}/accept', [InviteAcceptController::class, 'accept']);
});

Route::middleware(['auth', 'active'])->group(function () {
    // Reachable before the email is confirmed, so the app can say what to do.
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1');

    Route::middleware('verified')->group(function () {
        Route::get('lines', [CatalogController::class, 'lines']);
        Route::get('products', [CatalogController::class, 'products']);
        Route::get('colors', [CatalogController::class, 'colors']);
        Route::get('decorations', [CatalogController::class, 'decorations']);
        Route::get('collection/summary', [CatalogController::class, 'summary']);
        Route::get('browse', [BrowseController::class, 'index']);

        Route::get('variants', [VariantController::class, 'index']);
        Route::get('variants/{variant}', [VariantController::class, 'show']);

        Route::post('holdings', [HoldingController::class, 'store']);
        Route::patch('holdings/{holding}', [HoldingController::class, 'update']);

        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist', [WishlistController::class, 'store']);
        Route::patch('wishlist/{wishlistItem}', [WishlistController::class, 'update']);
        Route::delete('wishlist/{wishlistItem}', [WishlistController::class, 'destroy']);

        // The shared catalog, and who may use the app, are the admin's to change.
        Route::middleware('admin')->group(function () {
            Route::post('products', [ProductController::class, 'store']);
            Route::patch('products/{product}', [ProductController::class, 'update']);
            Route::post('products/{product}/merge', [ProductController::class, 'merge']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);

            Route::patch('colors/{color}', [ColorController::class, 'update']);
            Route::get('colors/{color}/checklist', [ColorController::class, 'checklist']);
            Route::post('colors/{color}/made', [ColorController::class, 'made']);

            Route::get('sources/{source}/names', [ListingReviewController::class, 'index']);
            Route::post('sources/{source}/rulings', [ListingReviewController::class, 'rule']);
            Route::post('sources/{source}/rulings/create', [ListingReviewController::class, 'create']);
            Route::post('sources/{source}/rulings/create-products', [ListingReviewController::class, 'createProducts']);
            Route::post('sources/{source}/rulings/undo', [ListingReviewController::class, 'undo']);

            Route::get('swatch-suggestions', [SwatchSuggestionController::class, 'index']);
            Route::post('swatch-suggestions/{swatchSuggestion}/accept', [SwatchSuggestionController::class, 'accept']);
            Route::post('swatch-suggestions/{swatchSuggestion}/dismiss', [SwatchSuggestionController::class, 'dismiss']);

            Route::get('invitations', [InvitationController::class, 'index']);
            Route::post('invitations', [InvitationController::class, 'store']);
            Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy']);

            Route::get('members', [MemberController::class, 'index']);
            Route::post('members/{user}/disable', [MemberController::class, 'disable']);
            Route::post('members/{user}/enable', [MemberController::class, 'enable']);
        });
    });
});
