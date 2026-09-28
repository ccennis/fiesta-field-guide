<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetColorMadeRequest;
use App\Http\Requests\UpdateColorRequest;
use App\Http\Resources\ChecklistEntryResource;
use App\Http\Resources\ColorResource;
use App\Models\Color;
use App\Models\Product;
use App\Services\ColorService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ColorController extends Controller
{
    public function __construct(
        private ColorService $colorService,
    ) {}

    public function update(UpdateColorRequest $request, Color $color): JsonResponse
    {
        try {
            return $this->success(new ColorResource($this->colorService->update($color, $request->validated())));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function checklist(Color $color): JsonResponse
    {
        return $this->success(ChecklistEntryResource::collection($this->colorService->checklist($color)));
    }

    public function made(SetColorMadeRequest $request, Color $color): JsonResponse
    {
        $data = $request->validated();

        try {
            $variant = $this->colorService->setMade($color, Product::findOrFail($data['product_id']), (bool) $data['made']);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new ChecklistEntryResource($variant));
    }
}
