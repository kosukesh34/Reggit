<?php

namespace App\Services;

use App\Models\Member;

/**
 * アプリ会員証（idnect-card）と Apple / Google Wallet で共通の配色・ランク表示を解決する。
 */
class MemberCardAppearanceService
{
    public function __construct(
        private MemberRankCalculator $rankCalculator,
    ) {}

    /**
     * @return array{
     *     rank_key: string,
     *     rank_label: string,
     *     card_color: string|null,
     *     colors: array{bg: string, fg: string, label: string},
     *     gradient_stops: array<int, array{0: float, 1: int, 2: int, 3: int}>,
     *     hex_background: string
     * }
     */
    public function resolveForMember(Member $member): array
    {
        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $rankKey = strtolower((string) ($rankInfo['current_rank'] ?? 'bronze'));
        $rankLabel = (string) ($rankInfo['current_rank_label'] ?? '');
        $cardColor = isset($rankInfo['current_rank_color']) && is_string($rankInfo['current_rank_color'])
            ? trim($rankInfo['current_rank_color'])
            : null;
        if ($cardColor === '') {
            $cardColor = null;
        }

        $gradientStops = $cardColor !== null
            ? $this->gradientStopsFromHex($cardColor)
            : $this->defaultGradientStops($rankKey);

        $bottom = $gradientStops[count($gradientStops) - 1];
        $colors = $this->colorsFromBottomRgb($bottom[1], $bottom[2], $bottom[3], $rankKey, $cardColor);

        return [
            'rank_key' => $rankKey,
            'rank_label' => $rankLabel,
            'card_color' => $cardColor,
            'colors' => $colors,
            'gradient_stops' => $gradientStops,
            'hex_background' => $cardColor ?? $this->defaultHexForRank($rankKey),
        ];
    }

    /**
     * @return array{bg: string, fg: string, label: string}
     */
    private function colorsFromBottomRgb(int $r, int $g, int $b, string $rankKey, ?string $cardColor): array
    {
        $light = $this->isLightColor($r, $g, $b);

        if ($light) {
            return [
                'bg' => sprintf('rgb(%d, %d, %d)', $r, $g, $b),
                'fg' => 'rgb(51, 51, 51)',
                'label' => 'rgb(80, 80, 80)',
            ];
        }

        if ($cardColor !== null) {
            return [
                'bg' => sprintf('rgb(%d, %d, %d)', $r, $g, $b),
                'fg' => 'rgb(255, 255, 255)',
                'label' => sprintf(
                    'rgb(%d, %d, %d)',
                    min(255, $r + 60),
                    min(255, $g + 50),
                    min(255, $b + 40)
                ),
            ];
        }

        $defaults = [
            'bronze' => ['bg' => 'rgb(92, 46, 10)', 'fg' => 'rgb(255, 255, 255)', 'label' => 'rgb(255, 235, 210)'],
            'silver' => ['bg' => 'rgb(144, 144, 144)', 'fg' => 'rgb(33, 33, 33)', 'label' => 'rgb(80, 80, 80)'],
            'gold' => ['bg' => 'rgb(184, 134, 11)', 'fg' => 'rgb(50, 42, 25)', 'label' => 'rgb(255, 248, 220)'],
            'platinum' => ['bg' => 'rgb(168, 168, 176)', 'fg' => 'rgb(35, 40, 50)', 'label' => 'rgb(95, 105, 120)'],
        ];

        return $defaults[$rankKey] ?? $defaults['bronze'];
    }

    /** @return array<int, array{0: float, 1: int, 2: int, 3: int}> */
    private function defaultGradientStops(string $rankKey): array
    {
        $map = [
            'bronze' => [
                [0.00, 201, 139, 74],
                [0.35, 160, 82, 45],
                [0.65, 139, 69, 19],
                [1.00, 92, 46, 10],
            ],
            'silver' => [
                [0.00, 240, 240, 240],
                [0.35, 216, 216, 216],
                [0.65, 192, 192, 192],
                [1.00, 144, 144, 144],
            ],
            'gold' => [
                [0.00, 255, 244, 176],
                [0.35, 255, 215, 0],
                [0.65, 218, 165, 32],
                [1.00, 184, 134, 11],
            ],
            'platinum' => [
                [0.00, 250, 250, 250],
                [0.35, 232, 232, 232],
                [0.65, 212, 212, 216],
                [1.00, 168, 168, 176],
            ],
        ];

        return $map[$rankKey] ?? $map['bronze'];
    }

    /**
     * CSS .idnect-card-wrapper[data-rank-color] と同じ 4 段グラデーション。
     *
     * @return array<int, array{0: float, 1: int, 2: int, 3: int}>
     */
    private function gradientStopsFromHex(string $hex): array
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        [$r0, $g0, $b0] = $this->mixRgb($r, $g, $b, 255, 255, 255, 0.15);
        [$r2, $g2, $b2] = $this->mixRgb($r, $g, $b, 0, 0, 0, 0.10);
        [$r3, $g3, $b3] = $this->mixRgb($r, $g, $b, 0, 0, 0, 0.35);

        return [
            [0.00, $r0, $g0, $b0],
            [0.35, $r, $g, $b],
            [0.65, $r2, $g2, $b2],
            [1.00, $r3, $g3, $b3],
        ];
    }

    private function defaultHexForRank(string $rankKey): string
    {
        return match ($rankKey) {
            'silver' => '#909090',
            'gold' => '#b8860b',
            'platinum' => '#a8a8b0',
            default => '#5c2e0a',
        };
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function mixRgb(int $r1, int $g1, int $b1, int $r2, int $g2, int $b2, float $ratioToSecond): array
    {
        return [
            (int) round($r1 + ($r2 - $r1) * $ratioToSecond),
            (int) round($g1 + ($g2 - $g1) * $ratioToSecond),
            (int) round($b1 + ($b2 - $b1) * $ratioToSecond),
        ];
    }

    private function isLightColor(int $r, int $g, int $b): bool
    {
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) >= 160;
    }
}
