<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login($request, $request->validated());

        if ($user === null) {
            return $this->error('That email and password do not match.', 422);
        }

        return $this->success(new UserResource($user));
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->created(
            new UserResource($this->authService->register($request, $request->validated())),
            'Check your email for a link to confirm your address.'
        );
    }

    /**
     * Opened from the confirmation email, so it answers with a page rather
     * than JSON.
     */
    public function verifyEmail(int $id, string $hash): RedirectResponse
    {
        return redirect($this->authService->verifyEmail($id, $hash) ? '/?verified=1' : '/?verified=0');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $this->authService->resendVerification($request->user());

        return $this->success(null, 'Sent. Check your email.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return $this->success(null, 'Signed out');
    }
}
