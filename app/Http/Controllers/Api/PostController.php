<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::with(['user:id,name,avatar_url,state', 'tags:id,name,color'])
            ->withCount(['likes', 'comments', 'confirms', 'denies'])
            // Exclude individually hidden posts
            ->where('is_hidden', false)
            // Exclude posts from shadow-banned users
            ->whereHas('user', fn ($q) => $q->where('is_shadow_banned', false));

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('product', 'like', '%' . $request->search . '%');
        }

        $posts = $query->latest()->paginate(15);

        return response()->json($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product'     => 'required|string|max:150',
            'price'       => 'required|numeric|min:0',
            'category'    => 'required|string',
            'state'       => 'required|string',
            'market'      => 'required|string|max:150',
            'location'    => 'nullable|string',
            'lat'         => 'nullable|numeric',
            'lng'         => 'nullable|numeric',
            'gps_accuracy'=> 'nullable|integer',
            'description' => 'nullable|string|max:1000',
            'image_url'   => 'nullable|url',
            'tags'        => 'nullable|array',
            'tags.*'      => 'integer|exists:tags,id',
        ]);

        $post = $request->user()->posts()->create($request->only([
            'product', 'price', 'category', 'state', 'market',
            'location', 'lat', 'lng', 'gps_accuracy', 'description', 'image_url',
        ]));

        if ($request->filled('tags')) {
            $post->tags()->sync($request->tags);
        }

        return response()->json($post->load(['user:id,name,avatar_url', 'tags:id,name,color']), 201);
    }

    public function show(Post $post): JsonResponse
    {
        $post->load(['user:id,name,avatar_url,state', 'tags:id,name,color'])
             ->loadCount(['likes', 'comments', 'confirms', 'denies']);

        return response()->json($post);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'tags'   => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
        ]);

        $post->update($request->only([
            'product', 'price', 'category', 'state', 'market',
            'location', 'lat', 'lng', 'description', 'image_url',
        ]));

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags ?? []);
        }

        return response()->json($post);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $post->delete();
        return response()->json(['message' => 'Post deleted.']);
    }

    public function like(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()->id;
        $existing = PostLike::where('post_id', $post->id)->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            PostLike::create(['post_id' => $post->id, 'user_id' => $userId]);
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'likes_count' => $post->likes()->count()]);
    }

    public function vote(Request $request, Post $post): JsonResponse
    {
        $request->validate(['type' => 'nullable|in:confirm,deny,remove']);
        $userId = $request->user()->id;

        if (is_null($request->type) || $request->type === 'remove') {
            PostVote::where('post_id', $post->id)->where('user_id', $userId)->delete();
        } else {
            PostVote::updateOrCreate(
                ['post_id' => $post->id, 'user_id' => $userId],
                ['type' => $request->type]
            );
        }

        return response()->json([
            'confirms' => $post->confirms()->count(),
            'denies'   => $post->denies()->count(),
        ]);
    }
}
