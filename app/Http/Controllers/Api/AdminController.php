<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PriceAlert;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // ─── Overview Stats ────────────────────────────────────────────────────────
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_users'    => User::count(),
            'total_posts'    => Post::count(),
            'total_comments' => Comment::count(),
            'flagged_posts'       => Post::where('is_flagged', true)->count(),
            'hidden_posts'        => Post::where('is_hidden', true)->count(),
            'banned_users'        => User::where('is_banned', true)->count(),
            'shadow_banned_users' => User::where('is_shadow_banned', true)->count(),
            'active_alerts'  => PriceAlert::where('is_active', true)->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_posts_today' => Post::whereDate('created_at', today())->count(),
            'top_states'     => Post::selectRaw('state, COUNT(*) as count')
                                    ->groupBy('state')->orderByDesc('count')->take(5)->get(),
            'top_categories' => Post::selectRaw('category, COUNT(*) as count')
                                    ->groupBy('category')->orderByDesc('count')->take(5)->get(),
            'recent_users'   => User::latest()->take(5)->get(['id','name','email','state','role','is_banned','created_at']),
            'recent_posts'   => Post::with('user:id,name')->latest()->take(5)
                                    ->get(['id','product','price','state','category','is_flagged','is_hidden','created_at','user_id']),
        ]);
    }

    // ─── User Management ───────────────────────────────────────────────────────
    public function listUsers(Request $request): JsonResponse
    {
        $query = User::withCount('posts');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%$q%")
                   ->orWhere('email', 'like', "%$q%")
                   ->orWhere('username', 'like', "%$q%")
                   ->orWhere('phone', 'like', "%$q%");
            });
        }
        if ($request->filled('role'))      $query->where('role', $request->role);
        if ($request->filled('is_banned')) $query->where('is_banned', (bool) $request->is_banned);
        if ($request->filled('state'))     $query->where('state', $request->state);

        $users = $query->latest()->paginate(20);
        return response()->json($users);
    }

    public function showUser(User $user): JsonResponse
    {
        $user->loadCount(['posts', 'comments', 'likes']);
        return response()->json(['user' => $user]);
    }

    public function banUser(Request $request, User $user): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot ban another admin.'], 403);
        }
        $user->update(['is_banned' => true, 'ban_reason' => $request->reason]);
        return response()->json(['message' => "User {$user->name} has been banned.", 'user' => $user]);
    }

    public function unbanUser(User $user): JsonResponse
    {
        $user->update(['is_banned' => false, 'ban_reason' => null]);
        return response()->json(['message' => "User {$user->name} has been unbanned.", 'user' => $user]);
    }

    public function promoteUser(User $user): JsonResponse
    {
        $user->update(['role' => 'admin']);
        return response()->json(['message' => "{$user->name} is now an admin.", 'user' => $user]);
    }

    public function demoteUser(User $user): JsonResponse
    {
        $user->update(['role' => 'user']);
        return response()->json(['message' => "{$user->name} has been demoted to user.", 'user' => $user]);
    }

    public function deleteUser(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot delete another admin.'], 403);
        }
        $user->tokens()->delete();
        $user->delete();
        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function editUser(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name'       => 'sometimes|string|min:2|max:100',
            'username'   => 'sometimes|string|max:60|unique:users,username,' . $user->id,
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'      => 'sometimes|nullable|string|max:20',
            'state'      => 'sometimes|nullable|string|max:60',
            'bio'        => 'sometimes|nullable|string|max:300',
            'occupation' => 'sometimes|nullable|string|max:150',
            'gender'     => 'sometimes|nullable|in:male,female,other,prefer_not_to_say',
            'dob'        => 'sometimes|nullable|date',
            'visibility' => 'sometimes|in:public,private',
            'language'   => 'sometimes|string|max:10',
            'avatar_url' => 'sometimes|nullable|string|max:500',
        ]);

        $user->update($request->only([
            'name', 'username', 'email', 'phone', 'state', 'bio',
            'occupation', 'gender', 'dob', 'visibility', 'language', 'avatar_url',
        ]));

        return response()->json(['message' => 'User updated.', 'user' => $user->fresh()]);
    }

    public function shadowBanUser(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot shadow-ban another admin.'], 403);
        }
        $user->update(['is_shadow_banned' => true]);
        return response()->json(['message' => "{$user->name}'s posts are now hidden from all feeds.", 'user' => $user]);
    }

    public function unshadowBanUser(User $user): JsonResponse
    {
        $user->update(['is_shadow_banned' => false]);
        return response()->json(['message' => "{$user->name}'s posts are visible again.", 'user' => $user]);
    }

    // ─── Post Management ───────────────────────────────────────────────────────
    public function listPosts(Request $request): JsonResponse
    {
        $query = Post::with(['user:id,name,avatar_url', 'tags:id,name,color'])
                     ->withCount(['likes', 'comments', 'confirms', 'denies']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('product', 'like', "%$q%")
                   ->orWhere('market', 'like', "%$q%");
            });
        }
        if ($request->filled('state'))      $query->where('state', $request->state);
        if ($request->filled('category'))   $query->where('category', $request->category);
        if ($request->filled('is_flagged')) $query->where('is_flagged', (bool) $request->is_flagged);
        if ($request->filled('is_hidden'))  $query->where('is_hidden',  (bool) $request->is_hidden);
        if ($request->filled('user_id'))    $query->where('user_id', $request->user_id);

        $posts = $query->latest()->paginate(20);
        return response()->json($posts);
    }

    public function flagPost(Post $post): JsonResponse
    {
        $post->update(['is_flagged' => true]);
        return response()->json(['message' => 'Post flagged for review.', 'post' => $post]);
    }

    public function unflagPost(Post $post): JsonResponse
    {
        $post->update(['is_flagged' => false]);
        return response()->json(['message' => 'Post unflagged.', 'post' => $post]);
    }

    public function deletePost(Post $post): JsonResponse
    {
        $post->delete();
        return response()->json(['message' => 'Post deleted by admin.']);
    }

    public function editPost(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'product'     => 'sometimes|string|max:150',
            'price'       => 'sometimes|numeric|min:0',
            'category'    => 'sometimes|string',
            'state'       => 'sometimes|string',
            'market'      => 'sometimes|string|max:150',
            'location'    => 'sometimes|nullable|string',
            'description' => 'sometimes|nullable|string|max:1000',
            'image_url'   => 'sometimes|nullable|string|max:500',
        ]);

        $post->update($request->only([
            'product', 'price', 'category', 'state', 'market', 'location', 'description', 'image_url',
        ]));

        return response()->json(['message' => 'Post updated by admin.', 'post' => $post->fresh()->load(['user:id,name', 'tags:id,name,color'])]);
    }

    public function hidePost(Request $request, Post $post): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $post->update(['is_hidden' => true, 'hide_reason' => $request->reason]);
        return response()->json(['message' => 'Post hidden from public feed.', 'post' => $post]);
    }

    public function unhidePost(Post $post): JsonResponse
    {
        $post->update(['is_hidden' => false, 'hide_reason' => null]);
        return response()->json(['message' => 'Post restored to public feed.', 'post' => $post]);
    }

    // ─── Comment Management ────────────────────────────────────────────────────
    public function listComments(Request $request): JsonResponse
    {
        $query = Comment::with(['user:id,name,email', 'post:id,product,state']);

        if ($request->filled('search')) {
            $query->where('body', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('post_id'))  $query->where('post_id',  $request->post_id);
        if ($request->filled('user_id'))  $query->where('user_id',  $request->user_id);

        return response()->json($query->latest()->paginate(20));
    }

    public function deleteComment(Comment $comment): JsonResponse
    {
        $comment->delete();
        return response()->json(['message' => 'Comment deleted by admin.']);
    }

    // ─── Price Alert Management ─────────────────────────────────────────────────
    public function listAlerts(Request $request): JsonResponse
    {
        $query = PriceAlert::latest();
        if ($request->filled('is_active')) $query->where('is_active', (bool) $request->is_active);
        if ($request->filled('state'))     $query->where('state', $request->state);
        return response()->json($query->paginate(20));
    }

    public function storeAlert(Request $request): JsonResponse
    {
        $request->validate([
            'product'   => 'required|string|max:150',
            'message'   => 'required|string|max:500',
            'state'     => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $alert = PriceAlert::create($request->only(['product', 'message', 'state', 'is_active']));
        return response()->json($alert, 201);
    }

    public function updateAlert(Request $request, PriceAlert $alert): JsonResponse
    {
        $request->validate([
            'product'   => 'sometimes|string|max:150',
            'message'   => 'sometimes|string|max:500',
            'state'     => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $alert->update($request->only(['product', 'message', 'state', 'is_active']));
        return response()->json($alert);
    }

    public function deleteAlert(PriceAlert $alert): JsonResponse
    {
        $alert->delete();
        return response()->json(['message' => 'Alert deleted.']);
    }

    // ─── Category Management ───────────────────────────────────────────────────
    public function listCategories(): JsonResponse
    {
        return response()->json(Category::orderBy('sort_order')->orderBy('name')->get());
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:categories,name',
            'emoji'       => 'nullable|string|max:8',
            'color'       => 'nullable|string|max:20',
            'description' => 'nullable|string|max:300',
            'is_active'   => 'boolean',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $category = Category::create($request->only(['name','emoji','color','description','is_active','sort_order']));
        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'name'        => 'sometimes|string|max:100|unique:categories,name,' . $category->id,
            'emoji'       => 'sometimes|nullable|string|max:8',
            'color'       => 'sometimes|nullable|string|max:20',
            'description' => 'sometimes|nullable|string|max:300',
            'is_active'   => 'sometimes|boolean',
            'sort_order'  => 'sometimes|integer|min:0',
        ]);

        $category->update($request->only(['name','emoji','color','description','is_active','sort_order']));
        return response()->json($category);
    }

    public function deleteCategory(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted.']);
    }

    // ─── Tag Management ────────────────────────────────────────────────────────
    public function listTags(): JsonResponse
    {
        return response()->json(Tag::orderBy('name')->get());
    }

    public function storeTag(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:60|unique:tags,name',
            'color'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $tag = Tag::create($request->only(['name','color','is_active']));
        return response()->json($tag, 201);
    }

    public function updateTag(Request $request, Tag $tag): JsonResponse
    {
        $request->validate([
            'name'      => 'sometimes|string|max:60|unique:tags,name,' . $tag->id,
            'color'     => 'sometimes|nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $tag->update($request->only(['name','color','is_active']));
        return response()->json($tag);
    }

    public function deleteTag(Tag $tag): JsonResponse
    {
        $tag->delete();
        return response()->json(['message' => 'Tag deleted.']);
    }
}
