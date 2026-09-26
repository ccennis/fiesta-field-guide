<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A swatch estimated from a source's product photos. It is a suggestion
     * with its method recorded, and only reaches the color when accepted.
     */
    public function up(): void
    {
        Schema::create('swatch_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('color_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('hex', 7);
            $table->unsignedSmallInteger('photos_sampled');
            $table->text('method');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['color_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swatch_suggestions');
    }
};
