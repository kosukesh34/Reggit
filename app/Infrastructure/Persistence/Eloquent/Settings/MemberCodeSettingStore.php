<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

/** 会員番号の採番設定。 */
class MemberCodeSettingStore
{
    /** 会員コード生成: sequential=連番, random=ランダム */
    public static function getMemberCodeType(?int $projectId = null): string
    {
        return (string) ShopSettingStore::getValue('member_code_type', 'random', $projectId);
    }

    /** 会員コード形式: numeric=数字のみ, alphanumeric=英数字混在 */
    public static function getMemberCodeFormat(?int $projectId = null): string
    {
        return (string) ShopSettingStore::getValue('member_code_format', 'numeric', $projectId);
    }

    /** 会員コード桁数（プレフィックス除く） */
    public static function getMemberCodeLength(?int $projectId = null): int
    {
        return (int) ShopSettingStore::getValue('member_code_length', 10, $projectId);
    }

    /** 会員コード先頭プレフィックス（任意） */
    public static function getMemberCodePrefix(?int $projectId = null): string
    {
        return (string) ShopSettingStore::getValue('member_code_prefix', '', $projectId);
    }

    /** 連番時の次に使う番号（内部用） */
    public static function getMemberCodeNextNumber(?int $projectId = null): int
    {
        return (int) ShopSettingStore::getValue('member_code_next_number', 1, $projectId);
    }

    public static function setMemberCodeNextNumber(int $next, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('member_code_next_number', $next, 'integer', $projectId);
    }
}
