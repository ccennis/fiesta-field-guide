<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per product and color an outside source lists. The names are kept
     * raw alongside a normalized key, so rulings match on the key and the report
     * can still show what the source actually said.
     */
    public function up(): void
    {
        Schema::create('external_listings', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('external_product_id');
            $table->string('external_variant_id');
            $table->string('title');
            $table->string('product_name');
            $table->string('product_key');
            $table->string('color_name');
            $table->string('color_key');
            $table->boolean('is_retired')->default(false);
            $table->boolean('is_set')->default(false);
            $table->string('url');
            $table->string('image_url')->nullable();
            $table->foreignId('variant_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['source', 'external_variant_id']);
            $table->index(['source', 'product_key']);
            $table->index(['source', 'color_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_listings');
    }
};
