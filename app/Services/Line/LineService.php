<?php

namespace App\Services\Line;

use App\Util\LogHelper;
use App\Util\StrHelper;
use GuzzleHttp\Client;
use RuntimeException;

class LineService
{
    private Client $client;

    private ?string $channelAccessToken;

    private ?string $channelSecret;

    public function __construct()
    {
        $this->channelAccessToken = env('LINE_CHANNEL_ACCESS_TOKEN');
        $this->channelSecret = env('LINE_CHANNEL_SECRET');
        $this->client = new Client([
            'base_uri' => 'https://api.line.me/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . ($this->channelAccessToken ?? ''),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function verifySignature(string $body, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }
        $this->ensureConfigured(true);
        $hash = hash_hmac('sha256', $body, $this->channelSecret ?? '', true);
        $expectedSignature = base64_encode($hash);

        return hash_equals($expectedSignature, $signature);
    }

    /** @param array<int, array{type: string, text?: string}>|array{type: string, text?: string} $messages */
    public function replyMessage(string $replyToken, array $messages): array
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

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Exception $e) {
            LogHelper::error('[LINE] reply failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** @param array<int, array{type: string, text?: string}>|array{type: string, text?: string} $messages */
    public function pushMessage(string $userId, array $messages): array
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

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Exception $e) {
            LogHelper::error('[LINE] push failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** @return array<string, mixed>|null */
    public function getProfile(string $userId): ?array
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

    /** @param array<string, mixed> $contents */
    public function createFlexMessage(string $altText, array $contents): array
    {
        return [
            'type' => 'flex',
            'altText' => $altText,
            'contents' => $contents,
        ];
    }

    public function createTextMessage(string $text): array
    {
        return [
            'type' => 'text',
            'text' => $text,
        ];
    }

    /** @param array<int, array<string, mixed>> $items */
    public function createQuickReplyMessage(string $text, array $items): array
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
        if (StrHelper::isEmptyStringOrNull($this->channelAccessToken)) {
            throw new RuntimeException('LINE Messaging API access token is not configured.');
        }
        if ($requireSecret && StrHelper::isEmptyStringOrNull($this->channelSecret)) {
            throw new RuntimeException('LINE Messaging API credentials are not configured.');
        }
    }

    /** @param array<int, array{type: string, text?: string}>|array{type: string, text?: string} $messages */
    private function normalizeMessages($messages): array
    {
        if (! is_array($messages)) {
            return [$messages];
        }

        if (array_key_exists('type', $messages)) {
            return [$messages];
        }

        return $messages;
    }
}
