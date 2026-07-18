<?php

namespace App\Support;

/**
 * クーポン通知・LINE Flex 用の LIFF / Web リンク（クーポンタブを開く）。
 */
class CouponLiffUrlBuilder
{
    public static function buildCouponsTabUrl(int $projectId, string $projectKey): string
    {
        return LiffTabUrlBuilder::build($projectId, $projectKey, 'coupons');
    }
}
