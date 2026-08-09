<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexColorRequest;
use App\Http\Requests\IndexProductRequest;
use App\Http\Resources\ColorResource;
use App\Http\Resources\DecorationResource;
use App\Http\Resources\LineResource;
use App\Http\Resources\ProductResource;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function __construct(
        private CatalogService $catalogService,
    ) {}

    public function lines(): JsonResponse
    {
        return $this->success(LineResource::collection($this->catalogService->lines()));
    }

    public function products(IndexProductRequest $request): JsonResponse
    {
        return $this->success(ProductResource::collection($this->catalogService->products($request->validated())));
    }

    public function colors(IndexColorRequest $request): JsonResponse
    {
        return $this->success(ColorResource::collection($this->catalogService->colors($request->validated())));
    }

    public function decorations(): JsonResponse
    {
        return $this->success(DecorationResource::collection($this->catalogService->decorations()));
    }

    public function summary(): JsonResponse
    {
        return $this->success($this->catalogService->summary());
    }
}
