<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\ShopOperations;
use App\Support\ShopMemberSession;
use App\UseCases\Shop\ShowShopPageUseCase;
use App\Util\AdminContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(
        private ShowShopPageUseCase $showShopPageUseCase,
        private ShopMemberSession $session,
    ) {}

    public function legal(Request $request, ShopOperations $operations): View
    {
        $project = AdminContext::getCurrentProject();
        abort_unless($project !== null, 404);
        $projectKey = (string) ($request->route('project_key') ?? '');

        return view('frontend.shop-legal', [
            'projectName' => (string) $project->name,
            'notice' => $operations->legalNotice((int) $project->id),
            'labels' => ShopOperations::LEGAL_FIELDS,
            'shopUrl' => $projectKey !== '' ? route('shop.show', ['project_key' => $projectKey]) : route('shop.domain.show'),
        ]);
    }

    public function show(Request $request): View|RedirectResponse
    {
        $projectKey = (string) ($request->route('project_key') ?? '');
        // Web で作成したユーザーは、会員登録（必須項目の入力）を済ませてから使う
        if ($this->session->current()?->member()->exists() === false) {
            return redirect()->to($projectKey !== ''
                ? route('shop.member.register', ['project_key' => $projectKey])
                : route('shop.domain.member.register'));
        }
        $loginUrl = $projectKey !== ''
            ? route('shop.login', ['project_key' => $projectKey])
            : route('shop.domain.login');
        $logoutUrl = $projectKey !== ''
            ? route('shop.logout', ['project_key' => $projectKey])
            : route('shop.domain.logout');
        $view = $this->showShopPageUseCase->handle($loginUrl, $logoutUrl, $projectKey);

        return view('frontend.shop', [
            'shopPage' => $view,
            'projectKey' => $view->projectKey,
            'projectName' => $view->projectName,
        ]);
    }
}
