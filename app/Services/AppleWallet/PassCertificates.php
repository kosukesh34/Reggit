<?php

namespace App\Services\AppleWallet;

use Illuminate\Support\Facades\File;

/** Apple Wallet の Pass 証明書・秘密鍵・WWDR 証明書の解決と PEM 変換。 */
class PassCertificates
{
    /**
     * 証明書まわりで Wallet Pass を利用できない理由（設定値のチェックは WalletPassService 側）。
     *
     * @return array<int, string>
     */
    public function unavailabilityReasons(): array
    {
        $reasons = [];
        $certPath = $this->resolveEffectiveCertPath();
        $keyPath = $this->resolveEffectiveKeyPath();
        $wwdrPath = $this->resolvePath(config('wallet_pass.wwdr_path', ''));
        if ($certPath === '' || ! is_readable($certPath)) {
            $configured = $this->resolvePath(config('wallet_pass.cert_path', ''));
            $reasons[] = 'pass.cer not found or not readable: '.($configured !== '' ? $configured : storage_path('app/wallet_pass/certificates/pass.cer'));
        }
        if ($keyPath === '' || ! is_readable($keyPath)) {
            $configured = $this->resolvePath(config('wallet_pass.key_path', ''));
            $reasons[] = 'iOS-Passkey-Key.key not found or not readable: '.($configured !== '' ? $configured : storage_path('app/wallet_pass/certificates/iOS-Passkey-Key.key'));
        }
        if ($wwdrPath !== '' && ! is_readable($wwdrPath)) {
            $reasons[] = 'WWDR 証明書 (AppleWWDRCAG3.cer / AppleWWDRCAG4.cer) が見つかりません: '.$wwdrPath;
        }
        if ($certPath !== '' && is_readable($certPath)) {
            $resolvedWwdr = $this->resolveWwdrPathForCertificate($certPath, $wwdrPath);
            if (! is_readable($resolvedWwdr) || ! $this->certificateIssuerMatches($certPath, $resolvedWwdr)) {
                $reasons[] = 'WWDR 証明書が Pass 証明書と一致しません。'
                    .$this->formatWwdrMismatchHint($certPath, $wwdrPath);
            }
        }

        return $reasons;
    }

    public function resolvePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }
        if (str_starts_with($path, '/') || (strlen($path) >= 2 && $path[1] === ':')) {
            return $path;
        }

        return base_path($path);
    }

    public function resolveEffectiveCertPath(): string
    {
        $configured = $this->resolvePath((string) config('wallet_pass.cert_path', ''));
        if ($configured !== '' && is_readable($configured)) {
            return $configured;
        }

        return $this->discoverCertificatePath('cer') ?? $configured;
    }

    public function resolveEffectiveKeyPath(): string
    {
        $configured = $this->resolvePath((string) config('wallet_pass.key_path', ''));
        if ($configured !== '' && is_readable($configured)) {
            return $configured;
        }

        return $this->discoverCertificatePath('key') ?? $configured;
    }

    public function resolveWwdrPathForCertificate(string $certPath, string $configuredWwdrPath): string
    {
        foreach ($this->collectWwdrCandidates($configuredWwdrPath) as $candidate) {
            if ($this->certificateIssuerMatches($certPath, $candidate)) {
                return $candidate;
            }
        }

        return $configuredWwdrPath;
    }

    public function ensurePemCertificate(string $path): string
    {
        $content = File::get($path);
        if (str_contains($content, '-----BEGIN')) {
            return $path;
        }
        $pem = '-----BEGIN CERTIFICATE-----'."\n"
            .chunk_split(base64_encode($content), 64, "\n")
            .'-----END CERTIFICATE-----'."\n";
        $cert = @openssl_x509_read($pem);
        if ($cert === false) {
            throw new \RuntimeException('証明書の読み込みに失敗しました。PEM 形式か DER 形式の証明書を配置してください: '.$path);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'wallet-pass-');
        if ($tmp === false) {
            throw new \RuntimeException('Wallet certificate temporary file could not be created.');
        }
        if (! openssl_x509_export_to_file($cert, $tmp)) {
            @unlink($tmp);
            throw new \RuntimeException('Wallet certificate conversion failed.');
        }
        openssl_x509_free($cert);

        return $tmp;
    }

    public function ensurePemKey(string $path): string
    {
        $content = File::get($path);
        if (str_contains($content, '-----BEGIN')) {
            return $path;
        }
        $key = @openssl_pkey_get_private('file://'.$path);
        if ($key === false) {
            throw new \RuntimeException('秘密鍵の読み込みに失敗しました。PEM 形式の鍵を配置してください: '.$path);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'wallet-pass-');
        if ($tmp === false) {
            throw new \RuntimeException('Wallet key temporary file could not be created.');
        }
        if (! openssl_pkey_export_to_file($key, $tmp)) {
            @unlink($tmp);
            throw new \RuntimeException('Wallet key conversion failed.');
        }
        openssl_pkey_free($key);

        return $tmp;
    }

    private function discoverCertificatePath(string $kind): ?string
    {
        $dir = storage_path('app/wallet_pass/certificates');
        if (! is_dir($dir)) {
            return null;
        }

        if ($kind === 'cer') {
            foreach (glob($dir.'/*.cer') ?: [] as $path) {
                $basename = basename($path);
                if (str_starts_with($basename, 'AppleWWDRCA')) {
                    continue;
                }
                if (is_readable($path)) {
                    return $path;
                }
            }

            return null;
        }

        foreach (glob($dir.'/*.key') ?: [] as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function collectWwdrCandidates(string $configuredWwdrPath): array
    {
        $dir = $configuredWwdrPath !== ''
            ? dirname($this->resolvePath($configuredWwdrPath))
            : storage_path('app/wallet_pass/certificates');
        $seen = [];
        $ordered = [];

        $add = function (string $path) use (&$seen, &$ordered): void {
            $path = $this->resolvePath($path);
            if ($path === '' || isset($seen[$path]) || ! is_readable($path)) {
                return;
            }
            $seen[$path] = true;
            $ordered[] = $path;
        };

        if ($configuredWwdrPath !== '') {
            $add($configuredWwdrPath);
        }

        foreach (['AppleWWDRCAG4.cer', 'AppleWWDRCAG3.cer', 'AppleWWDRCAG2.cer'] as $name) {
            $add($dir.'/'.$name);
        }

        foreach (glob($dir.'/AppleWWDRCA*.cer') ?: [] as $path) {
            $add($path);
        }

        return $ordered;
    }

    private function formatWwdrMismatchHint(string $certPath, string $configuredWwdrPath): string
    {
        $issuer = $this->getCertificateIssuerCommonName($certPath);
        $dir = $configuredWwdrPath !== '' ? dirname($configuredWwdrPath) : storage_path('app/wallet_pass/certificates');
        $hint = ' Pass 証明書の発行者: '.($issuer ?: '不明').'。';
        $hint .= ' Apple Developer から WWDR 証明書 (G4: https://www.apple.com/certificateauthority/AppleWWDRCAG4.cer ) をダウンロードし、';
        $hint .= ' '.$dir.' に AppleWWDRCAG4.cer として配置し、.env の WALLET_PASS_WWDR_PATH を更新してください。';

        return $hint;
    }

    private function getCertificateIssuerCommonName(string $certPath): ?string
    {
        if (! is_readable($certPath)) {
            return null;
        }

        try {
            $certPem = $this->ensurePemCertificate($certPath);
            $certInfo = openssl_x509_parse(File::get($certPem));

            return is_array($certInfo) ? ($certInfo['issuer']['CN'] ?? null) : null;
        } catch (\Throwable) {
            return null;
        } finally {
            if (isset($certPem) && $certPem !== $certPath) {
                @unlink($certPem);
            }
        }
    }

    private function certificateIssuerMatches(string $certPath, string $wwdrPath): bool
    {
        if (! is_readable($certPath) || ! is_readable($wwdrPath)) {
            return false;
        }

        try {
            $certPem = $this->ensurePemCertificate($certPath);
            $wwdrPem = $this->ensurePemCertificate($wwdrPath);
            $certInfo = openssl_x509_parse(File::get($certPem));
            $wwdrInfo = openssl_x509_parse(File::get($wwdrPem));
        } catch (\Throwable) {
            return false;
        } finally {
            if (isset($certPem) && $certPem !== $certPath) {
                @unlink($certPem);
            }
            if (isset($wwdrPem) && $wwdrPem !== $wwdrPath) {
                @unlink($wwdrPem);
            }
        }

        if (! is_array($certInfo) || ! is_array($wwdrInfo)) {
            return false;
        }

        return ($certInfo['issuer']['CN'] ?? null) === ($wwdrInfo['subject']['CN'] ?? null)
            && ($certInfo['issuer']['OU'] ?? null) === ($wwdrInfo['subject']['OU'] ?? null)
            && ($certInfo['issuer']['O'] ?? null) === ($wwdrInfo['subject']['O'] ?? null);
    }
}
