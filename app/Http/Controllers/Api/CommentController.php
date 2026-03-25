<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Post $post): JsonResponse
    {
        $comments = $post->comments()
            ->with('user:id,name,avatar_url')
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    public function store(Request $request, Post $post): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:1000']);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->body,
        ]);

        return response()->json($comment->load('user:id,name,avatar_url'), 201);
    }

    public function destroy(Request $request, Post $post, Comment $comment): JsonResponse
    {
        if ($request->user()->id !== $comment->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $comment->delete();
        return response()->json(['message' => 'Comment deleted.']);
    }
}
