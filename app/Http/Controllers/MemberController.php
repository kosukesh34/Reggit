<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function me(Request $request)
    {
        // テスト用: 認証がない場合はテストユーザーを使用
        $userId = $request->header('X-Line-User-Id');
        if ($userId) {
            $user = \App\Models\User::where('line_user_id', $userId)->first();
            if (!$user) {
                // テストユーザーを作成
                $user = \App\Models\User::create([
                    'line_user_id' => $userId,
                    'name' => 'Test User',
                ]);
            }
        } else {
            $user = $request->user();
        }

        if (!$user) {
            return response()->json([
                'message' => '認証が必要です',
                'is_member' => false,
            ], 401);
        }

        $member = $user->member;

        if (!$member) {
            return response()->json([
                'message' => '会員登録がまだです',
                'is_member' => false,
            ]);
        }

        return response()->json([
            'is_member' => true,
            'member' => $member->load('user'),
            'points' => $member->points,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'birthday' => 'nullable|date',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        // テスト用: 認証がない場合はテストユーザーを使用
        $userId = $request->header('X-Line-User-Id');
        if ($userId) {
            $user = \App\Models\User::where('line_user_id', $userId)->first();
            if (!$user) {
                // テストユーザーを作成
                $user = \App\Models\User::create([
                    'line_user_id' => $userId,
                    'name' => 'Test User',
                ]);
            }
        } else {
            $user = $request->user();
        }

        if (!$user) {
            return response()->json([
                'error' => '認証が必要です',
            ], 401);
        }

        if ($user->member) {
            return response()->json([
                'message' => '既に会員登録済みです',
                'member' => $user->member,
            ]);
        }

        // 会員番号を生成
        $memberNumber = 'MEM-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $member = Member::create([
            'user_id' => $user->id,
            'member_number' => $memberNumber,
            'points' => 0,
            'status' => 'active',
            'birthday' => $request->birthday,
            'address' => $request->address,
        ]);

        if ($request->phone) {
            $user->update(['phone' => $request->phone]);
        }

        return response()->json([
            'message' => '会員登録が完了しました',
            'member' => $member->load('user'),
        ], 201);
    }

    public function getPoints(Request $request)
    {
        $user = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json([
                'error' => '会員登録が必要です',
            ], 404);
        }

        $transactions = PointTransaction::where('member_id', $member->id)
            ->with('order')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'points' => $member->points,
            'transactions' => $transactions,
        ]);
    }

    public function addPoints(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json([
                'error' => '会員登録が必要です',
            ], 404);
        }

        $member->addPoints($request->points, $request->description);

        return response()->json([
            'message' => 'ポイントが追加されました',
            'points' => $member->fresh()->points,
        ]);
    }
}

