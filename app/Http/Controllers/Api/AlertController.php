<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PriceAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PriceAlert::where('is_active', true)->latest();

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        return response()->json($query->paginate(20));
    }

    public function dashboard(Request $request): JsonResponse
    {
        $state = $request->query('state');

        $postsQuery = Post::query();
        if ($state) {
            $postsQuery->where('state', $state);
        }

        $totalPosts   = $postsQuery->count();
        $totalUsers   = \App\Models\User::count();
        $avgPrice     = $postsQuery->avg('price');
        $alerts       = PriceAlert::where('is_active', true)
                            ->when($state, fn($q) => $q->where('state', $state))
                            ->latest()->take(5)->get();

        $topProducts = Post::selectRaw('product, COUNT(*) as report_count, AVG(price) as avg_price')
            ->when($state, fn($q) => $q->where('state', $state))
            ->groupBy('product')
            ->orderByDesc('report_count')
            ->take(5)
            ->get();

        return response()->json([
            'total_posts'   => $totalPosts,
            'total_users'   => $totalUsers,
            'avg_price'     => round($avgPrice ?? 0, 2),
            'alerts'        => $alerts,
            'top_products'  => $topProducts,
        ]);
    }
}
