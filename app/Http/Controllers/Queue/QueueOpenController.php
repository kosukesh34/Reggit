<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueOpenUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QueueOpenController extends Controller
{
    public function __construct(
        private QueueOpenUseCase $queueOpenUseCase,
    ) {}

    public function __invoke(string $project_key, string $token): View|RedirectResponse
    {
        $view = $this->queueOpenUseCase->handle($project_key, $token);

        if ($view->mode === 'line' && $view->lineUrl !== null) {
            return redirect()->away($view->lineUrl);
        }

        return view('queue.open', ['view' => $view]);
    }
}
