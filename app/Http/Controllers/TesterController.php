<?php

namespace App\Http\Controllers;

use App\Http\Resources\TesterResource;
use App\Models\User;
use App\Services\TesterService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TesterController extends Controller
{
    public function __construct(
        private TesterService $testers,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(TesterResource::collection($this->testers->list()));
    }

    public function disable(User $user): JsonResponse
    {
        try {
            return $this->success(new TesterResource($this->testers->disable($user)));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function enable(User $user): JsonResponse
    {
        try {
            return $this->success(new TesterResource($this->testers->enable($user)));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
