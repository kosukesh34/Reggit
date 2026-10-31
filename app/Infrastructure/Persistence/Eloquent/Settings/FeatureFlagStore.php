<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Domain\Settings\ShopSettingKeys;

/** 機能フラグの設定。 */
class FeatureFlagStore
{
    /** config/native_app.php を反映した機能フラグ表示名 */
    public static function featureFlagLabels(): array
    {
        return ShopSettingKeys::FEATURE_FLAG_LABELS;
    }

    /** 全機能フラグを取得 */
    public static function getFeatureFlags(?int $projectId = null): array
    {
        $flags = [];
        foreach (ShopSettingKeys::FEATURE_FLAGS as $key => $default) {
            $flags[$key] = $key === 'feature_gallery_enabled'
                ? self::galleryEnabledInHomeMenu($projectId)
                : (bool) ShopSettingStore::getValue($key, $default, $projectId);
        }

        return $flags;
    }

    /** 指定機能が有効か */
    public static function isFeatureEnabled(string $featureKey, ?int $projectId = null): bool
    {
        if ($featureKey === 'feature_gallery_enabled') {
            return self::galleryEnabledInHomeMenu($projectId);
        }

        $default = ShopSettingKeys::FEATURE_FLAGS[$featureKey] ?? true;

        return (bool) ShopSettingStore::getValue($featureKey, $default, $projectId);
    }

    /**
     * チェックボックス形式の保存用に、ガチャ系フラグを現行値で補完する。
     * 有効なときだけキーを付け、無効は未指定のままにする（missing=OFF と両立）。
     *
     * @param  array<string, bool|string|int|null>  $flags
     * @return array<string, bool|string|int|null>
     */
    public static function withPreservedLotteryManagedFlags(array $flags, ?int $projectId = null): array
    {
        $current = self::getFeatureFlags($projectId);
        foreach (ShopSettingKeys::LOTTERY_MANAGED_FEATURE_FLAGS as $key) {
            if (array_key_exists($key, $flags)) {
                continue;
            }
            if (($current[$key] ?? false) === true) {
                $flags[$key] = true;
            }
        }

        return $flags;
    }

    /** 機能フラグを一括更新（未指定キーは OFF。false も OFF として扱う） */
    public static function setFeatureFlags(array $flags, ?int $projectId = null): void
    {
        foreach (ShopSettingKeys::FEATURE_FLAGS as $key => $default) {
            $enabled = $key === 'feature_gallery_enabled'
                ? self::galleryEnabledInHomeMenu($projectId)
                : (array_key_exists($key, $flags) && self::isTruthyFeatureFlag($flags[$key]));
            ShopSettingStore::setValue($key, $enabled ? '1' : '0', 'boolean', $projectId);
        }
    }

    /** ギャラリーの公開状態はホームメニューの「表示」だけで決まる。 */
    private static function galleryEnabledInHomeMenu(?int $projectId): bool
    {
        $items = ShopSettingStore::getValue('frontend_menu_items', null, $projectId);
        if (! is_array($items)) {
            return true;
        }
        foreach ($items as $item) {
            if (is_array($item) && ($item['tab'] ?? null) === 'gallery') {
                return ! array_key_exists('enabled', $item)
                    || filter_var($item['enabled'], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return true;
    }

    /**
     * @param  bool|int|float|string|null  $value
     */
    private static function isTruthyFeatureFlag($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }
}
