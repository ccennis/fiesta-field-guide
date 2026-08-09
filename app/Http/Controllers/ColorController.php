<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateColorRequest;
use App\Http\Resources\ColorResource;
use App\Models\Color;
use App\Services\ColorService;
use Illuminate\Http\JsonResponse;

class ColorController extends Controller
{
    public function __construct(
        private ColorService $colorService,
    ) {}

    public function update(UpdateColorRequest $request, Color $color): JsonResponse
    {
        return $this->success(new ColorResource($this->colorService->update($color, $request->validated())));
    }
}
