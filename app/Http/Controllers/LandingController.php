<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ProjectRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $project = $this->projectRepository->findActiveByCustomShopDomain($request->getHost());
        if ($project !== null) {
            return redirect('/shop');
        }

        return view('landing');
    }
}
