<?php

namespace App\Services\Line;

use App\Util\LogHelper;
use App\Util\StrHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * LINE LIFF Server API（管理操作）。
 */
class LineLiffAdminService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.line.me/',
            'timeout' => 10,
        ]);
    }

    /**
     * LIFF Module mode を有効化し、ヘッダーのアクションメニューを非表示にする。
     */
    public function hideActionMenu(string $liffId, ?string $channelAccessToken = null): void
    {
        $liffId = trim($liffId);
        if ($liffId === '') {
            return;
        }

        $token = trim((string) ($channelAccessToken ?? ''));
        if ($token === '') {
            $token = trim((string) (config('line.channel_access_token') ?: env('LINE_CHANNEL_ACCESS_TOKEN', '')));
        }
        if (StrHelper::isEmptyStringOrNull($token)) {
            LogHelper::warning('[LINE LIFF] skip module mode: channel access token is not configured', [
                'liff_id' => $liffId,
            ]);

            return;
        }

        try {
            $this->client->put('liff/v1/apps/'.$liffId, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'view' => [
                        'moduleMode' => true,
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            LogHelper::warning('[LINE LIFF] failed to enable module mode', [
                'liff_id' => $liffId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
