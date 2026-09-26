<?php

namespace App\Services;

use App\Models\Holding;
use Illuminate\Support\Facades\DB;

class HoldingService extends BaseService
{
    public function __construct(
        private WishlistService $wishlistService,
    ) {}

    /**
     * Record another physical piece of a variant. One row per object, so this
     * is called once per piece rather than carrying a quantity. A matching open
     * wishlist item is fulfilled by the new piece.
     */
    public function create(array $data): Holding
    {
        return DB::transaction(function () use ($data) {
            $holding = Holding::create($data)->load(['variant.product.line', 'variant.color']);
            $holding->setRelation('fulfilledWishlistItem', $this->wishlistService->fulfill($holding));

            return $holding;
        });
    }

    public function update(Holding $holding, array $data): Holding
    {
        $holding->update($data);

        return $holding->fresh(['variant.product.line', 'variant.color']);
    }
}
