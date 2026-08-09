<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergeProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->created(new ProductResource($this->productService->create($request->validated())));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        return $this->success(new ProductResource($this->productService->update($product, $request->validated())));
    }

    public function merge(MergeProductRequest $request, Product $product): JsonResponse
    {
        $target = Product::findOrFail($request->validated()['into']);

        try {
            $moved = $this->productService->merge($product, $target);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($moved, "Merged into {$target->name}.");
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->delete($product);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->noContent();
    }
}
