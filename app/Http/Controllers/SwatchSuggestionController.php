<?php

namespace App\Http\Controllers;

use App\Http\Resources\SwatchSuggestionResource;
use App\Models\SwatchSuggestion;
use App\Services\Swatches\SwatchSuggestionService;
use Illuminate\Http\JsonResponse;

class SwatchSuggestionController extends Controller
{
    public function __construct(
        private SwatchSuggestionService $swatches,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(SwatchSuggestionResource::collection($this->swatches->pending()));
    }

    public function accept(SwatchSuggestion $swatchSuggestion): JsonResponse
    {
        return $this->success(new SwatchSuggestionResource($this->swatches->accept($swatchSuggestion)));
    }

    public function dismiss(SwatchSuggestion $swatchSuggestion): JsonResponse
    {
        return $this->success(new SwatchSuggestionResource($this->swatches->dismiss($swatchSuggestion)));
    }
}
