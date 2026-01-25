<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

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
        $hash = hash_hmac('sha256', $body, $this->channelSecret, true);
        $expectedSignature = base64_encode($hash);
        return hash_equals($expectedSignature, $signature);
    }

    public function replyMessage($replyToken, $messages)
    {
        try {
            $response = $this->client->post('bot/message/reply', [
                'json' => [
                    'replyToken' => $replyToken,
                    'messages' => is_array($messages) ? $messages : [$messages],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('LINE Reply Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function pushMessage($userId, $messages)
    {
        try {
            $response = $this->client->post('bot/message/push', [
                'json' => [
                    'to' => $userId,
                    'messages' => is_array($messages) ? $messages : [$messages],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('LINE Push Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getProfile($userId)
    {
        try {
            $response = $this->client->get("bot/profile/{$userId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('LINE Profile Error: ' . $e->getMessage());
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
}

