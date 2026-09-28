<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

// Invite links open the app, which shows the joining screen for the token.
Route::get('/invite/{token}', function () {
    return view('app');
});

// The link in the password reset email opens the app, which asks for a new password.
Route::get('/reset-password/{token}', function () {
    return view('app');
});

// The link in the confirmation email. The name is the one Laravel's
// verification email builds its link from.
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->whereNumber('id')
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
