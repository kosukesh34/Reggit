<?php

namespace App\Support;

/**
 * 順番待ち QR の中間ページ・ネイティブ起動 URL を組み立てる。
 */
class QueueLaunchUrlBuilder
{
    public static function buildOpenUrl(string $projectKey, string $token): string
    {
        $projectKey = trim($projectKey) !== '' ? trim($projectKey) : 'default';
        $token = trim($token);

        return url('/queue/open/'.$projectKey.'/'.$token);
    }

    public static function buildNativeSchemeUrl(string $projectKey, string $token): string
    {
        $projectKey = trim($projectKey) !== '' ? trim($projectKey) : 'default';
        $token = trim($token);

        return NativeApp::urlScheme().'://queue?'.http_build_query([
            'project_key' => $projectKey,
            'queue_token' => $token,
        ]);
    }

    public static function buildNativeWebUrl(string $projectKey, string $token): string
    {
        $projectKey = trim($projectKey) !== '' ? trim($projectKey) : 'default';
        $token = trim($token);

        return url('/liff/'.$projectKey.'?'.http_build_query([
            'tab' => 'queue',
            'queue_token' => $token,
        ]));
    }
}
