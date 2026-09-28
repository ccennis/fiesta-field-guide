<?php

namespace App\Services;

use App\Enums\VariantExistence;
use App\Models\Holding;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class HoldingService extends BaseService
{
    public function __construct(
        private WishlistService $wishlistService,
    ) {}

    /**
     * Record another physical piece for the person signed in. One row per
     * object, so this is called once per piece rather than carrying a
     * quantity. The piece crosses off their own matching wishlist item.
     *
     * The admin holding a piece is evidence it was made, as it has been since
     * the first import, so it confirms the variant. A member's piece does not
     * yet, since members' claims are not reviewed.
     */
    public function create(array $data, User $user): Holding
    {
        return DB::transaction(function () use ($data, $user) {
            $holding = Holding::create(['user_id' => $user->id] + $data)
                ->load(['variant.product.line', 'variant.color', 'user']);

            if ($user->isAdmin() && $holding->variant->existence !== VariantExistence::Confirmed) {
                $holding->variant->update(['existence' => VariantExistence::Confirmed]);
            }

            $holding->setRelation('fulfilledWishlistItem', $this->wishlistService->fulfill($holding));

            return $holding;
        });
    }

    public function update(Holding $holding, array $data, User $user): Holding
    {
        // Someone else's piece is reported as missing, not forbidden.
        if ($holding->user_id !== $user->id) {
            throw (new ModelNotFoundException)->setModel(Holding::class, [$holding->id]);
        }

        $holding->update($data);

        return $holding->fresh(['variant.product.line', 'variant.color']);
    }
}
