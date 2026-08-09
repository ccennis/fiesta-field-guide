<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A variant is a shape and color pairing within a single line. Rows are
     * generated as a cross product, so most are unconfirmed until evidence
     * says otherwise.
     */
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shape_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained()->cascadeOnDelete();
            $table->string('existence')->default('unconfirmed');
            $table->string('rarity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shape_id', 'color_id']);
            $table->index('existence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
