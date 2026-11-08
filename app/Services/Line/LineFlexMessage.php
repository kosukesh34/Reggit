<?php

namespace App\Services\Line;

/** 通知で使う「本文 + 緑のリンクボタン」の LINE Flex メッセージ */
final class LineFlexMessage
{
    public const BRAND_COLOR = '#06C755';

    /**
     * @param  list<array<string, mixed>>  $bodyContents
     * @param  array<string, mixed>  $bubbleParts  header / hero など本文・フッター以外の部品
     * @return array{type: string, altText: string, contents: array<string, mixed>}
     */
    public static function withButton(
        string $altText,
        array $bodyContents,
        string $buttonLabel,
        string $uri,
        array $bubbleParts = [],
    ): array {
        return [
            'type' => 'flex',
            'altText' => $altText,
            'contents' => ['type' => 'bubble'] + $bubbleParts + [
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => $bodyContents,
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => $buttonLabel,
                            'uri' => $uri,
                        ],
                        'color' => self::BRAND_COLOR,
                    ]],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> 通知の見出し */
    public static function title(string $text): array
    {
        return ['type' => 'text', 'text' => $text, 'weight' => 'bold', 'size' => 'lg', 'color' => self::BRAND_COLOR];
    }

    /** @return array{hero: array<string, string>} */
    public static function hero(string $imageUrl, string $aspectMode): array
    {
        return ['hero' => [
            'type' => 'image',
            'url' => $imageUrl,
            'size' => 'full',
            'aspectRatio' => '20:13',
            'aspectMode' => $aspectMode,
        ]];
    }
}
