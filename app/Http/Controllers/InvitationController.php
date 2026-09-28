<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class InvitationController extends Controller
{
    public function __construct(
        private InvitationService $invitations,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(InvitationResource::collection($this->invitations->open()));
    }

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        try {
            $created = $this->invitations->create($request->user(), $request->validated('note'), $request->validated('email'));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $message = $created['invitation']->sent_to ? "Emailed the invite to {$created['invitation']->sent_to}." : 'Created';

        return $this->created(new InvitationResource($created['invitation'], $created['link']), $message);
    }

    public function destroy(Invitation $invitation): JsonResponse
    {
        $this->invitations->revoke($invitation);

        return $this->noContent();
    }
}
