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
        $header = self::lineUserId($request);
        if ($header !== '') {
            return $header;
        }

        $query = trim((string) ($request->query('line_user_id') ?? ''));
        if ($query !== '') {
            return $query;
        }

        if ($userLineUserId !== null && trim($userLineUserId) !== '') {
            return trim($userLineUserId);
        }

        return '';
    }

    public static function queueToken(Request $request): ?string
    {
        $token = self::normalizeString($request->query('queue_token'));

        return $token !== '' ? $token : null;
    }

    public static function queueId(Request $request): ?int
    {
        $id = self::normalizeString($request->query('queue_id'));
        if ($id !== '' && ctype_digit($id)) {
            return (int) $id;
        }

        return null;
    }

    /**
     * @return list<string|null>
     */
    public static function embeddedRawParams(Request $request): array
    {
        return [
            $request->query('liff.state'),
            $request->query('state'),
            $request->query('liffRedirectUri'),
            $request->headers->get('referer'),
        ];
    }

    private static function normalizeString(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
