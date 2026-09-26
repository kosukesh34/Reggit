<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StampGrantLimit;
use App\UseCases\Admin\Stamp\AdminStampGrantQrIndexUseCase;
use App\UseCases\Admin\Stamp\AdminStampGrantQrStoreUseCase;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StampGrantQrController extends AdminBaseController
{
    public function __construct(
        private AdminStampGrantQrIndexUseCase $indexUseCase,
        private AdminStampGrantQrStoreUseCase $storeUseCase,
    ) {}

    public function show(Request $request, string $projectKey, int $id): View
    {
        $output = $this->indexUseCase->handle(
            $this->resolveAdminProjectId(),
            $id,
            (int) ($request->query('expires_minutes') ?? StampGrantLimit::DEFAULT_EXPIRES_MINUTES),
            (int) ($request->query('amount') ?? 1),
            (string) $request->query('limit', ''),
            (int) ($request->query('quota') ?? StampGrantLimit::DEFAULT_QUOTA),
            (int) $request->session()->get('issued_grant_code_id', 0),
        );

        return view('admin.stamps.grant-qr', ['view' => $output]);
    }

    public function store(Request $request, string $projectKey, int $id): RedirectResponse
    {
        $code = $this->storeUseCase->handle(new AdminStampGrantQrInput(
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
}
