<?php

namespace App\Models\Concerns;

use App\Models\Member;
use App\Models\Order;
use App\Models\Scopes\ProjectScope;
use App\Models\Scopes\StoreAccessScope;
use App\Models\Store;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;

trait BelongsToProject
{
    protected static function bootBelongsToProject(): void
    {
        static::addGlobalScope(new ProjectScope);
        static::addGlobalScope(new StoreAccessScope);

        static::creating(function ($model) {
            if ($model->project_id !== null) {
                return;
            }
            $projectId = AdminContext::getCurrentProjectId();
            if ($projectId !== null) {
                $model->project_id = $projectId;
            }
        });
        static::creating(function ($model) {
            if (in_array($model->getTable(), StoreAccessScope::TRANSACTIONS, true)) {
                $context = app(StoreContext::class);
                $projectId = (int) $model->project_id;
                if ($model->store_id === null && $model->order_id !== null) {
                    $model->store_id = Order::withoutGlobalScopes()->where('project_id', $projectId)->whereKey($model->order_id)->value('store_id');
                }
                $commonLedger = in_array($model->getTable(), ['point_transactions', 'member_stamp_transactions'], true);
                if ($commonLedger && Store::where('project_id', $projectId)->count() > 1 && $model->store_id === null && $context->store === null && ($context->admin === null || $context->admin->isSystemAdmin() || $context->admin->isProjectAdmin())) {
                    $model->origin = 'headquarters';

                    return;
                }
                $model->store_id ??= $context->resolveForWrite($projectId);
                if ($commonLedger) {
                    $model->origin = 'store';
                }
                $context->assertStore($projectId, (int) $model->store_id);
            }
        });
        static::created(function ($model) {
            if ($model->getTable() === 'members') {
                $context = app(StoreContext::class);
                $storeId = $context->store?->id;
                if ($storeId === null && ! $context->bound) {
                    $stores = Store::where('project_id', $model->project_id)->limit(2)->pluck('id');
                    $storeId = $stores->count() === 1 ? $stores->first() : null;
                }
                if ($storeId !== null) {
                    $model->stores()->syncWithoutDetaching([$storeId => ['project_id' => $model->project_id]]);
                }
            } elseif ($model->store_id !== null) {
                $memberId = $model->member_id;
                if ($memberId === null && $model->user_id !== null) {
                    $memberId = Member::withoutGlobalScopes()->where('project_id', $model->project_id)->where('user_id', $model->user_id)->whereNull('deleted_at')->value('id');
                }
                if ($memberId !== null) {
                    DB::table('member_stores')->upsert(
                        [['member_id' => $memberId, 'store_id' => $model->store_id, 'project_id' => $model->project_id, 'created_at' => now(), 'updated_at' => now()]],
                        ['member_id', 'store_id'], ['updated_at'],
                    );
                }
            }
        });
    }
}
