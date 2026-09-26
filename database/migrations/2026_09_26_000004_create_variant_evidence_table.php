<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Why a variant is believed to exist. Each row cites the outside listing
     * that shows it, so a confirmed variant can always say where that came from.
     */
    public function up(): void
    {
        Schema::create('variant_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_listing_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['variant_id', 'external_listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_evidence');
    }
};
