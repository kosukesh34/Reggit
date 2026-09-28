<?php

namespace App\Models\Scopes;

use App\Util\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreAccessScope implements Scope
{
    public const TRANSACTIONS = ['orders', 'reservations', 'queue_entries', 'member_visits', 'point_transactions', 'member_stamp_transactions', 'coupon_redemptions', 'store_app_credentials'];

    public function apply(Builder $builder, Model $model): void
    {
        $context = app(StoreContext::class);
        if (! $context->bound || $context->visibleStoreIds === null) {
            return;
        }
        $table = $model->getTable();
        $ids = $context->visibleStoreIds;
        if (in_array($table, self::TRANSACTIONS, true)) {
            $builder->whereIn($table.'.store_id', $ids);
        } elseif ($table === 'order_items') {
            $builder->whereHas('order', fn ($q) => $q->whereIn('orders.store_id', $ids));
        } elseif ($table === 'member_visit_items') {
            $builder->whereHas('visit', fn ($q) => $q->whereIn('member_visits.store_id', $ids));
        } elseif ($context->admin !== null && $table === 'announcement_reads') {
            $builder->whereHas('announcement');
            $builder->whereHas('user');
        } elseif ($context->admin !== null && ($context->projectStoreCount > 1 || $ids === []) && in_array($table, ['members', 'users'], true)) {
            $builder->where(function ($access) use ($table, $ids) {
                $access->whereExists(function ($query) use ($table, $ids) {
                    $query->selectRaw('1')->from('member_stores');
                    if ($table === 'users') {
                        $query->join('members as store_member', 'store_member.id', '=', 'member_stores.member_id')
                            ->whereColumn('store_member.user_id', 'users.id')->whereNull('store_member.deleted_at');
                    } else {
                        $query->whereColumn('member_stores.member_id', 'members.id');
                    }
                    $query->whereColumn('member_stores.project_id', $table.'.project_id')->whereIn('member_stores.store_id', $ids);
                });
                if ($table === 'users') {
                    foreach (['orders', 'reservations', 'queue_entries'] as $transactionTable) {
                        $access->orWhereExists(function ($query) use ($transactionTable, $ids) {
                            $query->selectRaw('1')->from($transactionTable)
                                ->whereColumn($transactionTable.'.user_id', 'users.id')
                                ->whereColumn($transactionTable.'.project_id', 'users.project_id')
                                ->whereIn($transactionTable.'.store_id', $ids);
                        });
                    }
                }
            });
        } elseif ($context->admin !== null && $table === 'announcements') {
            $builder->where(function ($query) use ($ids, $context) {
                $query->whereRaw('1 = 0');
                if ($context->projectStoreCount === 1) {
                    $query->orWhereNull('announcements.store_ids');
                }
                foreach ($ids as $id) {
                    $query->orWhereJsonContains('announcements.store_ids', (int) $id);
                }
            });
        }
    }
}
