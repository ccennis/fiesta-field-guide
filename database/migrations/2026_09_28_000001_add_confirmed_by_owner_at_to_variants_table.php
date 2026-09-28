<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records that the owner said a product was made in a color. Without it, a
 * confirmation the owner made by hand would be undone when a store listing
 * that also evidenced it goes away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->timestamp('confirmed_by_owner_at')->nullable()->after('existence');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropColumn('confirmed_by_owner_at');
        });
    }
};
