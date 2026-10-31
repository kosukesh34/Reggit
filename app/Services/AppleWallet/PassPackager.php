<?php

namespace App\Services\AppleWallet;

use Illuminate\Support\Facades\File;

/** pass.json と画像を置いた作業ディレクトリから、署名付き .pkpass（ZIP）を作る。 */
class PassPackager
{
    public function __construct(
        private PassCertificates $certificates,
    ) {}

    public function package(string $dir): string
    {
        $this->writeManifest($dir);
        $this->signManifest($dir);

        return $this->createZip($dir);
    }

    private function writeManifest(string $dir): void
    {
        $files = ['pass.json'];
        foreach (['icon.png', 'icon@2x.png', 'icon@3x.png', 'logo.png', 'logo@2x.png', 'strip.png', 'strip@2x.png'] as $f) {
            if (File::isFile($dir.'/'.$f)) {
                $files[] = $f;
            }
        }

        $manifest = [];
        foreach ($files as $name) {
            $path = $dir.'/'.$name;
            $manifest[$name] = sha1_file($path);
        }
        File::put($dir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    private function signManifest(string $dir): void
    {
        $certPath = $this->certificates->resolveEffectiveCertPath();
        $keyPath = $this->certificates->resolveEffectiveKeyPath();
        $wwdrPath = $this->certificates->resolveWwdrPathForCertificate($certPath, $this->certificates->resolvePath(config('wallet_pass.wwdr_path')));

        if (! File::isFile($certPath) || ! File::isReadable($certPath)) {
            throw new \RuntimeException('Wallet Pass 証明書が見つかりません: '.$certPath);
        }
        if (! File::isFile($keyPath) || ! File::isReadable($keyPath)) {
            throw new \RuntimeException('Wallet Pass 秘密鍵が見つかりません: '.$keyPath);
        }
        if (! File::isFile($wwdrPath) || ! File::isReadable($wwdrPath)) {
            throw new \RuntimeException('WWDR 証明書が見つかりません: '.$wwdrPath);
        }

        $certPem = $certPath;
        $keyPem = $keyPath;
        $wwdrPem = $wwdrPath;
        try {
            $certPem = $this->certificates->ensurePemCertificate($certPath);
            $keyPem = $this->certificates->ensurePemKey($keyPath);
            $wwdrPem = $this->certificates->ensurePemCertificate($wwdrPath);

            $manifestPath = $dir.'/manifest.json';
            $signaturePath = $dir.'/signature';

            $keyPassphrase = config('wallet_pass.key_passphrase', '');
            $keyUri = 'file://'.str_replace('\\', '/', $keyPem);
            $keyForSign = $keyPassphrase !== '' ? [$keyUri, $keyPassphrase] : $keyUri;

            $headers = [
                'signing-time' => (new \DateTime)->format('Y-m-d H:i:s'),
            ];

            $certUri = 'file://'.str_replace('\\', '/', $certPem);
            $ok = @openssl_pkcs7_sign(
                $manifestPath,
                $signaturePath,
                $certUri,
                $keyForSign,
                $headers,
                PKCS7_BINARY | PKCS7_DETACHED,
                $wwdrPem
            );
        } finally {
            if ($certPem !== $certPath) {
                @unlink($certPem);
            }
            if ($keyPem !== $keyPath) {
                @unlink($keyPem);
            }
            if ($wwdrPem !== $wwdrPath) {
                @unlink($wwdrPem);
            }
        }

        if ($ok === false) {
            $msg = openssl_error_string() ?: '署名に失敗しました。';
            throw new \RuntimeException('Wallet Pass の署名に失敗しました: '.$msg);
        }

        $this->stripSignatureToDer($signaturePath);
    }

    private function stripSignatureToDer(string $signaturePath): void
    {
        $content = File::get($signaturePath);
        $begin = 'filename="smime.p7s"';
        $end = '------';
        $start = strpos($content, $begin);
        if ($start === false) {
            return;
        }
        $start += strlen($begin);
        $content = substr($content, $start);
        $endPos = strpos($content, $end);
        if ($endPos !== false) {
            $content = substr($content, 0, $endPos);
        }
        $content = trim($content);
        $binary = base64_decode($content, true);
        if ($binary !== false) {
            File::put($signaturePath, $binary);
        }
    }

    private function createZip(string $dir): string
    {
        $files = ['pass.json', 'manifest.json', 'signature'];
        foreach (['icon.png', 'icon@2x.png', 'icon@3x.png', 'logo.png', 'logo@2x.png', 'strip.png', 'strip@2x.png'] as $f) {
            if (File::isFile($dir.'/'.$f)) {
                $files[] = $f;
            }
        }

        $zipPath = $dir.'/pass.zip';

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive;
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ZIP の作成に失敗しました。');
            }
            foreach ($files as $name) {
                $zip->addFile($dir.'/'.$name, $name);
            }
            $zip->close();
        } else {
            $this->createZipWithCommand($dir, $zipPath, $files);
        }

        $content = File::get($zipPath);
        @unlink($zipPath);

        return $content;
    }

    /**
     * ZipArchive が無い環境用: システムの zip コマンドで .pkpass 用 ZIP を作成
     */
    private function createZipWithCommand(string $dir, string $zipPath, array $files): void
    {
        $zipName = basename($zipPath);
        $list = implode(' ', array_map('escapeshellarg', $files));
        $cmd = sprintf(
            'cd %s && zip -r -q %s %s',
            escapeshellarg($dir),
            escapeshellarg($zipName),
            $list
        );
        $output = [];
        $ret = 0;
        @exec($cmd.' 2>&1', $output, $ret);
        if ($ret !== 0 || ! File::isFile($zipPath)) {
            throw new \RuntimeException(
                'ZIP の作成に失敗しました。PHP の zip 拡張を有効にするか、システムに zip コマンドをインストールしてください。'
            );
        }
    }
}
