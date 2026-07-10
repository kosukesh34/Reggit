<?php

namespace App\UseCases\Member\WalletPass\Data;

/**
 * Wallet Pass 用ワンタイム URL 発行 UseCase の出力。
 */
final class WalletPassUrlIssueOutput
{
    public const ERROR_UNAVAILABLE = 'unavailable';

    public const ERROR_NOT_MEMBER = 'not_member';

    /** @param array<int, string> $reasons */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $url = null,
        public readonly ?string $errorType = null,
        public readonly ?string $errorMessage = null,
        public readonly array $reasons = [],
    ) {}

    public static function success(string $url): self
    {
        return new self(success: true, url: $url);
    }

    /** @param array<int, string> $reasons */
    public static function unavailable(array $reasons): self
    {
        return new self(
            success: false,
            errorType: self::ERROR_UNAVAILABLE,
            errorMessage: 'Apple Wallet は現在ご利用いただけません',
            reasons: $reasons,
        );
    }

    public static function notMember(): self
    {
        return new self(
            success: false,
            errorType: self::ERROR_NOT_MEMBER,
            errorMessage: '会員登録が必要です',
        );
    }
}
