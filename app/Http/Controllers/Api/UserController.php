<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount('posts');
        return response()->json(['user' => $user]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'sometimes|string|min:2|max:100',
            'username'   => 'sometimes|string|unique:users,username,' . $request->user()->id,
            'bio'        => 'sometimes|nullable|string|max:300',
            'state'      => 'sometimes|string',
            'occupation' => 'sometimes|nullable|string',
            'gender'     => 'sometimes|nullable|string',
            'dob'        => 'sometimes|nullable|date',
            'visibility' => 'sometimes|in:public,private',
            'language'   => 'sometimes|string|max:10',
            'avatar_url' => 'sometimes|nullable|string|max:500',
        ]);

        $request->user()->update($request->only([
            'name', 'username', 'bio', 'state', 'occupation',
            'gender', 'dob', 'visibility', 'language', 'avatar_url',
        ]));

        return response()->json(['user' => $request->user()->fresh()]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount('posts');
        return response()->json(['user' => $user->makeHidden(['email', 'phone', 'notification_settings', 'privacy_settings'])]);
    }

    public function posts(Request $request, User $user): JsonResponse
    {
        $posts = $user->posts()
            ->withCount(['likes', 'comments', 'confirms', 'denies'])
            ->where('is_hidden', false)   // respect per-post hide
            ->latest()
            ->paginate(15);

        return response()->json($posts);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();
        return response()->json(['message' => 'Account deleted successfully.']);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'notification_settings' => 'sometimes|array',
            'privacy_settings'      => 'sometimes|array',
        ]);

        $request->user()->update($request->only(['notification_settings', 'privacy_settings']));

        return response()->json(['message' => 'Settings updated.']);
    }
}
