<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The reset email links to the app's own page. Only the token travels in
        // the link; the page asks for the email address again.
        ResetPassword::createUrlUsing(fn ($user, string $token) => url('/reset-password/'.$token));
    }
}
