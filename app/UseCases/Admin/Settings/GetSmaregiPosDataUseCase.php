<?php

namespace App\UseCases\Admin\Settings;

use App\Services\Smaregi\SmaregiApiService;
use App\UseCases\Admin\Settings\Data\SmaregiPosDataView;

/**
 * スマレジAPIから取得したPOSデータを返す（仮想POS確認・開発用・後で削除可）。
 *
 * 店舗・会員・商品はDB/Cacheに保存せず、リクエストのたびにAPI通信のみで取得して表示する。
 * [POS仕様書](https://developers.smaregi.dev/platform-api-reference/apis/pos/) の一覧取得APIをすべて呼び出す。
 */
class GetSmaregiPosDataUseCase
{
    private const LIST_LIMIT = 30;

    /**
     * POS 一覧取得API: path => [表示ラベル, レスポンスのリストキー]
     * 公式仕様: 店舗=pos/stores, 部門=pos/categories, 部門グループ=pos/category_groups
     */
    private const POS_LIST_ENDPOINTS = [
        'pos/stores' => ['店舗', 'stores'],
        'pos/categories' => ['部門', 'categories'],
        'pos/category_groups' => ['部門グループ', 'category_groups'],
        'pos/products' => ['商品', 'products'],
        'pos/sales' => ['セール', 'sales'],
        'pos/customers' => ['会員', 'customers'],
        'pos/customer_ranks' => ['会員ランク', 'customer_ranks'],
        'pos/stocks' => ['在庫', 'stocks'],
        'pos/transactions' => ['取引', 'transactions'],
        'pos/layaways' => ['取置き', 'layaways'],
        'pos/pending_transactions' => ['仮販売', 'pending_transactions'],
        'pos/terminals' => ['レジ端末', 'terminals'],
        'pos/staffs' => ['スタッフ', 'staffs'],
        'pos/settlements' => ['精算', 'settlements'],
        'pos/daily_summaries' => ['日次締め情報', 'daily_summaries'],
        'pos/payment_methods' => ['支払方法', 'payment_methods'],
        'pos/app_payment_methods' => ['個別アプリ専用支払方法', 'app_payment_methods'],
        'pos/coupons' => ['クーポン', 'coupons'],
        'pos/roles' => ['役割・役職', 'roles'],
        'pos/suppliers' => ['仕入先', 'suppliers'],
        'pos/budgets/monthly' => ['月別予算', 'budgets'],
    ];

    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {}

    public function handle(int $projectId): SmaregiPosDataView
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';

        if ($contractId === '') {
            return new SmaregiPosDataView('', '契約IDが設定されていません。スマレジ連携で契約を紐付けてください。');
        }

        $token = $this->smaregiApi->getAppAccessToken($contractId);
        if ($token === null) {
            $clientId = config('smaregi.client_id');
            $clientSecret = config('smaregi.client_secret');
            $envCheck = 'SMAREGI_CLIENT_ID: '.(is_string($clientId) && trim($clientId) !== '' ? '設定済み' : '未設定')
                .' / SMAREGI_CLIENT_SECRET: '.(is_string($clientSecret) && trim($clientSecret) !== '' ? '設定済み' : '未設定');

            return new SmaregiPosDataView($contractId, 'トークン取得に失敗しました。.env の SMAREGI_CLIENT_ID / SMAREGI_CLIENT_SECRET および契約IDを確認してください。接続タイムアウトの場合は id.smaregi.dev へのネットワーク到達性（ファイアウォール・VPN）を確認してください。詳細は storage/logs/laravel.log の [Smaregi] を参照してください。', envCheck: $envCheck);
        }

        $sections = [];
        foreach (self::POS_LIST_ENDPOINTS as $path => $config) {
            $label = $config[0];
            $listKey = $config[1];
            $query = ['limit' => self::LIST_LIMIT];
            // 取引一覧は日付範囲のいずれかが必須のため、締め日で指定
            if ($path === 'pos/transactions') {
                $query['sum_date-from'] = now()->startOfMonth()->format('Y-m-d');
                $query['sum_date-to'] = now()->format('Y-m-d');
            }
            $result = $this->smaregiApi->request($contractId, 'GET', $path, $query);
            if ($result === null) {
                $sections[] = [
                    'label' => $label,
                    'path' => $path,
                    'items' => [],
                    'total' => 0,
                    'error' => '取得失敗（スコープまたはAPIエラー）',
                ];

                continue;
            }
            $list = $this->extractListFromResponse($result, $listKey);
            $total = (int) ($result['total'] ?? count($list));
            $sections[] = [
                'label' => $label,
                'path' => $path,
                'items' => $list,
                'total' => $total,
                'error' => null,
            ];
        }

        return new SmaregiPosDataView($contractId, null, null, $sections);
    }

    /**
     * スマレジAPIは一覧取得で「配列をそのまま返す」場合と「{ key: [] } の形で返す」場合があるため両方に対応する。
     *
     * @param  array<int|string, bool|int|float|string|array|object|null>  $result
     * @return array<int, bool|int|float|string|array|object|null>
     */
    private function extractListFromResponse(array $result, string $listKey): array
    {
        // レスポンスが先頭からキー 0,1,2... の配列（リスト）の場合
        if (array_keys($result) === range(0, count($result) - 1) || $result === []) {
            return $result;
        }
        $list = $result[$listKey] ?? [];

        return is_array($list) ? $list : [];
    }
}
