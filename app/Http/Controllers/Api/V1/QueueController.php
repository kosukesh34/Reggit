<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\QueueEntry;
use App\Models\ShopSetting;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QueueController extends BaseApiController
{
    public function __construct(private UserService $userService)
    {
    }

    private function getQueueStats(int $projectId, ?int $beforeQueueNumber = null): array
    {
        $base = QueueEntry::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->where('status', 'waiting');

        $waitingCount = (clone $base)->count();

        $aheadCount = null;
        if ($beforeQueueNumber !== null) {
            $aheadCount = (clone $base)->where('queue_number', '<', $beforeQueueNumber)->count();
        }

        $avgMinutes = ShopSetting::getQueueAvgServeMinutes($projectId);
        $estimatedMinutes = $beforeQueueNumber !== null
            ? $aheadCount * $avgMinutes
            : $waitingCount * $avgMinutes;

        return [
            'waiting_count' => $waitingCount,
            'ahead_count' => $aheadCount,
            'estimated_wait_minutes' => $estimatedMinutes,
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'party_size' => 'required|integer|min:1|max:20',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;

            $projectId = $member ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1);
            $maxNumber = QueueEntry::where('project_id', $projectId)->whereDate('created_at', today())->max('queue_number') ?? 0;
            $nextNumber = $maxNumber + 1;

            $entry = QueueEntry::create([
                'project_id' => $projectId,
                'member_id' => $member?->id,
                'guest_name' => $validated['guest_name'] ?? ($user->name ?? null),
                'guest_phone' => $validated['guest_phone'] ?? ($user->phone ?? null),
                'party_size' => $validated['party_size'],
                'status' => 'waiting',
                'queue_number' => $nextNumber,
            ]);

            $stats = $this->getQueueStats($projectId, $entry->queue_number);
            $entry->refresh();

            return $this->success([
                'queue_entry' => [
                    'id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'status' => $entry->status,
                    'party_size' => $entry->party_size,
                    'created_at' => $entry->created_at->toIso8601String(),
                    'updated_at' => $entry->updated_at->toIso8601String(),
                    'waiting_count' => $stats['waiting_count'],
                    'ahead_count' => $stats['ahead_count'],
                    'estimated_wait_minutes' => $stats['estimated_wait_minutes'],
                ],
                'updated_at' => now()->toIso8601String(),
            ], '順番待ちに登録しました', 201);
        } catch (\Exception $e) {
            Log::error('Queue store error: ' . $e->getMessage());
            return $this->error('順番待ちの登録に失敗しました', 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;

            $entry = null;
            if ($member) {
                $entry = QueueEntry::where('member_id', $member->id)
                    ->whereIn('status', ['waiting', 'called'])
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            $projectId = $entry ? $entry->project_id : ($member ? $member->project_id : (AdminContext::getCurrentProjectId() ?? 1));
            $stats = $this->getQueueStats($projectId, $entry ? $entry->queue_number : null);

            if (!$entry) {
                return $this->success([
                    'queue_entry' => null,
                    'message' => '待ち番号はありません',
                    'waiting_count' => $stats['waiting_count'],
                    'estimated_wait_minutes' => $stats['estimated_wait_minutes'],
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            return $this->success([
                'queue_entry' => [
                    'id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'status' => $entry->status,
                    'party_size' => $entry->party_size,
                    'created_at' => $entry->created_at->toIso8601String(),
                    'updated_at' => $entry->updated_at->toIso8601String(),
                    'called_at' => $entry->called_at?->toIso8601String(),
                    'waiting_count' => $stats['waiting_count'],
                    'ahead_count' => $stats['ahead_count'],
                    'estimated_wait_minutes' => $stats['estimated_wait_minutes'],
                ],
                'updated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Queue me error: ' . $e->getMessage());
            return $this->error('取得に失敗しました', 500);
        }
    }
}
