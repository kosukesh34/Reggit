<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Support\MemberCardDesignMap;
use App\Util\AdminContext;

/** 会員証の表示設定。 */
class MemberCardSettingStore
{
    /** フロントでポイント数を表示するか */
    public static function getShowPointsDisplay(?int $projectId = null): bool
    {
        return (bool) ShopSettingStore::getValue('show_points_display', true, $projectId);
    }

    /** フロントで「次のランクまで」を表示するか */
    public static function getShowRankProgress(?int $projectId = null): bool
    {
        return (bool) ShopSettingStore::getValue('show_rank_progress', true, $projectId);
    }

    /** 会員未登録（ゲスト）のときに会員証カードを表示するか。false のときはカードを表示せずメッセージのみ */
    public static function getShowMemberCardForGuest(?int $projectId = null): bool
    {
        return (bool) ShopSettingStore::getValue('show_member_card_for_guest', true, $projectId);
    }

    public static function setShowMemberCardForGuest(bool $value, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('show_member_card_for_guest', $value, 'boolean', AdminContext::resolveProjectId($projectId));
    }

    /** 会員証のレイアウトデザイン（MemberCardDesignMap::DESIGNS） */
    public static function getMemberCardDesign(?int $projectId = null): string
    {
        $v = (string) ShopSettingStore::getValue('member_card_design', 'default', $projectId);

        return MemberCardDesignMap::isValidDesign($v) ? $v : 'default';
    }

    public static function setMemberCardDesign(string $value, ?int $projectId = null): void
    {
        $value = MemberCardDesignMap::isValidDesign($value) ? $value : 'default';
        ShopSettingStore::setValue('member_card_design', $value, 'string', AdminContext::resolveProjectId($projectId));
    }

    /** 会員証の表示形式。card=カード型（裏表あり）, sheet=シート型（1面表示） */
    public static function getMemberCardDisplayType(?int $projectId = null): string
    {
        $v = ShopSettingStore::getValue('member_card_display_type', 'card', $projectId);

        return in_array($v, ['card', 'sheet'], true) ? $v : 'card';
    }

    public static function setMemberCardDisplayType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['card', 'sheet'], true) ? $value : 'card';
        ShopSettingStore::setValue('member_card_display_type', $value, 'string', AdminContext::resolveProjectId($projectId));
    }

    /** 会員証の会員番号表示。barcode=バーコード, qrcode=QRコード */
    public static function getMemberCardCodeType(?int $projectId = null): string
    {
        $v = ShopSettingStore::getValue('member_card_code_type', 'barcode', $projectId);

        return in_array($v, ['barcode', 'qrcode'], true) ? $v : 'barcode';
    }

    public static function setMemberCardCodeType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['barcode', 'qrcode'], true) ? $value : 'barcode';
        ShopSettingStore::setValue('member_card_code_type', $value, 'string', AdminContext::resolveProjectId($projectId));
    }
}
