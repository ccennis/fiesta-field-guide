<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A decorated piece is the same shape and color as its plain counterpart,
     * so the uniqueness key has to include the decoration. Plain variants keep
     * a null decoration and remain the cross product; decorated ones are only
     * created where the collection data evidences them.
     */
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->foreignId('decoration_id')->nullable()->after('color_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->dropUnique(['shape_id', 'color_id']);
            $table->unique(['shape_id', 'color_id', 'decoration_id']);
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropUnique(['shape_id', 'color_id', 'decoration_id']);
            $table->dropConstrainedForeignId('decoration_id');
            $table->unique(['shape_id', 'color_id']);
        });
    }
};
