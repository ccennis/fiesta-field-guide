<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('produced_from')->nullable();
            $table->unsignedSmallInteger('produced_to')->nullable();
            $table->string('rarity')->nullable();
            $table->string('hex', 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['line_id', 'name', 'produced_from']);
            $table->index('produced_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
