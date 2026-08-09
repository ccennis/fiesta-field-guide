<?php

namespace App\Services;

use App\Models\Holding;

class HoldingService extends BaseService
{
    /**
     * Record another physical piece of a variant. One row per object, so this
     * is called once per piece rather than carrying a quantity.
     */
    public function create(array $data): Holding
    {
        return Holding::create($data)->load(['variant.product.line', 'variant.color']);
    }

    public function update(Holding $holding, array $data): Holding
    {
        $holding->update($data);

        return $holding->fresh(['variant.product.line', 'variant.color']);
    }
}
