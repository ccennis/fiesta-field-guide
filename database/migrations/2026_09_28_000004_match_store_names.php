<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Store names that exactly match a catalog name are now tied without asking.
 * This ties the ones already imported, on deploy, rather than waiting for the
 * weekly store import. It runs after the color guide, so the colors the guide
 * added are matched too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('fiesta:match-store-names');
    }

    public function down(): void
    {
        // Ties made here are ordinary rulings and can be undone in Admin.
    }
};
