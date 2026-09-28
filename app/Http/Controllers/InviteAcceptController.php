<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The public side of an invite link: checking it, and joining with it.
 */
class InviteAcceptController extends Controller
{
    public function __construct(
        private InvitationService $invitations,
    ) {}

    public function show(string $token): JsonResponse
    {
        if ($this->invitations->find($token) === null) {
            return $this->error('This invite link has expired or has already been used.', 404);
        }

        return $this->success(['owner_name' => User::owner()?->name], 'This invite link is ready to use.');
    }

    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        try {
            $user = $this->invitations->accept($token, $request->validated(), $request);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->created(new UserResource($user));
    }
}
