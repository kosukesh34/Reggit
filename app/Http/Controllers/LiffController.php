<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Member;

class LiffController extends Controller
{
    public function index(Request $request)
    {
        // メインのindex.blade.phpを表示（LINEアプリでも同じ画面を使用）
        return view('index');
    }

    public function products(Request $request)
    {
        $category = $request->query('category');
        $products = Product::query();

        if ($category && $category !== 'all') {
            $products->where('category', $category);
        }

        $products = $products->where('is_available', true)->orderBy('category')->orderBy('name')->get();

        return response()->json($products);
    }

    public function memberCard(Request $request)
    {
        $lineUserId = $request->query('userId') ?? $request->header('X-Line-User-Id');
        
        if (!$lineUserId) {
            return response()->json(['error' => 'User ID required'], 400);
        }

        $user = User::where('line_user_id', $lineUserId)->first();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $member = $user->member;
        
        if (!$member) {
            return response()->json([
                'is_member' => false,
                'message' => '会員登録がまだです'
            ]);
        }

        $recentTransactions = $member->pointTransactions()
            ->with('order')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'is_member' => true,
            'member' => $member,
            'user' => $user,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function orders(Request $request)
    {
        $lineUserId = $request->query('userId') ?? $request->header('X-Line-User-Id');
        
        if (!$lineUserId) {
            return response()->json(['error' => 'User ID required'], 400);
        }

        $user = User::where('line_user_id', $lineUserId)->first();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $orders = $user->orders()
            ->with('items.product')
            ->latest()
            ->take(20)
            ->get();

        return response()->json($orders);
    }
}

