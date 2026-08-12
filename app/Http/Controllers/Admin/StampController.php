<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Http\Requests\Admin\StampStoreRequest;
use App\Http\Requests\Admin\StampUpdateRequest;
use App\Models\StampCard;
use App\UseCases\Admin\Stamp\AdminStampDeleteUseCase;
use App\UseCases\Admin\Stamp\AdminStampEditUseCase;
use App\UseCases\Admin\Stamp\AdminStampIndexUseCase;
use App\UseCases\Admin\Stamp\AdminStampStoreUseCase;
use App\UseCases\Admin\Stamp\AdminStampUpdateUseCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StampController extends AdminBaseController
{
    private AdminStampIndexUseCase $indexUseCase;

    private AdminStampStoreUseCase $storeUseCase;

    private AdminStampEditUseCase $editUseCase;

    private AdminStampUpdateUseCase $updateUseCase;

    private AdminStampDeleteUseCase $deleteUseCase;

    public function __construct(
        AdminStampIndexUseCase $indexUseCase,
        AdminStampStoreUseCase $storeUseCase,
        AdminStampEditUseCase $editUseCase,
        AdminStampUpdateUseCase $updateUseCase,
        AdminStampDeleteUseCase $deleteUseCase,
        private ShopSettingRepository $shopSettingRepository,
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editUseCase = $editUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
    }

    public function stamps()
    {
        $stampCards = $this->indexUseCase->handle();

        return view('admin.stamps.index', compact('stampCards'));
    }

    public function createStamp()
    {
        return view('admin.stamps.create');
    }

    public function grantQr(string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $stampCard = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->findOrFail($id);
        $nonce = Str::random(40);
        $expiresAt = now()->addMinutes(15);
        $grantUrl = URL::temporarySignedRoute('api.stamps.grant', $expiresAt, [
            'stampCard' => $stampCard->id,
            'nonce' => $nonce,
            'project_key' => $projectKey,
        ]);
        $configuredLiffUrl = trim($this->shopSettingRepository->getLineLiffUrl($projectId));
        $liffBaseUrl = $configuredLiffUrl !== '' ? $configuredLiffUrl : url('/liff/'.$projectKey);
        $liffBaseUrl = preg_replace('/#.*$/', '', $liffBaseUrl) ?: url('/liff/'.$projectKey);
        $stampUrl = $liffBaseUrl.'#/stamp?grant='.rawurlencode($grantUrl);

        return view('admin.stamps.grant-qr', compact('stampCard', 'stampUrl', 'expiresAt'));
    }

    public function storeStamp(StampStoreRequest $request)
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを追加しました');
    }

    public function editStamp(string $projectKey, $id)
    {
        $stampCard = $this->editUseCase->handle((int) $id);

        return view('admin.stamps.edit', compact('stampCard'));
    }

    public function updateStamp(StampUpdateRequest $request, string $projectKey, $id)
    {
        $this->updateUseCase->handle((int) $id, $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを更新しました');
    }

    public function deleteStamp(string $projectKey, $id)
    {
        $this->deleteUseCase->handle((int) $id);

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを削除しました');
    }
}
