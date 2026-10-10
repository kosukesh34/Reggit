<?php

namespace App\Services\Smaregi;

use App\Domain\Settings\Repositories\RankSettings;
use App\Models\Project;
use App\Models\SmaregiRankSettingsSync;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Persist desired project settings until remote mapping and member requests are durable. */
class SmaregiRankSettingsSyncService
{
    public function __construct(
        private SmaregiCustomerRankSyncService $masters,
        private SmaregiCustomerRankAssignmentService $members,
        private RankSettings $settings,
    ) {}

    /** Call inside the same transaction that saves settings, before reading or writing settings. */
    public function requestSync(int $projectId): void
    {
        SmaregiRankSettingsSync::upsert([[
            'project_id' => $projectId,
            'request_token' => (string) Str::uuid(),
            'retry_at' => now(),
        ]], ['project_id'], ['request_token', 'retry_at']);
        DB::afterCommit(function () use ($projectId): void {
            try {
                $this->retryPending($projectId);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    public function hasPending(int $projectId): bool
    {
        return SmaregiRankSettingsSync::whereKey($projectId)->exists();
    }

    public function retryPending(int $projectId): bool
    {
        $leaseToken = (string) Str::uuid();
        $request = DB::transaction(function () use ($projectId, $leaseToken): ?SmaregiRankSettingsSync {
            $request = SmaregiRankSettingsSync::whereKey($projectId)->where('retry_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
                ->lockForUpdate()->first();
            if ($request === null) {
                return null;
            }
            $request->update(['leased_until' => now()->addMinutes(5), 'lease_token' => $leaseToken]);

            return $request;
        });
        if ($request === null) {
            return true;
        }
        $owned = fn () => SmaregiRankSettingsSync::whereKey($projectId)
            ->where('request_token', $request->request_token)->where('lease_token', $leaseToken);
        $previousProject = AdminContext::getCurrentProject();
        $attempted = false;
        $settled = false;
        try {
            AdminContext::setCurrentProject(Project::findOrFail($projectId));
            if ($this->settings->getValue('member_rank_settings', [], $projectId) === []) {
                $owned()->delete();

                return true;
            }
            if (! $this->masters->hasSmaregiContract($projectId)) {
                $owned()->update(['retry_at' => now()->addHour()]);

                return true;
            }
            $rows = $this->settings->getMemberRankSettings($projectId);
            $attempted = true;
            $rankIdsByKey = $this->masters->sync($projectId, $rows);
            $settled = DB::transaction(function () use ($projectId, $owned, $rows, $rankIdsByKey): bool {
                $current = $owned()->lockForUpdate()->first();
                if ($current === null) {
                    return false;
                }
                foreach ($rows as $index => $row) {
                    $key = (string) $row['key'];
                    if (! isset($rankIdsByKey[$key]) || $rankIdsByKey[$key] === '') {
                        throw new \RuntimeException('スマレジの会員ランクIDを取得できませんでした。');
                    }
                    $rows[$index]['smaregi_rank_id'] = $rankIdsByKey[$key];
                }
                $this->settings->setMemberRankSettings($rows, $projectId);
                // No HTTP callbacks: either every member intent and mapping commit, or none do.
                $this->members->requestPointRatesForProject($projectId);
                $current->delete();

                return true;
            }, 3);
        } catch (\Throwable $exception) {
            report($exception);
            $settled = $owned()->update(['retry_at' => now()->addMinute()]) > 0;

            return false;
        } finally {
            AdminContext::setCurrentProject($previousProject);
            SmaregiRankSettingsSync::whereKey($projectId)->where('lease_token', $leaseToken)
                ->update(['leased_until' => null, 'lease_token' => null]);
            if ($attempted && ! $settled) {
                // A stale external completion must not leave remote state without reconciliation.
                SmaregiRankSettingsSync::insertOrIgnore([
                    'project_id' => $projectId,
                    'request_token' => (string) Str::uuid(),
                    'retry_at' => now(),
                ]);
            }
        }

        return true;
    }
}
