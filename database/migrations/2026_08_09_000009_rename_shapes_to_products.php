<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shapes were named after the spreadsheet strings they were derived from.
     * They are now a managed entity the owner edits directly, so the vocabulary
     * follows the change.
     */
    public function up(): void
    {
        Schema::rename('shapes', 'products');

        Schema::table('variants', function (Blueprint $table) {
            $table->renameColumn('shape_id', 'product_id');
        });

        Schema::table('value_observations', function (Blueprint $table) {
            $table->renameColumn('shape_id', 'product_id');
        });
    }

    public function down(): void
    {
        Schema::table('value_observations', function (Blueprint $table) {
            $table->renameColumn('product_id', 'shape_id');
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->renameColumn('product_id', 'shape_id');
        });

        Schema::rename('products', 'shapes');
    }
};
