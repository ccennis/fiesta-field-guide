<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Applies fiesta-color-guide.com to the live catalog once, on deploy. On an
 * empty database there are no Fiesta colors yet and this does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('fiesta:apply-color-guide');
    }

    public function down(): void
    {
        // Swatches and years are not restored; they came from seed data that
        // no longer holds the old values.
    }
};
