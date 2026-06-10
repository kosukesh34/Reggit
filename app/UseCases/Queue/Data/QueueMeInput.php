<?php

namespace App\UseCases\Queue\Data;

use App\Models\User;
use Illuminate\Http\Request;

class QueueMeInput
{
    /**
     * @param  list<string|null>  $embeddedRawParams
     */
    public function __construct(
        public User $user,
        public string $lineUserId,
        public ?string $queueToken,
        public ?string $queueId,
        public array $embeddedRawParams,
    ) {}

    public static function fromRequest(Request $request, User $user): self
    {
        return new self(
            $user,
            (string) $request->header('X-Line-User-Id', ''),
            self::normalizeQuery($request->query('queue_token')),
            self::normalizeQuery($request->query('queue_id')),
            [
                self::normalizeQuery($request->query('liff.state')),
                self::normalizeQuery($request->query('state')),
                self::normalizeQuery($request->query('liffRedirectUri')),
                is_string($request->headers->get('referer')) ? $request->headers->get('referer') : null,
            ],
        );
    }

    private static function normalizeQuery(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
