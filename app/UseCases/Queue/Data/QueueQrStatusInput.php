<?php

namespace App\UseCases\Queue\Data;

use App\Models\User;
use Illuminate\Http\Request;

class QueueQrStatusInput
{
    public function __construct(
        public string $token,
        public ?string $headerLineUserId,
        public ?string $queryLineUserId,
        public string $userAgent,
        public ?User $sessionUser,
    ) {}

    public static function fromRequest(Request $request, string $token, ?User $sessionUser): self
    {
        $header = $request->header('X-Line-User-Id');
        $query = $request->query('line_user_id');

        return new self(
            $token,
            is_string($header) ? $header : null,
            is_string($query) ? $query : null,
            (string) $request->userAgent(),
            $sessionUser,
        );
    }
}
