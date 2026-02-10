<?php

namespace App\Observers;

use App\Models\MemberStamp;

class MemberStampObserver
{
    public function saved(MemberStamp $memberStamp): void
    {
        // reserved for future hooks
    }
}
