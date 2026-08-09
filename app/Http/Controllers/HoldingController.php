<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHoldingRequest;
use App\Http\Requests\UpdateHoldingRequest;
use App\Http\Resources\HoldingResource;
use App\Models\Holding;
use App\Services\HoldingService;
use Illuminate\Http\JsonResponse;

class HoldingController extends Controller
{
    public function __construct(
        private HoldingService $holdingService,
    ) {}

    public function store(StoreHoldingRequest $request): JsonResponse
    {
        return $this->created(new HoldingResource($this->holdingService->create($request->validated())));
    }

    public function update(UpdateHoldingRequest $request, Holding $holding): JsonResponse
    {
        return $this->success(new HoldingResource($this->holdingService->update($holding, $request->validated())));
    }
}
