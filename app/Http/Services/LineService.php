<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use App\Util\LogHelper;
use RuntimeException;

class LineService
{
    private $client;
    private $channelAccessToken;
    private $channelSecret;

    public function __construct()
    {
        $this->channelAccessToken = env('LINE_CHANNEL_ACCESS_TOKEN');
        $this->channelSecret = env('LINE_CHANNEL_SECRET');
        $this->client = new Client([
            'base_uri' => 'https://api.line.me/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->channelAccessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function verifySignature($body, $signature)
    {
        $this->ensureConfigured(true);
        $hash = hash_hmac('sha256', $body, $this->channelSecret, true);
        $expectedSignature = base64_encode($hash);
        return hash_equals($expectedSignature, $signature);
    }

    public function replyMessage($replyToken, $messages)
    {
        $this->ensureConfigured();
        $normalizedMessages = $this->normalizeMessages($messages);

        try {
            $response = $this->client->post('bot/message/reply', [
                'json' => [
                    'replyToken' => $replyToken,
                    'messages' => $normalizedMessages,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            LogHelper::error('[LINE] reply failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function pushMessage($userId, $messages)
    {
        $this->ensureConfigured();
        $normalizedMessages = $this->normalizeMessages($messages);

        try {
            $response = $this->client->post('bot/message/push', [
                'json' => [
                    'to' => $userId,
                    'messages' => $normalizedMessages,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            LogHelper::error('[LINE] push failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getProfile($userId)
    {
        try {
            $response = $this->client->get("bot/profile/{$userId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            LogHelper::error('[LINE] profile failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function createFlexMessage($altText, $contents)
    {
        return [
            'type' => 'flex',
            'altText' => $altText,
            'contents' => $contents,
        ];
    }

    public function createTextMessage($text)
    {
        return [
            'type' => 'text',
            'text' => $text,
        ];
    }

    public function createQuickReplyMessage($text, $items)
    {
        return [
            'type' => 'text',
            'text' => $text,
            'quickReply' => [
                'items' => $items,
            ],
        ];
    }

    private function ensureConfigured(bool $requireSecret = false): void
    {
        if (\App\Util\StrHelper::isEmptyStringOrNull($this->channelAccessToken ?? null)) {
            throw new RuntimeException('LINE Messaging API access token is not configured.');
        }
        if ($requireSecret && \App\Util\StrHelper::isEmptyStringOrNull($this->channelSecret ?? null)) {
            throw new RuntimeException('LINE Messaging API credentials are not configured.');
        }
    }

    private function normalizeMessages($messages): array
    {
        if (!is_array($messages)) {
            return [$messages];
        }

        // 単一メッセージ連想配列（例: ['type' => 'text', 'text' => '...']）
        if (array_key_exists('type', $messages)) {
            return [$messages];
        }

        // 複数メッセージ配列
        return $messages;
    }
}
