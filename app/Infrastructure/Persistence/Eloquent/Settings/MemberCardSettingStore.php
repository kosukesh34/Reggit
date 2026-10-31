<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Support\MemberCardDesignMap;
use App\Util\AdminContext;

/** 会員証の表示設定。 */
class MemberCardSettingStore
{
    public function __construct(
        private ShopSettingStore $values,
    ) {}

    /** フロントでポイント数を表示するか */
    public function getShowPointsDisplay(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('show_points_display', true, $projectId);
    }

    /** フロントで「次のランクまで」を表示するか */
    public function getShowRankProgress(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('show_rank_progress', true, $projectId);
    }

    /** 会員未登録（ゲスト）のときに会員証カードを表示するか。false のときはカードを表示せずメッセージのみ */
    public function getShowMemberCardForGuest(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('show_member_card_for_guest', true, $projectId);
    }

    public function setShowMemberCardForGuest(bool $value, ?int $projectId = null): void
    {
        $this->values->setValue('show_member_card_for_guest', $value, 'boolean', AdminContext::resolveProjectId($projectId));
    }

    /** 会員証のレイアウトデザイン（MemberCardDesignMap::DESIGNS） */
    public function getMemberCardDesign(?int $projectId = null): string
    {
        $v = (string) $this->values->getValue('member_card_design', 'default', $projectId);

        return MemberCardDesignMap::isValidDesign($v) ? $v : 'default';
    }

    public function setMemberCardDesign(string $value, ?int $projectId = null): void
    {
        $value = MemberCardDesignMap::isValidDesign($value) ? $value : 'default';
        $this->values->setValue('member_card_design', $value, 'string', AdminContext::resolveProjectId($projectId));
    }

    /** 会員証の表示形式。card=カード型（裏表あり）, sheet=シート型（1面表示） */
    public function getMemberCardDisplayType(?int $projectId = null): string
    {
        $v = $this->values->getValue('member_card_display_type', 'card', $projectId);

        return in_array($v, ['card', 'sheet'], true) ? $v : 'card';
    }

    public function setMemberCardDisplayType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['card', 'sheet'], true) ? $value : 'card';
        $this->values->setValue('member_card_display_type', $value, 'string', AdminContext::resolveProjectId($projectId));
    }

    /** 会員証の会員番号表示。barcode=バーコード, qrcode=QRコード */
    public function getMemberCardCodeType(?int $projectId = null): string
    {
        $v = $this->values->getValue('member_card_code_type', 'barcode', $projectId);

        return in_array($v, ['barcode', 'qrcode'], true) ? $v : 'barcode';
    }

    public function setMemberCardCodeType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['barcode', 'qrcode'], true) ? $value : 'barcode';
        $this->values->setValue('member_card_code_type', $value, 'string', AdminContext::resolveProjectId($projectId));
    }
}
