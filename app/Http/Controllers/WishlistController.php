<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexWishlistRequest;
use App\Http\Requests\StoreWishlistItemRequest;
use App\Http\Requests\UpdateWishlistItemRequest;
use App\Http\Resources\WishlistItemResource;
use App\Models\WishlistItem;
use App\Services\WishlistService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WishlistController extends Controller
{
    public function __construct(
        private WishlistService $wishlistService,
    ) {}

    public function index(IndexWishlistRequest $request): JsonResponse
    {
        return $this->success(WishlistItemResource::collection($this->wishlistService->list($request->validated(), $request->user())));
    }

    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        try {
            $item = $this->wishlistService->create($request->validated(), $request->user());
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created(new WishlistItemResource($item));
    }

    public function update(UpdateWishlistItemRequest $request, WishlistItem $wishlistItem): JsonResponse
    {
        try {
            $item = $this->wishlistService->update($wishlistItem, $request->validated(), $request->user());
        } catch (ModelNotFoundException $e) {
            // Someone else's item: let it answer as a plain 404.
            throw $e;
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new WishlistItemResource($item));
    }

    public function destroy(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        $this->wishlistService->delete($wishlistItem, $request->user());

        return $this->noContent();
    }
}
