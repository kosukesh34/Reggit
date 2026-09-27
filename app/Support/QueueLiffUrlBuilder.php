<?php

namespace App\Support;

use App\Domain\Settings\Repositories\LineConnectionSettings;

/**
 * 順番待ち QR / LINE 通知用の LIFF リンクを組み立てる。
 * iOS WaitingSystem の LiffURLResolver + URL+Query と同等のルール。
 */
class QueueLiffUrlBuilder
{
    public static function build(int $projectId, string $projectKey, ?string $qrToken, ?int $queueId = null): string
    {
        $projectKey = ProjectKey::require($projectKey);
        $token = trim((string) ($qrToken ?? ''));
        $id = $queueId !== null && $queueId > 0 ? $queueId : null;

        $baseUrl = self::resolveBaseUrl($projectId, $projectKey);

        if ($token !== '') {
            return self::appendQueueParams($baseUrl, $projectKey, 'queue_token', $token);
        }

        return self::appendQueueParams($baseUrl, $projectKey, 'queue_id', (string) ($id ?? ''));
    }

    /**
     * Wallet 追加後など、アプリの LIFF エントリ URL（project_key のみ）を返す。
     */
    public static function buildEntryUrl(int $projectId, string $projectKey): string
    {
        $projectKey = ProjectKey::require($projectKey);
        $repo = app(LineConnectionSettings::class);
        $liffId = trim($repo->getLineLiffId($projectId));
        if ($liffId !== '') {
            return 'https://liff.line.me/'.$liffId.'?'.http_build_query([
                'liff.state' => http_build_query(['project_key' => $projectKey]),
            ]);
        }

        $baseUrl = self::resolveBaseUrl($projectId, $projectKey);
        $parts = parse_url($baseUrl);
        if ($parts !== false) {
            $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
            if ($host === 'liff.line.me' || $host === 'miniapp.line.me') {
                $resolvedLiffId = self::extractLiffId($parts);
                if ($resolvedLiffId !== '') {
                    return 'https://liff.line.me/'.$resolvedLiffId.'?'.http_build_query([
                        'liff.state' => http_build_query(['project_key' => $projectKey]),
                    ]);
                }
            }
        }

        return url('/liff/'.$projectKey);
    }

    public static function resolveBaseUrl(int $projectId, string $projectKey): string
    {
        $projectKey = ProjectKey::require($projectKey);
        $repo = app(LineConnectionSettings::class);
        $liffUrl = trim($repo->getLineLiffUrl($projectId));
        if ($liffUrl !== '') {
            return self::normalizeLiffWebBase($liffUrl) ?? $liffUrl;
        }

        $liffId = trim($repo->getLineLiffId($projectId));
        if ($liffId !== '') {
            return 'https://liff.line.me/'.$liffId;
        }

        return url('/liff/'.$projectKey);
    }

    /**
     * @param  'queue_token'|'queue_id'  $queueParamName
     */
    public static function appendQueueParams(
        string $baseUrl,
        string $projectKey,
        string $queueParamName,
        string $queueParamValue
    ): string {
        $projectKey = ProjectKey::require($projectKey);
        $queueParamValue = trim($queueParamValue);
        if ($queueParamValue === '') {
            return $baseUrl;
        }

        $normalizedBase = self::normalizeLiffWebBase($baseUrl) ?? $baseUrl;
        $parts = parse_url($normalizedBase);
        if ($parts === false) {
            return $normalizedBase;
        }

        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        if ($host === 'liff.line.me' || $host === 'miniapp.line.me') {
            $liffId = self::extractLiffId($parts);
            if ($liffId === '') {
                return self::buildWebUrl($normalizedBase, $projectKey, $queueParamName, $queueParamValue);
            }

            $liffState = http_build_query([
                'project_key' => $projectKey,
                $queueParamName => $queueParamValue,
            ]);

            return 'https://liff.line.me/'.$liffId.'?'.http_build_query([
                'liff.state' => $liffState,
            ]);
        }

        return self::buildWebUrl($normalizedBase, $projectKey, $queueParamName, $queueParamValue);
    }

    private static function normalizeLiffWebBase(string $liffUrl): ?string
    {
        $parts = parse_url(trim($liffUrl));
        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if ($host !== 'liff.line.me' && $host !== 'miniapp.line.me') {
            return null;
        }

        $liffId = self::extractLiffId($parts);
        if ($liffId === '') {
            return null;
        }

        return 'https://liff.line.me/'.$liffId;
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private static function extractLiffId(array $parts): string
    {
        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);

        return trim((string) end($segments));
    }

    private static function buildWebUrl(
        string $baseUrl,
        string $projectKey,
        string $queueParamName,
        string $queueParamValue
    ): string {
        $parts = parse_url($baseUrl);
        if ($parts === false) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';

            return $baseUrl.$separator.http_build_query([
                'project_key' => $projectKey,
                $queueParamName => $queueParamValue,
            ]);
        }

        $queryParams = [];
        if (isset($parts['query'])) {
            parse_str((string) $parts['query'], $queryParams);
        }

        unset(
            $queryParams['project_key'],
            $queryParams['queue_token'],
            $queryParams['queue_id'],
            $queryParams['liff.state']
        );

        $orderedParams = array_merge(
            ['project_key' => $projectKey, $queueParamName => $queueParamValue],
            $queryParams
        );
        $query = http_build_query($orderedParams);

        $uri = '';
        if (isset($parts['scheme'])) {
            $uri .= $parts['scheme'].'://';
        }
        if (isset($parts['user'])) {
            $uri .= $parts['user'];
            if (isset($parts['pass'])) {
                $uri .= ':'.$parts['pass'];
            }
            $uri .= '@';
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
