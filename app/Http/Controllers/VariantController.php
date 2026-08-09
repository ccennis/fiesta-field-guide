<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexVariantRequest;
use App\Http\Resources\VariantCollection;
use App\Http\Resources\VariantDetailResource;
use App\Models\Variant;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;

class VariantController extends Controller
{
    public function __construct(
        private CatalogService $catalogService,
    ) {}

    /**
     * Backs both the collection view and the missing view. They are the same
     * query with `owned` flipped.
     */
    public function index(IndexVariantRequest $request): JsonResponse
    {
        return $this->success(new VariantCollection($this->catalogService->variants($request->validated())));
    }

    public function show(Variant $variant): JsonResponse
    {
        return $this->success(new VariantDetailResource($this->catalogService->identify($variant)));
    }
}
