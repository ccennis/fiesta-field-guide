<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The owner's ruling on a product name a source uses. A null product with
     * an ignored decision means the name was deliberately left out.
     */
    public function up(): void
    {
        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('external_key');
            $table->string('decision');
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['source', 'external_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_aliases');
    }
};
