<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexWishlistRequest;
use App\Http\Requests\StoreWishlistItemRequest;
use App\Http\Requests\UpdateWishlistItemRequest;
use App\Http\Resources\WishlistItemResource;
use App\Models\WishlistItem;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class WishlistController extends Controller
{
    public function __construct(
        private WishlistService $wishlistService,
    ) {}

    public function index(IndexWishlistRequest $request): JsonResponse
    {
        return $this->success(WishlistItemResource::collection($this->wishlistService->list($request->validated())));
    }

    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        try {
            $item = $this->wishlistService->create($request->validated());
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created(new WishlistItemResource($item));
    }

    public function update(UpdateWishlistItemRequest $request, WishlistItem $wishlistItem): JsonResponse
    {
        try {
            $item = $this->wishlistService->update($wishlistItem, $request->validated());
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new WishlistItemResource($item));
    }

    public function destroy(WishlistItem $wishlistItem): JsonResponse
    {
        $this->wishlistService->delete($wishlistItem);

        return $this->noContent();
    }
}
