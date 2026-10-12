<?php

namespace Tests\Feature;

use App\Services\WalletPassService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WalletPassSigningCleanupTest extends TestCase
{
    #[TestWith([false])]
    #[TestWith([true])]
    public function test_signing_cleans_converted_certificates_on_success_and_key_failure(bool $validKey): void
    {
        $dir = sys_get_temp_dir().'/wallet-signing-'.bin2hex(random_bytes(8));
        File::makeDirectory($dir);
        try {
            $key = openssl_pkey_new(['private_key_bits' => 2048]);
            $csr = openssl_csr_new(['commonName' => 'Wallet Test'], $key);
            $certificate = openssl_csr_sign($csr, null, $key, 1);
            openssl_x509_export($certificate, $pem);
            $der = base64_decode(preg_replace('/-----[^\n]+-----|\s/', '', $pem), true);
            File::put($dir.'/pass.cer', $der);
            if ($validKey) {
                openssl_pkey_export_to_file($key, $dir.'/pass.key');
            } else {
                File::put($dir.'/pass.key', 'invalid key');
            }
            config([
                'wallet_pass.cert_path' => $dir.'/pass.cer', 'wallet_pass.key_path' => $dir.'/pass.key',
                'wallet_pass.wwdr_path' => $dir.'/pass.cer', 'wallet_pass.assets_path' => $dir,
                'wallet_pass.team_id' => 'TEST', 'wallet_pass.type_id' => 'pass.example.member',
            ]);
            $before = glob(sys_get_temp_dir().'/wallet-pass-*');
            $service = app(WalletPassService::class);
            $pass = $service->buildMembershipPass('123', 'Test', 'member-1');
            if ($validKey) {
                $this->assertNotNull($pass, $service->getLastError() ?? 'Pass generation failed');
                $this->assertStringStartsWith('PK', $pass);
            } else {
                $this->assertNull($pass);
                $this->assertStringContainsString('秘密鍵の読み込み', $service->getLastError());
            }
            $this->assertSame([], glob($dir.'/*.pem.*'));
            $this->assertSame($before, glob(sys_get_temp_dir().'/wallet-pass-*'));
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
