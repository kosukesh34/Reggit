<?php

namespace App\Enums;

use App\Util\EnumBase;

/**
 * スマレジ POS Webhook の action。
 *
 * イベントにより利用可能な値が異なる。
 * - pos:transactions: created / edited / canceled / disposed / bulk-update / bulk-deleted
 * - pos:customers: created / edited / deleted / bulk-update / bulk-deleted
 * - pos:customer-points: edited / bulk-update
 *
 * @see https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/postwebhooktransactions/
 * @see https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/postwebhookcustomers/
 * @see https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/postwebhookcustomerspoints/
 */
class SmaregiWebhookAction extends EnumBase
{
    protected static array $values = [
        'created' => '登録',
        'edited' => '更新',
        'deleted' => '削除',
        'canceled' => '取消',
        'disposed' => '返品取消',
        'bulk-update' => '一括更新',
        'bulk-deleted' => '一括削除',
    ];

    public const CREATED = 'created';

    public const EDITED = 'edited';

    public const DELETED = 'deleted';

    public const CANCELED = 'canceled';

    public const DISPOSED = 'disposed';

    public const BULK_UPDATE = 'bulk-update';

    public const BULK_DELETED = 'bulk-deleted';

    public static function isCustomerDeletedAction(?string $action): bool
    {
        if ($action === null || $action === '') {
            return false;
        }

        $normalized = strtolower($action);

        return $normalized === self::DELETED || $normalized === self::BULK_DELETED;
    }
}
