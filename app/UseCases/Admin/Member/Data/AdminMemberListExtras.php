<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Coupon;
use App\Models\MemberAudienceGroup;
use App\Models\MemberTag;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * 会員一覧画面の付帯表示データ（LINE招待QR、配信と共通の絞り込み条件の選択肢など）。
 */
class AdminMemberListExtras
{
    /**
     * @param  Collection<int, MemberTag>  $memberTags
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Coupon>  $coupons
     * @param  array<string, array{label: string, color: string}>  $rfmSegments
     * @param  list<array<string, mixed>>  $profileFields
     * @param  Collection<int, MemberAudienceGroup>  $audienceGroups
     */
    public function __construct(
        public readonly string $lineInviteUrl,
        public readonly string $lineInviteLabel,
        public readonly Collection $memberTags = new Collection,
        public readonly Collection $products = new Collection,
        public readonly Collection $coupons = new Collection,
        public readonly array $rfmSegments = [],
        public readonly array $profileFields = [],
        public readonly Collection $audienceGroups = new Collection,
    ) {}
}
