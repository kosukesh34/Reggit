<?php

namespace App\Support;

/**
 * LIFF / Web で指定タブを開く URL を組み立てる。
 */
class LiffTabUrlBuilder
{
    public static function build(int $projectId, string $projectKey, string $tab): string
    {
        $projectKey = trim($projectKey) !== '' ? trim($projectKey) : 'default';
        $tab = trim($tab) !== '' ? trim($tab) : 'home';
        $baseUrl = QueueLiffUrlBuilder::resolveBaseUrl($projectId, $projectKey);

        return self::appendTabParams($baseUrl, $projectKey, $tab);
    }

    private static function appendTabParams(string $baseUrl, string $projectKey, string $tab): string
    {
        $parts = parse_url($baseUrl);
        if ($parts === false) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';

            return $baseUrl.$separator.http_build_query([
                'project_key' => $projectKey,
                'tab' => $tab,
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
                    'tab' => $tab,
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
            ['project_key' => $projectKey, 'tab' => $tab],
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
