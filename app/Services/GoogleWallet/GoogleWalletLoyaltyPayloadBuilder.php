<?php

namespace App\Services\GoogleWallet;

use App\Enums\GoogleWalletApi;

/**
 * Google Wallet JWT 用の LoyaltyClass / LoyaltyObject 配列を組み立てる。
 * マジックストリング排除・クラス行数削減のためサービスから分離。
 */
class GoogleWalletLoyaltyPayloadBuilder
{
    /**
     * LoyaltyClass と LoyaltyObject の配列を返す。
     *
     * @param  array{classId: string, objectId: string, programName: string, programLogoUri: string, heroImageUri: string, accountNameDisplay: string, accountIdDisplay: string, barcodeValue: string, points: int, rankLabel: string}  $params
     * @return array{loyaltyClass: array<string, mixed>, loyaltyObject: array<string, mixed>}
     */
    public function build(array $params): array
    {
        $loyaltyClass = $this->buildLoyaltyClass($params);
        $loyaltyObject = $this->buildLoyaltyObject($params);

        return ['loyaltyClass' => $loyaltyClass, 'loyaltyObject' => $loyaltyObject];
    }

    /**
     * 表示用文字列を指定長以内に収める（マルチバイト考慮）。
     */
    public function truncateForWallet(string $s, int $maxLen): string
    {
        if (mb_strlen($s) <= $maxLen) {
            return $s;
        }

        return mb_substr($s, 0, $maxLen);
    }

    /**
     * @param  array{classId: string, programName: string, programLogoUri: string, accountNameDisplay: string, accountIdDisplay: string, barcodeValue: string, points: int, rankLabel: string}  $params
     * @return array<string, mixed>
     */
    private function buildLoyaltyClass(array $params): array
    {
        $lang = GoogleWalletApi::DEFAULT_LANGUAGE;
        $logoImage = [
            'sourceUri' => ['uri' => $params['programLogoUri']],
            'contentDescription' => [
                'defaultValue' => ['language' => $lang, 'value' => $params['programName'] . ' logo'],
            ],
        ];
        $class = [
            'id' => $params['classId'],
            'programName' => $params['programName'],
            'issuerName' => $params['programName'],
            'reviewStatus' => GoogleWalletApi::REVIEW_STATUS_UNDER_REVIEW,
            'programLogo' => $logoImage,
            'wideProgramLogo' => $logoImage,
            'accountNameLabel' => GoogleWalletApi::LABEL_ACCOUNT_NAME,
            'accountIdLabel' => GoogleWalletApi::LABEL_ACCOUNT_ID,
            'rewardsTierLabel' => GoogleWalletApi::LABEL_RANK,
        ];
        $hexBg = config('google_wallet.hex_background_color', '');
        if ($hexBg !== '' && preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $hexBg)) {
            $class['hexBackgroundColor'] = $hexBg;
        }
        $class['classTemplateInfo'] = $this->getClassTemplateInfo();

        return $class;
    }

    /**
     * @return array<string, mixed>
     */
    private function getClassTemplateInfo(): array
    {
        return [
            'cardTemplateOverride' => [
                'cardRowTemplateInfos' => [
                    [
                        'twoItems' => [
                            'startItem' => ['firstValue' => ['fields' => [['fieldPath' => 'object.accountName']]]],
                            'endItem' => ['firstValue' => ['fields' => [['fieldPath' => 'object.loyaltyPoints']]]],
                        ],
                    ],
                    [
                        'oneItem' => [
                            'item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.accountId']]]],
                        ],
                    ],
                ],
            ],
            'listTemplateOverride' => [
                'firstRowOption' => ['fieldOption' => ['fields' => [['fieldPath' => 'object.accountName']]]],
                'secondRowOption' => ['fields' => [['fieldPath' => 'object.loyaltyPoints.balance']]],
            ],
            'detailsTemplateOverride' => [
                'detailsItemInfos' => [
                    ['item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.textModulesData[\'rank\']']]]]],
                    ['item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.textModulesData[\'points\']']]]]],
                    ['item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.textModulesData[\'member_info\']']]]]],
                ],
            ],
        ];
    }

    /**
     * @param  array{objectId: string, classId: string, programName: string, heroImageUri: string, accountNameDisplay: string, accountIdDisplay: string, barcodeValue: string, points: int, rankLabel: string}  $params
     * @return array<string, mixed>
     */
    private function buildLoyaltyObject(array $params): array
    {
        $lang = GoogleWalletApi::DEFAULT_LANGUAGE;
        $textModules = $this->buildTextModules($params['rankLabel'], (string) $params['points'], $params['barcodeValue']);

        return [
            'id' => $params['objectId'],
            'classId' => $params['classId'],
            'state' => GoogleWalletApi::OBJECT_STATE_ACTIVE,
            'accountId' => $params['accountIdDisplay'],
            'accountName' => $params['accountNameDisplay'],
            'barcode' => [
                'type' => GoogleWalletApi::BARCODE_TYPE_CODE_128,
                'value' => $params['barcodeValue'],
                'alternateText' => $params['barcodeValue'],
            ],
            'loyaltyPoints' => [
                'label' => GoogleWalletApi::LABEL_POINTS,
                'balance' => ['int' => (int) $params['points']],
            ],
            'textModulesData' => $textModules,
            'heroImage' => [
                'sourceUri' => ['uri' => $params['heroImageUri']],
                'contentDescription' => [
                    'defaultValue' => ['language' => $lang, 'value' => $params['programName'] . ' card'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildTextModules(string $rankLabel, string $pointsStr, string $barcodeValue): array
    {
        $textModules = [];
        if ($rankLabel !== '') {
            $textModules[] = [
                'id' => 'rank',
                'header' => GoogleWalletApi::LABEL_RANK,
                'body' => $rankLabel,
            ];
        }
        $textModules[] = [
            'id' => 'points',
            'header' => GoogleWalletApi::LABEL_POINTS,
            'body' => $pointsStr . ' pt' . ($rankLabel !== '' ? ' · ' . $rankLabel : ''),
        ];
        $textModules[] = [
            'id' => 'member_info',
            'header' => GoogleWalletApi::LABEL_ACCOUNT_ID,
            'body' => $barcodeValue,
        ];

        return $textModules;
    }
}
