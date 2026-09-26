<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Record every existing product under its current name as its spreadsheet
     * name, before products start taking store names. The importers look
     * products up through these, so a renamed product is still found.
     */
    public function up(): void
    {
        $now = now();

        $rows = DB::table('products')
            ->join('lines', 'lines.id', '=', 'products.line_id')
            ->select('products.id', 'products.name', 'lines.name as line_name')
            ->get()
            ->map(fn ($product) => [
                'source' => 'collection_export',
                'external_key' => mb_strtolower($product->line_name).'|'.mb_strtolower($product->name),
                'decision' => 'mapped',
                'product_id' => $product->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('product_aliases')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        DB::table('product_aliases')->where('source', 'collection_export')->delete();
    }
};
