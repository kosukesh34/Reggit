<?php

namespace App\Services\Smaregi;

use App\Domain\Settings\Repositories\RankSettings;
use App\Models\Project;
use App\Models\SmaregiRankSettingsSync;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmaregiRankSettingsRecoveryService
{
    public function __construct(
        private RankSettings $settings,
        private SmaregiCustomerRankSyncService $masters,
        private SmaregiCustomerRankAssignmentService $members,
        private SmaregiApiService $api,
    ) {}

    public function record(int $projectId): void
    {
        SmaregiRankSettingsSync::upsert([[
            'project_id' => $projectId, 'request_token' => (string) Str::uuid(),
            'retry_at' => now(), 'delivered_at' => null,
        ]], ['project_id'], ['request_token', 'retry_at', 'delivered_at']);
    }

    public function retryPending(int $projectId): bool
    {
        $lease = (string) Str::uuid();
        $request = DB::transaction(function () use ($projectId, $lease): ?SmaregiRankSettingsSync {
            $row = SmaregiRankSettingsSync::whereKey($projectId)->whereNull('delivered_at')
                ->where('retry_at', '<=', now())
                ->where(fn ($q) => $q->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
                ->lockForUpdate()->first();
            if ($row !== null) {
                $row->update(['lease_token' => $lease, 'leased_until' => now()->addMinutes(5)]);
            }

            return $row;
        });
        if ($request === null) {
            return true;
        }
        $owned = fn () => SmaregiRankSettingsSync::whereKey($projectId)
            ->where('request_token', $request->request_token)->where('lease_token', $lease);
        $previous = AdminContext::getCurrentProject();
        $attempted = false;
        try {
            AdminContext::setCurrentProject(Project::findOrFail($projectId));
            if (! $this->masters->hasSmaregiContract($projectId)) {
                $owned()->update(['retry_at' => now()->addHour()]);

                return true;
            }
            $contract = trim((string) $this->api->getContractIdForProject($projectId));
            $rows = $this->settings->getMemberRankSettings($projectId);
            $attempted = true;
            $ids = $this->masters->sync($projectId, $rows);
            $current = DB::transaction(function () use ($projectId, $owned, $contract, $ids): bool {
                Project::whereKey($projectId)->lockForUpdate()->firstOrFail();
                if ($owned()->lockForUpdate()->first() === null
                    || trim((string) $this->api->getContractIdForProject($projectId)) !== $contract) {
                    return false;
                }
                $rows = $this->settings->getMemberRankSettings($projectId);
                foreach ($rows as $index => $row) {
                    if (isset($ids[$row['key']]) && $ids[$row['key']] !== '') {
                        $rows[$index]['smaregi_rank_id'] = $ids[$row['key']];
                    }
                }
                $this->settings->setMemberRankSettings($rows, $projectId);

                return true;
            });
            if (! $current) {
                $owned()->update(['retry_at' => now()->addMinute()]);

                return false;
            }
            $this->members->syncPointRatesForProject($projectId, immediate: false);
            if (trim((string) $this->api->getContractIdForProject($projectId)) !== $contract) {
                $owned()->update(['retry_at' => now()->addMinute()]);

                return false;
            }
            if ($owned()->update(['delivered_at' => now()]) === 0) {
                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            report($exception);
            $owned()->update(['retry_at' => now()->addMinute()]);

            return false;
        } finally {
            try {
                if ($attempted && ! $owned()->exists()) {
                    // Rotate the generation so an active newer worker cannot acknowledge stale remote effects.
                    SmaregiRankSettingsSync::whereKey($projectId)->update([
                        'request_token' => (string) Str::uuid(), 'delivered_at' => null, 'retry_at' => now(),
                    ]);
                }
                SmaregiRankSettingsSync::whereKey($projectId)->where('lease_token', $lease)
                    ->update(['lease_token' => null, 'leased_until' => null]);
            } finally {
                AdminContext::setCurrentProject($previous);
            }
        }
    }
}
