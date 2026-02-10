<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    public function created(Project $project): void
    {
        // reserved for future hooks
    }

    public function updated(Project $project): void
    {
        // reserved for future hooks
    }
}
