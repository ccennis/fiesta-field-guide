<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A value observation always names a shape. A null color means the figure
     * applies to that shape in any color, which is how the owner's blanket
     * per-shape numbers are stored without inflating one guess into hundreds of
     * rows. A non-null color is specific to that variant and wins at read time.
     */
    public function up(): void
    {
        Schema::create('value_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shape_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('source');
            $table->date('observed_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shape_id', 'color_id', 'observed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('value_observations');
    }
};
