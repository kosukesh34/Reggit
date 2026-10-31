<?php

namespace App\UseCases\Payment;

/**
 * プロジェクトの決済設定（秘密鍵）で動く決済。決済設定のない注文は共通の秘密鍵で動くため、既定プロジェクトでだけ使える。
 * 資格情報を自分で持つ決済はこれを実装しない。
 */
interface ProjectCredentialedGateway
{
    /** @param array<string, string> $metadata */
    public function withProjectCredentials(string $secretKey, string $brand, array $metadata): PaymentGateway;
}
