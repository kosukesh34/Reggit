<?php

namespace App\Support;

/**
 * クーポン通知・LINE Flex 用の LIFF / Web リンク（クーポンタブを開く）。
 */
class CouponLiffUrlBuilder
{
    public static function buildCouponsTabUrl(int $projectId, string $projectKey): string
    {
        $projectKey = trim($projectKey) !== '' ? trim($projectKey) : 'default';
        $baseUrl = QueueLiffUrlBuilder::resolveBaseUrl($projectId, $projectKey);

        return self::appendCouponsTabParams($baseUrl, $projectKey);
    }

    private static function appendCouponsTabParams(string $baseUrl, string $projectKey): string
    {
        $projectKey = trim($projectKey) !== '' ? trim($projectKey) : 'default';
        $parts = parse_url($baseUrl);
        if ($parts === false) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';

            return $baseUrl.$separator.http_build_query([
                'project_key' => $projectKey,
                'tab' => 'coupons',
            ]);
        }

        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        if ($host === 'liff.line.me' || $host === 'miniapp.line.me') {
            $path = trim((string) ($parts['path'] ?? ''), '/');
            $segments = explode('/', $path);
            $liffId = trim((string) end($segments));
            if ($liffId !== '') {
                $liffState = http_build_query([
                    'project_key' => $projectKey,
                    'tab' => 'coupons',
                ]);

                return 'https://liff.line.me/'.$liffId.'?'.http_build_query([
                    'liff.state' => $liffState,
                ]);
            }
        }

        $queryParams = [];
        if (isset($parts['query'])) {
            parse_str((string) $parts['query'], $queryParams);
        }
        unset($queryParams['project_key'], $queryParams['tab'], $queryParams['liff.state']);
        $orderedParams = array_merge(
            ['project_key' => $projectKey, 'tab' => 'coupons'],
            $queryParams
        );
        $query = http_build_query($orderedParams);

        $uri = '';
        if (isset($parts['scheme'])) {
            $uri .= $parts['scheme'].'://';
        }
        if (isset($parts['host'])) {
            $uri .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $uri .= ':'.$parts['port'];
        }
        $uri .= $parts['path'] ?? '';
        if ($query !== '') {
            $uri .= '?'.$query;
        }
        if (isset($parts['fragment'])) {
            $uri .= '#'.$parts['fragment'];
        }

        return $uri;
    }
}
