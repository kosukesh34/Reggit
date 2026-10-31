<?php

namespace App\Services\AppleWallet;

use App\Models\Member;
use App\Services\WalletPassAuthTokenService;
use Illuminate\Support\Facades\File;

/** .pkpass の pass.json（会員番号のバーコード・表示項目・ランク色・更新用トークン）を書き出す。 */
class PassJsonWriter
{
    public function __construct(private WalletPassAuthTokenService $walletPassAuthTokenService) {}

    /**
     * buildMembershipPass 用の pass.json を書き出す（Member に依存しない）
     *
     * @param  array<string, string|int|float|bool|null>  $extraFields  パス用追加フィールド（points, rank_label, rank_key 等）
     */
    public function write(
        string $dir,
        string $barcodeMessage,
        string $organizationName,
        string $serialNumber,
        array $extraFields,
        ?Member $member = null
    ): void {
        $points = isset($extraFields['points']) ? (string) $extraFields['points'] : '0';
        $rankLabel = isset($extraFields['rank_label']) ? (string) $extraFields['rank_label'] : '';
        $rankKey = isset($extraFields['rank_key']) ? (string) $extraFields['rank_key'] : 'bronze';
        $appearance = is_array($extraFields['appearance'] ?? null) ? $extraFields['appearance'] : null;
        $colors = $appearance['colors'] ?? $this->getRankColors($rankKey);

        $pass = [
            'formatVersion' => 1,
            'passTypeIdentifier' => config('wallet_pass.type_id'),
            'teamIdentifier' => config('wallet_pass.team_id'),
            'organizationName' => $organizationName,
            'serialNumber' => $serialNumber,
            'description' => '会員証',
            'backgroundColor' => $colors['bg'],
            'foregroundColor' => $colors['fg'],
            'labelColor' => $colors['label'],
            'storeCard' => [
                'primaryFields' => [
                    ['key' => 'points', 'label' => 'ポイント', 'value' => $points],
                ],
                'secondaryFields' => [
                    ['key' => 'rank', 'label' => 'ランク', 'value' => $rankLabel],
                ],
                'auxiliaryFields' => [
                    ['key' => 'member_number', 'label' => '会員番号', 'value' => $barcodeMessage],
                ],
            ],
            'barcode' => [
                'message' => $barcodeMessage,
                'format' => 'PKBarcodeFormatCode128',
                'messageEncoding' => 'iso-8859-1',
                'altText' => $barcodeMessage,
            ],
        ];

        $webServiceUrl = trim((string) config('wallet_pass.web_service_url', ''));
        if ($webServiceUrl !== '' && $member instanceof Member) {
            $normalizedWebServiceUrl = rtrim($webServiceUrl, '/').'/';
            if (filter_var($normalizedWebServiceUrl, FILTER_VALIDATE_URL)
                && str_starts_with(strtolower($normalizedWebServiceUrl), 'https://')) {
                $pass['webServiceURL'] = $normalizedWebServiceUrl;
                $pass['authenticationToken'] = $this->walletPassAuthTokenService->createForMember($member);
            }
        }

        File::put($dir.'/pass.json', json_encode($pass, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /** ランク別カード色（ウェブ会員証のグラデーション末尾色に合わせた背景・文字・ラベル） @return array{bg: string, fg: string, label: string} */
    private function getRankColors(string $rankKey): array
    {
        $map = [
            'bronze' => ['bg' => 'rgb(92, 46, 10)', 'fg' => 'rgb(255, 255, 255)', 'label' => 'rgb(255, 235, 210)'],
            'silver' => ['bg' => 'rgb(144, 144, 144)', 'fg' => 'rgb(33, 33, 33)', 'label' => 'rgb(80, 80, 80)'],
            'gold' => ['bg' => 'rgb(184, 134, 11)', 'fg' => 'rgb(50, 42, 25)', 'label' => 'rgb(255, 248, 220)'],
            'platinum' => ['bg' => 'rgb(168, 168, 176)', 'fg' => 'rgb(35, 40, 50)', 'label' => 'rgb(95, 105, 120)'],
        ];
        $key = strtolower($rankKey);

        return $map[$key] ?? $map['bronze'];
    }
}
