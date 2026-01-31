<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use App\Services\MemberService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    private MemberService $memberService;
    private UserService $userService;

    public function __construct(MemberService $memberService, UserService $userService)
    {
        $this->memberService = $memberService;
        $this->userService = $userService;
    }

    public function me(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $data = $this->memberService->getMemberData($user);
            
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Failed to get member data: ' . $e->getMessage());
            return response()->json([
                'message' => '認証が必要です',
                'is_member' => false,
            ], 401);
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $data = $this->memberService->updateProfile($user, $validated);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Member profile update error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $this->memberService->registerMember($user, $validated);
            $user->refresh();
            $data = $this->memberService->getMemberData($user);

            return response()->json($data, 201);
        } catch (\Exception $e) {
            Log::error('Member registration error: ' . $e->getMessage());

            if ($e->getMessage() === '既に会員登録済みです') {
                try {
                    $user = $this->userService->getOrCreateUser($request);
                    $data = $this->memberService->getMemberData($user);
                    return response()->json($data, 200);
                } catch (\Exception $innerException) {
                    return response()->json([
                        'error' => $e->getMessage(),
                    ], 400);
                }
            }

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function getPoints(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
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
        } catch (\Exception $e) {
            Log::error('Failed to get points: ' . $e->getMessage());
            return response()->json([
                'error' => 'ポイント情報の取得に失敗しました',
            ], 500);
        }
    }

    public function addPoints(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;

            if (!$member) {
                return response()->json([
                    'error' => '会員登録が必要です',
                ], 404);
            }

            $member->addPoints($validated['points'], $validated['description'] ?? null);

            return response()->json([
                'message' => 'ポイントが追加されました',
                'points' => $member->fresh()->points,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add points: ' . $e->getMessage());
            return response()->json([
                'error' => 'ポイントの追加に失敗しました',
            ], 500);
        }
    }
}

