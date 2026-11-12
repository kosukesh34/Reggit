<?php

namespace App\Support;

/**
 * 順番待ち QR の中間ページ・ネイティブ起動 URL を組み立てる。
 */
class QueueLaunchUrlBuilder
{
    public static function buildNativeSchemeUrl(string $projectKey, string $token): string
    {
        $projectKey = ProjectKey::require($projectKey);
        $token = trim($token);

        return NativeApp::urlScheme().'://queue?'.http_build_query([
            'project_key' => $projectKey,
            'queue_token' => $token,
        ]);
    }
}
