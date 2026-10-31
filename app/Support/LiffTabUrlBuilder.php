<?php

namespace App\Support;

/**
 * LIFF / Web で指定タブを開く URL を組み立てる。
 */
class LiffTabUrlBuilder
{
    public function __construct(private QueueLiffUrlBuilder $queueUrls) {}

    /**
     * @param  array<string, string>  $extra
     */
    public function build(int $projectId, string $projectKey, string $tab, array $extra = []): string
    {
        $projectKey = ProjectKey::require($projectKey);
        $tab = trim($tab) !== '' ? trim($tab) : 'home';
        $baseUrl = $this->queueUrls->resolveBaseUrl($projectId, $projectKey);

        return self::appendTabParams($baseUrl, $projectKey, $tab, $extra);
    }

    /**
     * @param  array<string, string>  $extra
     */
    public static function buildFromBaseUrl(string $baseUrl, string $projectKey, string $tab, array $extra = []): string
    {
        return self::appendTabParams($baseUrl, ProjectKey::require($projectKey), $tab, $extra);
    }

    /** @param array<string, string> $extra */
    private static function appendTabParams(string $baseUrl, string $projectKey, string $tab, array $extra = []): string
    {
        $state = array_merge(['project_key' => $projectKey, 'tab' => $tab], $extra);
        $parts = parse_url($baseUrl);
        if ($parts === false) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';

            return $baseUrl.$separator.http_build_query($state);
        }

        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        if ($host === 'liff.line.me' || $host === 'miniapp.line.me') {
            $path = trim((string) ($parts['path'] ?? ''), '/');
            $segments = explode('/', $path);
            $liffId = trim((string) end($segments));
            if ($liffId !== '') {
                return 'https://liff.line.me/'.$liffId.'?'.http_build_query([
                    'liff.state' => http_build_query($state),
                ]);
            }
        }

        $queryParams = [];
        if (isset($parts['query'])) {
            parse_str((string) $parts['query'], $queryParams);
        }
        unset($queryParams['project_key'], $queryParams['tab'], $queryParams['liff.state']);
        foreach (array_keys($extra) as $extraKey) {
            unset($queryParams[$extraKey]);
        }
        $orderedParams = array_merge($state, $queryParams);
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
