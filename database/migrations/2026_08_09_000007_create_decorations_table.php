<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An applied decoration such as a decal, as distinct from the glaze
     * underneath it. A decorated piece is a product in a color with a decoration
     * on top, and the decoration carries its own production run and rarity.
     */
    public function up(): void
    {
        Schema::create('decorations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedSmallInteger('produced_from')->nullable();
            $table->unsignedSmallInteger('produced_to')->nullable();
            $table->string('rarity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decorations');
    }
};
