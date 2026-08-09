<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shapes carry no production years. The source data does not record them,
     * so the columns are deliberately absent rather than present and null.
     *
     * Renamed to `products` by a later migration once they became a managed
     * entity rather than strings derived from the export.
     */
    public function up(): void
    {
        Schema::create('shapes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rarity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['line_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shapes');
    }
};
