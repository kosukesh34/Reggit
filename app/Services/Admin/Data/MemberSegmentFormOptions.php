<?php

namespace App\Services\Admin\Data;

use App\Models\Coupon;
use App\Models\MemberAudienceGroup;
use App\Models\MemberTag;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * 会員セグメント配信フォーム（クーポン配信・お知らせ・スケジュール配信・会員一覧）で共通の選択肢。
 */
class MemberSegmentFormOptions
{
    /**
     * @param  Collection<int, Coupon>  $coupons
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, MemberTag>  $memberTags
     * @param  array<string, array{label: string, color: string}>  $rfmSegments
     * @param  list<array<string, mixed>>  $profileFields
     * @param  Collection<int, MemberAudienceGroup>  $audienceGroups
     * @param  list<array{key: string, label: string}>  $rankOptions
     */
    public function __construct(
        public readonly Collection $coupons,
        public readonly Collection $products,
        public readonly Collection $memberTags,
        public readonly array $rfmSegments,
        public readonly array $profileFields,
        public readonly Collection $audienceGroups,
        public readonly array $rankOptions,
    ) {}
}
