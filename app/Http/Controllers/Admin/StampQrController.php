<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StampQrRequest;
use App\UseCases\Admin\Stamp\AdminStampGrantQrStoreUseCase;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrInput;
use Illuminate\Http\RedirectResponse;

class StampQrController extends AdminBaseController
{
    public function store(StampQrRequest $request, AdminStampGrantQrStoreUseCase $useCase): RedirectResponse
    {
        $code = $useCase->handle(new AdminStampGrantQrInput(
            $this->resolveAdminProjectId(),
            (int) $request->validated('stamp_card_id'),
            (int) $request->validated('expires_minutes'),
            (int) $request->validated('amount'),
            (string) $request->validated('limit'),
            (int) $request->validated('quota'),
        ));

        return redirect()->route('admin.qr-codes', $this->buildAdminRouteParams())
            ->with('issued_stamp_code_id', $code->id)->with('success', 'スタンプ付与QRを発行しました。画像を保存してご利用ください。');
    }
}
