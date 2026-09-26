<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A wishlist item always names a product. A null variant means that product
     * in any plain color. Fulfilled items are kept, pointing at the holding that
     * fulfilled them.
     */
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('priority')->default('want');
            $table->decimal('max_price', 10, 2)->nullable();
            $table->string('source');
            $table->text('notes')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('fulfilled_by_holding_id')->nullable()->constrained('holdings')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'variant_id', 'fulfilled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
