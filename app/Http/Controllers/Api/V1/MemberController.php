<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PointTransaction;
use App\Services\MemberService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MemberController extends BaseApiController
{
    public function __construct(
        private MemberService $memberService,
        private UserService $userService
    ) {
    }

    public function me(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $data = $this->memberService->getMemberData($user);
            return $this->success(['member' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get member data: ' . $e->getMessage());
            return $this->error('認証が必要です', 401);
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
            return $this->success(['member' => $data], '更新しました');
        } catch (\Exception $e) {
            Log::error('Member profile update error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
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

            return $this->success(['member' => $data], '会員登録が完了しました', 201);
        } catch (\Exception $e) {
            Log::error('Member registration error: ' . $e->getMessage());
            if ($e->getMessage() === '既に会員登録済みです') {
                try {
                    $user = $this->userService->getOrCreateUser($request);
                    $data = $this->memberService->getMemberData($user);
                    return $this->success(['member' => $data], '既に会員登録済みです');
                } catch (\Exception $innerException) {
                    return $this->error($e->getMessage(), 400);
                }
            }

            return $this->error($e->getMessage(), 400);
        }
    }

    public function getPoints(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;

            if ($member === null) {
                return $this->error('会員登録が必要です', 404);
            }

            $transactions = PointTransaction::where('member_id', $member->id)
                ->with('order')
                ->latest()
                ->take(20)
                ->get();

            return $this->success([
                'points' => $member->points,
                'transactions' => $transactions,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get points: ' . $e->getMessage());
            return $this->error('ポイント情報の取得に失敗しました', 500);
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

            if ($member === null) {
                return $this->error('会員登録が必要です', 404);
            }

            $member->addPoints($validated['points'], $validated['description'] ?? null);

            return $this->success([
                'points' => $member->fresh()->points,
            ], 'ポイントが追加されました');
        } catch (\Exception $e) {
            Log::error('Failed to add points: ' . $e->getMessage());
            return $this->error('ポイントの追加に失敗しました', 500);
        }
    }
}
