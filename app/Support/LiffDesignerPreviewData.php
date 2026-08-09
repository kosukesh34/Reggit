<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\StampCard;
use App\Providers\AppServiceProvider;

/**
 * LIFFデザイナー（テンプレ含む）プレビュー用に、店舗の実データ要約を返す。
 */
class LiffDesignerPreviewData
{
    /**
     * @return array{
     *   products: list<array{id: int|string, name: string, price: int|float|string, category: string, image_url: string|null}>,
     *   coupons: list<array{id: int|string, title: string, meta: string, image_url: string|null, available: bool}>,
     *   stamps: list<array{id: int|string, name: string, required: int, reward: string, image_url: string|null}>,
     *   member: array<string, mixed>|null
     * }
     */
    public static function forProject(int $projectId): array
    {
        $products = Product::query()
            ->where('project_id', $projectId)
            ->where('is_available', true)
            ->orderBy('id')
            ->limit(16)
            ->get(['id', 'name', 'price', 'category', 'image_url'])
            ->map(static function (Product $p): array {
                return [
                    'id' => $p->id,
                    'name' => (string) $p->name,
                    'price' => $p->price,
                    'category' => (string) ($p->category ?: 'other'),
                    'image_url' => $p->image_url !== null && $p->image_url !== '' ? (string) $p->image_url : null,
                ];
            })
            ->all();

        $coupons = Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(static function (Coupon $c): array {
                $title = trim((string) $c->name);
                if ($title === '') {
                    $title = 'クーポン';
                }
                $meta = trim((string) ($c->description ?? ''));
                if ($meta === '') {
                    $meta = '会員限定';
                }

                return [
                    'id' => $c->id,
                    'title' => $title,
                    'meta' => mb_substr($meta, 0, 40),
                    'image_url' => $c->image_url !== null && $c->image_url !== ''
                        ? (string) $c->image_url
                        : null,
                    'available' => true,
                ];
            })
            ->all();

        $stamps = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(4)
            ->get(['id', 'name', 'required_stamps', 'reward_description', 'image_url'])
            ->map(static function (StampCard $s): array {
                return [
                    'id' => $s->id,
                    'name' => (string) $s->name,
                    'required' => (int) $s->required_stamps,
                    'reward' => (string) ($s->reward_description ?: ''),
                    'image_url' => $s->image_url !== null && $s->image_url !== '' ? (string) $s->image_url : null,
                ];
            })
            ->all();

        $member = null;
        try {
            $member = AppServiceProvider::buildDesignerPreviewMemberPayloadForProject($projectId);
        } catch (\Throwable) {
            $member = null;
        }

        return [
            'products' => $products,
            'coupons' => $coupons,
            'stamps' => $stamps,
            'member' => $member,
        ];
    }
}
