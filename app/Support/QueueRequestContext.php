<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * 順番待ち API の HTTP リクエストからコンテキスト値を抽出する。
 */
class QueueRequestContext
{
    public static function lineUserId(Request $request): string
    {
        return trim((string) ($request->header('X-Line-User-Id') ?? ''));
    }

    public static function resolveLineUserId(Request $request, ?string $userLineUserId): string
    {
        if ($userLineUserId !== null && trim($userLineUserId) !== '') {
            return trim($userLineUserId);
        }
        if (LineIdentityPolicy::requiresVerifiedToken()) {
            return '';
        }

        $header = self::lineUserId($request);
        if ($header !== '') {
            return $header;
        }

        $query = trim((string) ($request->query('line_user_id') ?? ''));
        if ($query !== '') {
            return $query;
        }

        return '';
    }

    public static function queueToken(Request $request): ?string
    {
        $token = self::normalizeString($request->query('queue_token'));
        if ($token !== '') {
            return $token;
        }
        foreach (self::embeddedRawParams($request) as $raw) {
            [$embeddedToken] = self::extractQueueIdentifierFromRaw($raw);
            if ($embeddedToken !== null) {
                return $embeddedToken;
            }
        }

        return null;
    }

    public static function queueId(Request $request): ?int
    {
        $id = self::normalizeString($request->query('queue_id'));
        if ($id !== '' && ctype_digit($id)) {
            return (int) $id;
        }
        foreach (self::embeddedRawParams($request) as $raw) {
            [, $embeddedId] = self::extractQueueIdentifierFromRaw($raw);
            if ($embeddedId !== null) {
                return $embeddedId;
            }
        }

        return null;
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    public static function extractQueueIdentifierFromRaw(?string $raw): array
    {
        if ($raw === null) {
            return [null, null];
        }

        $candidates = [];
        $value = trim($raw);
        if ($value !== '') {
            $candidates[] = $value;
            $decoded = $value;
            for ($i = 0; $i < 2; $i++) {
                $next = urldecode($decoded);
                if ($next === $decoded) {
                    break;
                }
                $decoded = $next;
                $candidates[] = $decoded;
            }
        }

        foreach ($candidates as $candidate) {
            [$token, $id] = self::extractQueueIdentifierFromString($candidate);
            if ($token !== null || $id !== null) {
                return [$token, $id];
            }
        }

        return [null, null];
    }

    /**
     * @return list<string>
     */
    private static function embeddedRawParams(Request $request): array
    {
        $raws = [];
        foreach ([
            $request->query('liff.state'),
            $request->query('liff_state'),
            $request->query('state'),
            $request->query('liffRedirectUri'),
            $request->headers->get('referer'),
        ] as $value) {
            if (is_string($value) && trim($value) !== '') {
                $raws[] = $value;
            }
        }

        return $raws;
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private static function extractQueueIdentifierFromString(string $value): array
    {
        $normalized = ltrim($value, '#');
        $params = [];

        $parts = parse_url($normalized);
        if (is_array($parts)) {
            if (isset($parts['query'])) {
                parse_str($parts['query'], $params);
            } elseif (isset($parts['fragment'])) {
                parse_str($parts['fragment'], $params);
            }
        }

        if ($params === []) {
            $query = str_contains($normalized, '?')
                ? (string) parse_url($normalized, PHP_URL_QUERY)
                : $normalized;
            parse_str((string) $query, $params);
        }

        $token = isset($params['queue_token']) && is_string($params['queue_token'])
            ? trim($params['queue_token'])
            : null;
        if ($token === '') {
            $token = null;
        }

        $queueIdRaw = isset($params['queue_id']) && is_string($params['queue_id'])
            ? trim($params['queue_id'])
            : '';
        $queueId = ctype_digit($queueIdRaw) ? (int) $queueIdRaw : null;

        return [$token, $queueId];
    }

    private static function normalizeString(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
