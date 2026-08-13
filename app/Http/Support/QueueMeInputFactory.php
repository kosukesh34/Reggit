<?php

namespace App\Http\Support;

use App\Models\User;
use App\Support\QueueRequestContext;
use App\Support\QueueSessionStore;
use App\UseCases\Queue\Data\QueueMeInput;
use App\Util\AdminContext;
use Illuminate\Http\Request;

class QueueMeInputFactory
{
    public function __construct(
        private QueueSessionStore $sessionStore,
    ) {}

    public function fromRequest(Request $request, User $user): QueueMeInput
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;

        return new QueueMeInput(
            $user,
            $projectId,
            QueueRequestContext::queueToken($request),
            QueueRequestContext::queueId($request),
            $this->sessionStore->getEntryId($request, $projectId),
        );
    }
}
