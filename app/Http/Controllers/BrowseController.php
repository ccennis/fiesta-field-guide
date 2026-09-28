<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrowseRequest;
use App\Http\Resources\BrowseColorResource;
use App\Http\Resources\BrowseProductResource;
use App\Services\BrowseService;
use Illuminate\Http\JsonResponse;

class BrowseController extends Controller
{
    public function __construct(
        private BrowseService $browseService,
    ) {}

    public function index(BrowseRequest $request): JsonResponse
    {
        $results = $this->browseService->search($request->validated(), $request->user());

        return $this->success([
            'colors' => BrowseColorResource::collection($results['colors']),
            'products' => BrowseProductResource::collection($results['products']),
        ]);
    }
}
