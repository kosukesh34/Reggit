<?php

namespace Tests\Unit;

use App\Enums\GoogleWalletApi;
use App\Services\GoogleWallet\GoogleWalletLoyaltyPayloadBuilder;
use Tests\TestCase;

/**
 * GoogleWalletLoyaltyPayloadBuilder の Unit テスト。
 * ビルド結果が API 仕様のキーと定数を含むことを検証する。
 */
class GoogleWalletLoyaltyPayloadBuilderTest extends TestCase
{
    public function test_build_期待する構造を返すこと(): void
    {
        $builder = new GoogleWalletLoyaltyPayloadBuilder();
        $params = [
            'classId' => 'issuer.class1',
            'objectId' => 'issuer.obj1',
            'programName' => 'Test Program',
            'programLogoUri' => 'https://example.com/logo.png',
            'heroImageUri' => 'https://example.com/hero.png',
            'accountNameDisplay' => '会員名',
            'accountIdDisplay' => '12345',
            'barcodeValue' => '12345',
            'points' => 100,
            'rankLabel' => 'ゴールド',
        ];

        $result = $builder->build($params);

        $this->assertArrayHasKey('loyaltyClass', $result);
        $this->assertArrayHasKey('loyaltyObject', $result);

        $class = $result['loyaltyClass'];
        $this->assertSame('issuer.class1', $class['id']);
        $this->assertSame(GoogleWalletApi::REVIEW_STATUS_UNDER_REVIEW, $class['reviewStatus']);
        $this->assertArrayHasKey('classTemplateInfo', $class);

        $object = $result['loyaltyObject'];
        $this->assertSame('issuer.obj1', $object['id']);
        $this->assertSame(GoogleWalletApi::OBJECT_STATE_ACTIVE, $object['state']);
        $this->assertSame(GoogleWalletApi::BARCODE_TYPE_CODE_128, $object['barcode']['type']);
        $this->assertSame(100, $object['loyaltyPoints']['balance']['int']);
    }

    public function test_truncateForWallet_指定長で切り詰めること(): void
    {
        $builder = new GoogleWalletLoyaltyPayloadBuilder();

        $this->assertSame('ab', $builder->truncateForWallet('abcd', 2));
        $this->assertSame('あ', $builder->truncateForWallet('あいう', 1));
    }
}
