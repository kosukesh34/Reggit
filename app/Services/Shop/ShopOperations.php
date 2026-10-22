<?php

namespace App\Services\Shop;

use App\Domain\Settings\Repositories\SettingValueReader;
use Carbon\CarbonImmutable;

/**
 * 配送ECの運用設定（特定商取引法の表記・送料無料・お届け日時・登録番号・配送業者）。
 */
class ShopOperations
{
    /** 特定商取引法に基づく表記の項目。すべて必須。 */
    public const LEGAL_FIELDS = [
        'seller' => '販売業者',
        'representative' => '運営統括責任者',
        'address' => '所在地',
        'phone' => '電話番号',
        'email' => 'メールアドレス',
        'extra_fees' => '商品代金以外の必要料金',
        'payment' => 'お支払い方法・お支払い時期',
        'delivery' => '商品の引渡し時期',
        'returns' => '返品・交換・キャンセル',
    ];

    public const CARRIERS = [
        'yamato' => 'ヤマト運輸',
        'sagawa' => '佐川急便',
        'japanpost' => '日本郵便',
        'seino' => '西濃運輸',
        'other' => 'その他',
    ];

    // ponytail: 各社の公開お問い合わせURL。URL形式が変わったら番号表示のみになるよう、ここだけを直す。
    private const TRACKING_URLS = [
        'yamato' => 'https://jizen.kuronekoyamato.co.jp/jizen/servlet/crjz.b.NQ0010?id=',
        'sagawa' => 'https://k2k.sagawa-exp.co.jp/p/web/okurijosearch.do?okurijoNo=',
        'japanpost' => 'https://trackings.post.japanpost.jp/services/srv/search/direct?locale=ja&reqCodeNo1=',
        'seino' => 'https://track.seino.co.jp/cgi-bin/gnpquery.pgm?GNPNO1=',
    ];

    public function __construct(private SettingValueReader $settings) {}

    /** @return array<string, string> */
    public function legalNotice(int $projectId): array
    {
        $saved = $this->settings->getValue('shop_legal_notice', [], $projectId);
        $saved = is_array($saved) ? $saved : [];

        $notice = [];
        foreach (array_keys(self::LEGAL_FIELDS) as $key) {
            $notice[$key] = trim((string) ($saved[$key] ?? ''));
        }

        return $notice;
    }

    public function isLegalNoticeComplete(int $projectId): bool
    {
        return ! in_array('', $this->legalNotice($projectId), true);
    }

    public function freeShippingThreshold(int $projectId): ?int
    {
        $value = $this->settings->getValue('shop_free_shipping_threshold', null, $projectId);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @return array{enabled: bool, earliest: string, latest: string, time_slots: list<string>} */
    public function deliveryOptions(int $projectId): array
    {
        $lead = max(1, (int) $this->settings->getValue('shop_delivery_lead_days', 3, $projectId));
        $range = max(1, (int) $this->settings->getValue('shop_delivery_range_days', 14, $projectId));
        $slots = $this->settings->getValue('shop_delivery_time_slots', [], $projectId);
        $today = CarbonImmutable::today();

        return [
            'enabled' => (bool) $this->settings->getValue('shop_delivery_date_enabled', false, $projectId),
            'earliest' => $today->addDays($lead)->toDateString(),
            'latest' => $today->addDays($lead + $range - 1)->toDateString(),
            'time_slots' => array_values(array_filter(array_map('strval', is_array($slots) ? $slots : []), fn (string $slot): bool => $slot !== '')),
        ];
    }

    public function invoiceRegistrationNumber(int $projectId): string
    {
        return trim((string) $this->settings->getValue('shop_invoice_registration_number', '', $projectId));
    }

    public static function carrierLabel(?string $carrier): string
    {
        return self::CARRIERS[$carrier ?? ''] ?? '';
    }

    public static function trackingUrl(?string $carrier, ?string $number): ?string
    {
        $number = preg_replace('/[^0-9A-Za-z]/', '', (string) $number);
        $base = self::TRACKING_URLS[$carrier ?? ''] ?? null;

        return $base !== null && $number !== '' ? $base.$number : null;
    }
}
