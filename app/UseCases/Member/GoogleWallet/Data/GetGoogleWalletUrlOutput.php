<?php

namespace App\UseCases\Member\GoogleWallet\Data;

/**
 * Google Wallet URL 発行 UseCase の出力。
 * 成功時は url を返し、失敗時は errorType と errorMessage（および reasons）で HTTP レスポンスを組み立てる。
 */
final class GetGoogleWalletUrlOutput
{
    public const ERROR_UNAVAILABLE = 'unavailable';

    public const ERROR_NOT_MEMBER = 'not_member';

    public const ERROR_URL_FAILED = 'url_failed';

    /** @param array<int, string> $reasons 利用不可時の理由一覧 */
    public function __construct(
        public bool $success,
        public ?string $url = null,
        public ?string $errorType = null,
        public ?string $errorMessage = null,
        public array $reasons = [],
    ) {
    }

    public static function success(string $url): self
    {
        return new self(success: true, url: $url);
    }

    public static function unavailable(array $reasons): self
    {
        return new self(
            success: false,
            errorType: self::ERROR_UNAVAILABLE,
            errorMessage: 'Google Wallet は利用できません。',
            reasons: $reasons
        );
    }

    public static function notMember(): self
    {
        return new self(
            success: false,
            errorType: self::ERROR_NOT_MEMBER,
            errorMessage: '会員登録が必要です'
        );
    }

    public static function urlFailed(): self
    {
        return new self(
            success: false,
            errorType: self::ERROR_URL_FAILED,
            errorMessage: 'URL を発行できませんでした'
        );
    }
}
