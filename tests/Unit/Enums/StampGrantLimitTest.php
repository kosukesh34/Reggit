<?php

namespace Tests\Unit\Enums;

use App\Enums\StampGrantLimit;
use PHPUnit\Framework\TestCase;

class StampGrantLimitTest extends TestCase
{
    public function test_未知の上限は1日1回に正規化すること(): void
    {
        $this->assertSame(StampGrantLimit::DAILY, StampGrantLimit::normalize(''));
        $this->assertSame(StampGrantLimit::DAILY, StampGrantLimit::normalize('invalid'));
        $this->assertSame(StampGrantLimit::DAILY, StampGrantLimit::normalize(StampGrantLimit::DAILY));
        $this->assertSame(StampGrantLimit::PER_MEMBER, StampGrantLimit::normalize(StampGrantLimit::PER_MEMBER));
        $this->assertSame(StampGrantLimit::ONCE, StampGrantLimit::normalize(StampGrantLimit::ONCE));
        $this->assertSame(StampGrantLimit::UNLIMITED, StampGrantLimit::normalize(StampGrantLimit::UNLIMITED));
    }

    public function test_利用上限の説明がユースケースごとに用意されていること(): void
    {
        $useCases = StampGrantLimit::useCases();

        $this->assertSame(['daily', 'member', 'once', 'unlimited'], array_keys($useCases));
        $this->assertSame('レジ横に貼っておく来店スタンプ', $useCases[StampGrantLimit::DAILY]['example']);
        $this->assertSame('チラシ・DM・初回限定のQR', $useCases[StampGrantLimit::PER_MEMBER]['example']);
        $this->assertSame('オープニングキャンペーンの来店スタンプ', $useCases[StampGrantLimit::ONCE]['example']);
        $this->assertSame('動作確認や、連続で押す必要があるとき', $useCases[StampGrantLimit::UNLIMITED]['example']);
    }

    public function test_使用済みメッセージが上限の種類に応じて分かれること(): void
    {
        $this->assertSame('本日はこのQRからのスタンプを付与済みです', StampGrantLimit::redeemedMessage(StampGrantLimit::DAILY));
        $this->assertSame('先着人数に達したため、このQRは終了しました', StampGrantLimit::redeemedMessage(StampGrantLimit::ONCE));
        $this->assertSame('このQRからのスタンプは付与済みです', StampGrantLimit::redeemedMessage(StampGrantLimit::PER_MEMBER));
        $this->assertSame('このQRからのスタンプは付与済みです', StampGrantLimit::redeemedMessage(StampGrantLimit::UNLIMITED));
    }

    public function test_先着人数の表示と正規化ができること(): void
    {
        $this->assertSame('先着10名', StampGrantLimit::displayLabel(StampGrantLimit::ONCE, 10));
        $this->assertSame('先着1名', StampGrantLimit::displayLabel(StampGrantLimit::ONCE, 0));
        $this->assertSame('1日1回（お客様ごと）', StampGrantLimit::displayLabel(StampGrantLimit::DAILY, 30));
        $this->assertSame(1, StampGrantLimit::normalizeQuota(0));
        $this->assertSame(30, StampGrantLimit::normalizeQuota(30));
        $this->assertSame(StampGrantLimit::MAX_QUOTA, StampGrantLimit::normalizeQuota(9999));
    }

    public function test_付与個数は1から上限の範囲に収めること(): void
    {
        $this->assertSame(1, StampGrantLimit::normalizeAmount(0));
        $this->assertSame(3, StampGrantLimit::normalizeAmount(3));
        $this->assertSame(StampGrantLimit::MAX_AMOUNT, StampGrantLimit::normalizeAmount(99));
    }

    public function test_有効期限は選択肢にない値をデフォルトに戻すこと(): void
    {
        $this->assertSame(60, StampGrantLimit::normalizeExpiresMinutes(60));
        $this->assertSame(StampGrantLimit::NEVER_EXPIRES, StampGrantLimit::normalizeExpiresMinutes(0));
        $this->assertSame(525600, StampGrantLimit::normalizeExpiresMinutes(525600));
        $this->assertSame(StampGrantLimit::DEFAULT_EXPIRES_MINUTES, StampGrantLimit::normalizeExpiresMinutes(7));
    }
}
