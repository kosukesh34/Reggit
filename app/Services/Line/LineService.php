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
        $this->channelAccessToken = config('line.channel_access_token') ?: null;
        $this->channelSecret = config('line.channel_secret') ?: null;
        $this->client = new Client([
            'base_uri' => 'https://api.line.me/v2/',
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer '.($this->channelAccessToken ?? ''),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function verifySignature(string $body, ?string $signature): bool
    {
        return $this->verifySignatureWithSecret($body, $signature, $this->channelSecret);
    }

    public function verifySignatureWithSecret(string $body, ?string $signature, ?string $secret): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }
        $trimmedSecret = trim((string) $secret);
        if ($trimmedSecret === '') {
            return false;
        }
        $hash = hash_hmac('sha256', $body, $trimmedSecret, true);

        return hash_equals(base64_encode($hash), $signature);
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
    public function pushMessage(string $userId, array $messages, ?string $channelAccessToken = null): array
    {
        $token = $this->resolveAccessToken($channelAccessToken);
        $this->ensureTokenConfigured($token);
        $normalizedMessages = $this->normalizeMessages($messages);

        try {
            $response = $this->client->post('bot/message/push', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
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
        $this->ensureTokenConfigured($this->resolveAccessToken(null));
        if ($requireSecret && StrHelper::isEmptyStringOrNull($this->channelSecret)) {
            throw new RuntimeException('LINE Messaging API credentials are not configured.');
        }
    }

    private function ensureTokenConfigured(?string $token): void
    {
        if (StrHelper::isEmptyStringOrNull($token)) {
            throw new RuntimeException('LINE Messaging API access token is not configured.');
        }
    }

    private function resolveAccessToken(?string $channelAccessToken): ?string
    {
        $token = trim((string) ($channelAccessToken ?? ''));
        if ($token !== '') {
            return $token;
        }

        $fallback = trim((string) ($this->channelAccessToken ?? ''));

        return $fallback !== '' ? $fallback : null;
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
