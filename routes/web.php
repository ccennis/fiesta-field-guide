<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

// Invite links open the app, which shows the joining screen for the token.
Route::get('/invite/{token}', function () {
    return view('app');
});
