<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pieces and wishes belong to a person. Everything recorded so far is the
     * owner's. On a fresh install the importers run before any login exists, so
     * the column allows no owner yet; the first account created claims them.
     */
    public function up(): void
    {
        foreach (['holdings', 'wishlist_items'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $blueprint->index('user_id');
            });
        }

        $owner = DB::table('users')->where('role', 'owner')->value('id');

        if ($owner !== null) {
            DB::table('holdings')->whereNull('user_id')->update(['user_id' => $owner]);
            DB::table('wishlist_items')->whereNull('user_id')->update(['user_id' => $owner]);
        }
    }

    public function down(): void
    {
        foreach (['holdings', 'wishlist_items'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('user_id');
            });
        }
    }
};
