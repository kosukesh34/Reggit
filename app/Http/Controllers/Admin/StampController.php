<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StampGrantLimit;
use App\Http\Requests\Admin\StampStoreRequest;
use App\Http\Requests\Admin\StampUpdateRequest;
use App\UseCases\Admin\Stamp\AdminStampCreateFormUseCase;
use App\UseCases\Admin\Stamp\AdminStampDeleteUseCase;
use App\UseCases\Admin\Stamp\AdminStampEditUseCase;
use App\UseCases\Admin\Stamp\AdminStampGrantQrIndexUseCase;
use App\UseCases\Admin\Stamp\AdminStampGrantQrStoreUseCase;
use App\UseCases\Admin\Stamp\AdminStampIndexUseCase;
use App\UseCases\Admin\Stamp\AdminStampStoreUseCase;
use App\UseCases\Admin\Stamp\AdminStampUpdateUseCase;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrInput;
use Illuminate\Http\Request;

class StampController extends AdminBaseController
{
    public function __construct(
        private AdminStampIndexUseCase $indexUseCase,
        private AdminStampCreateFormUseCase $createFormUseCase,
        private AdminStampStoreUseCase $storeUseCase,
        private AdminStampEditUseCase $editUseCase,
        private AdminStampUpdateUseCase $updateUseCase,
        private AdminStampDeleteUseCase $deleteUseCase,
    ) {}

    public function stamps()
    {
        $stampCards = $this->indexUseCase->handle();

        return view('admin.stamps.index', compact('stampCards'));
    }

    public function createStamp()
    {
        $output = $this->createFormUseCase->handle($this->resolveAdminProjectId());

        return view('admin.stamps.create', [
            'coupons' => $output->coupons,
            'isGachaEnabled' => $output->isGachaEnabled,
            'cycleTargetCards' => $output->cycleTargetCards,
        ]);
    }

    public function grantQr(Request $request, string $projectKey, int $id, AdminStampGrantQrIndexUseCase $useCase)
    {
        $output = $useCase->handle(
            $this->resolveAdminProjectId(),
            $id,
            (int) ($request->query('expires_minutes') ?? StampGrantLimit::DEFAULT_EXPIRES_MINUTES),
            (int) ($request->query('amount') ?? 1),
            (string) $request->query('limit', ''),
            (int) ($request->query('quota') ?? StampGrantLimit::DEFAULT_QUOTA),
            (int) $request->session()->get('issued_grant_code_id', 0),
        );

        return view('admin.stamps.grant-qr', [
            'stampCard' => $output->stampCard,
            'codes' => $output->codes,
            'expiresMinutes' => $output->expiresMinutes,
            'amount' => $output->amount,
            'limit' => $output->limit,
            'quota' => $output->quota,
            'issuedCode' => $output->issuedCode,
        ]);
    }

    public function storeGrantQr(Request $request, string $projectKey, int $id, AdminStampGrantQrStoreUseCase $useCase)
    {
        $code = $useCase->handle(new AdminStampGrantQrInput(
            $this->resolveAdminProjectId(),
            $id,
            (int) ($request->input('expires_minutes') ?? StampGrantLimit::DEFAULT_EXPIRES_MINUTES),
            (int) ($request->input('amount') ?? 1),
            (string) $request->input('limit', ''),
            (int) ($request->input('quota') ?? 1),
        ));

        return redirect()
            ->route('admin.stamps.grant-qr', array_merge($this->buildAdminRouteParams(), ['id' => $id]))
            ->with('success', 'スタンプ付与QRを発行しました。この画面を離れるとQRは表示されません。')
            ->with('issued_grant_code_id', $code->id);
    }

    public function storeStamp(StampStoreRequest $request)
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを追加しました');
    }

    public function editStamp(string $projectKey, $id)
    {
        $output = $this->editUseCase->handle($this->resolveAdminProjectId(), (int) $id);

        return view('admin.stamps.edit', [
            'stampCard' => $output->stampCard,
            'coupons' => $output->coupons,
            'isGachaEnabled' => $output->isGachaEnabled,
            'cycleTargetCards' => $output->cycleTargetCards,
        ]);
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
