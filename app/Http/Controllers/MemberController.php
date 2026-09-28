<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemberResource;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class MemberController extends Controller
{
    public function __construct(
        private MemberService $members,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(MemberResource::collection($this->members->list()));
    }

    public function disable(User $user): JsonResponse
    {
        try {
            return $this->success(new MemberResource($this->members->disable($user)));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function enable(User $user): JsonResponse
    {
        try {
            return $this->success(new MemberResource($this->members->enable($user)));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
