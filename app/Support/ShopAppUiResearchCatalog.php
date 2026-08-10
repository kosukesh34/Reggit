<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * docs/research 配下の店舗アプリ UI 学習データを読み取り用に整形する。
 */
class ShopAppUiResearchCatalog
{
    private const RESEARCH_DIR = 'docs/research';

    private const FEATURED = [
        ['file' => '01-starbucks-rewards.jpg', 'title' => 'Starbucks Rewards', 'note' => '挨拶 → 残高/進捗 → 特典交換 CTA', 'template' => 'rg-suite-loyalty / cafe'],
        ['file' => '02-chateraise-member-card.jpg', 'title' => 'シャトレーゼ 会員証', 'note' => '大ポイント + 下部バーコード提示', 'template' => 'rg-suite-sweets / barcode-dock'],
        ['file' => '03-cozycorner-home.jpg', 'title' => 'コージーコーナー ホーム', 'note' => 'マイル帯 → ヒーロー → 会員証/クーポン', 'template' => 'rg-suite-sweets'],
        ['file' => '04-godiva-brand.jpg', 'title' => 'GODIVA', 'note' => 'ラグジュアリー会員クラブ', 'template' => 'rg-screen-chocolate-club'],
        ['file' => '05-uniqlo-category.jpg', 'title' => 'UNIQLO カテゴリ', 'note' => '検索・スキャン・会員証常駐', 'template' => 'rg-suite-retail'],
        ['file' => '06-mcdonalds-order.jpg', 'title' => 'マクドナルド 注文', 'note' => 'オーダー/デリバリーを最初に宣言', 'template' => 'rg-suite-order'],
        ['file' => '07-chipotle-pickup.jpg', 'title' => 'Chipotle Pickup', 'note' => '受取時刻・袋数で不安を消す', 'template' => 'rg-screen-pickup-ready'],
        ['file' => '08-chateraise-category.jpg', 'title' => 'シャトレーゼ カテゴリ', 'note' => '食欲写真 × ブランド色 × 受取', 'template' => 'rg-suite-sweets'],
    ];

    private const PRINCIPLES = [
        [
            'id' => 'P1',
            'title' => '会計時は「1タップ・大バーコード」',
            'body' => 'ポイントを大きく、画面下部にバーコードを常時露出。ホームからも sticky CTA「会員証を提示」。',
            'source' => 'シャトレーゼ',
        ],
        [
            'id' => 'P2',
            'title' => 'ホームは「ステータス → 主役1つ → クイックアクション」',
            'body' => '商品棚・統計・お知らせを全部載せない。主役は1つ。',
            'source' => 'Starbucks / コージーコーナー / Chipotle / UNIQLO',
        ],
        [
            'id' => 'P3',
            'title' => 'ポイントは「次の特典までの距離」',
            'body' => 'stats_row に points_to_next を入れ、交換 CTA を近くに置く。',
            'source' => 'Starbucks Rewards',
        ],
        [
            'id' => 'P4',
            'title' => '菓子・土産は「食欲写真 × ブランド色 × 受取」',
            'body' => '季節ヒーローと会員証/クーポン並列。商品棚より会計で使えるカードが主役。',
            'source' => 'シャトレーゼ / コージーコーナー / GODIVA',
        ],
        [
            'id' => 'P5',
            'title' => '注文系は「受取不安を消す」',
            'body' => '受取カードを最上部に。時刻・場所・袋数・ヘルプを明示。',
            'source' => "Chipotle / McDonald's",
        ],
        [
            'id' => 'P6',
            'title' => '小売は「検索・スキャン・会員証」が常駐',
            'body' => '検索導線とカテゴリ発見を先に、会員証は下部タブと sticky で届く位置に。',
            'source' => 'UNIQLO',
        ],
    ];

    private const MAPPINGS = [
        ['source' => 'Starbucks Rewards', 'target' => 'rg-suite-loyalty / rg-screen-rewards'],
        ['source' => 'シャトレーゼ / コージーコーナー', 'target' => 'rg-suite-sweets / rg-screen-sweets-home / rg-screen-barcode-dock'],
        ['source' => 'GODIVA / Lindt', 'target' => 'rg-screen-chocolate-club / theme chocolate'],
        ['source' => "McDonald's / Chipotle", 'target' => 'rg-suite-order / rg-screen-pickup-ready'],
        ['source' => 'UNIQLO', 'target' => 'rg-suite-retail / rg-screen-category-browse'],
        ['source' => 'Starbucks 朝', 'target' => 'rg-suite-cafe / rg-screen-morning'],
    ];

    public static function researchRoot(): string
    {
        return base_path(self::RESEARCH_DIR);
    }

    /**
     * @return array{
     *   brands: int,
     *   with_app: int,
     *   without_app: int,
     *   by_category: array<string, array{total: int, with_app: int}>
     * }
     */
    public static function summary(): array
    {
        $path = self::researchRoot().'/app-store-lookup/summary.json';
        if (! is_file($path)) {
            return ['brands' => 0, 'with_app' => 0, 'without_app' => 0, 'by_category' => []];
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return ['brands' => 0, 'with_app' => 0, 'without_app' => 0, 'by_category' => []];
        }

        return [
            'brands' => (int) ($decoded['brands'] ?? 0),
            'with_app' => (int) ($decoded['with_app'] ?? 0),
            'without_app' => (int) ($decoded['without_app'] ?? 0),
            'by_category' => is_array($decoded['by_category'] ?? null) ? $decoded['by_category'] : [],
        ];
    }

    /**
     * @return array<int, array{id: string, title: string, body: string, source: string}>
     */
    public static function principles(): array
    {
        return self::PRINCIPLES;
    }

    /**
     * @return array<int, array{file: string, title: string, note: string, template: string, exists: bool}>
     */
    public static function featuredShots(): array
    {
        $dir = self::researchRoot().'/app-ui-screenshots/_featured';

        return array_map(static function (array $item) use ($dir): array {
            $item['exists'] = is_file($dir.'/'.$item['file']);

            return $item;
        }, self::FEATURED);
    }

    /**
     * @return array<int, array{source: string, target: string}>
     */
    public static function templateMappings(): array
    {
        return self::MAPPINGS;
    }

    /**
     * @return array<int, array{
     *   brand: string,
     *   categories: string,
     *   trackName: string,
     *   appStoreUrl: string,
     *   screenshotCount: int,
     *   averageUserRating: float,
     *   userRatingCount: int
     * }>
     */
    public static function highlightBrands(int $limit = 40): array
    {
        $path = self::researchRoot().'/app-store-lookup/brand_app_lookup.csv';
        if (! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }

        $rows = [];
        while (($cols = fgetcsv($handle)) !== false) {
            if (count($cols) < 14) {
                continue;
            }
            $hasApp = strtolower((string) $cols[3]) === 'true';
            $score = (int) $cols[4];
            if (! $hasApp || $score < 120) {
                continue;
            }
            $rows[] = [
                'brand' => (string) $cols[0],
                'categories' => (string) $cols[2],
                'trackName' => (string) $cols[5],
                'appStoreUrl' => (string) $cols[10],
                'screenshotCount' => (int) $cols[11],
                'averageUserRating' => (float) $cols[12],
                'userRatingCount' => (int) $cols[13],
            ];
        }
        fclose($handle);

        usort($rows, static fn (array $a, array $b): int => $b['userRatingCount'] <=> $a['userRatingCount']);

        return array_slice($rows, 0, $limit);
    }

    public static function learningsMarkdownHtml(): string
    {
        $path = self::researchRoot().'/APP_UI_SCREENSHOT_LEARNINGS.md';
        if (! is_file($path)) {
            return '';
        }

        return Str::markdown((string) file_get_contents($path));
    }

    /**
     * docs/research 配下の許可された相対パスだけを実ファイルパスに解決する。
     */
    public static function resolveAssetPath(string $relativePath): ?string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $allowedPrefixes = [
            'app-ui-screenshots/_featured/',
            'app-store-lookup/',
            'smaregi_shop_app_ui_all_stores.csv',
            'APP_UI_SCREENSHOT_LEARNINGS.md',
            'BRAND_APP_LOOKUP_REPORT.md',
        ];
        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($relativePath, $prefix) || $relativePath === $prefix) {
                $isAllowed = true;
                break;
            }
        }
        if (! $isAllowed) {
            return null;
        }

        $full = self::researchRoot().'/'.$relativePath;
        if (! is_file($full)) {
            return null;
        }

        $realResearch = realpath(self::researchRoot());
        $realFile = realpath($full);
        if ($realResearch === false || $realFile === false || ! str_starts_with($realFile, $realResearch.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $realFile;
    }
}
