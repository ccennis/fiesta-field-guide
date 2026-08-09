<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success($this->postService->all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $post = $this->postService->create($validated);

        return $this->created($post);
    }

    public function show(Post $post): JsonResponse
    {
        return $this->success($post);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
        ]);

        $post = $this->postService->update($post, $validated);

        return $this->success($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->postService->delete($post);

        return $this->noContent();
    }
}
