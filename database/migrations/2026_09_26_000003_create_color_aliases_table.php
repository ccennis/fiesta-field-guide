<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The owner's ruling on a color name a source uses. Names repeat across
     * eras, so a ruling points at one specific color row, never at a name.
     */
    public function up(): void
    {
        Schema::create('color_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('external_key');
            $table->string('decision');
            $table->foreignId('color_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['source', 'external_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('color_aliases');
    }
};
