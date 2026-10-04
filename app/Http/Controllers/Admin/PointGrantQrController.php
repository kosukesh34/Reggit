<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PointGrantQrRequest;
use App\UseCases\Admin\Qr\IssuePointGrantQrUseCase;
use Illuminate\Http\RedirectResponse;

class PointGrantQrController extends AdminBaseController
{
    public function store(PointGrantQrRequest $request, IssuePointGrantQrUseCase $useCase): RedirectResponse
    {
        $code = $useCase->handle($this->resolveAdminProjectId(), (int) $request->validated('points'), (int) $request->validated('expires_minutes'), (int) $request->validated('usage_quota'));

        return redirect()->route('admin.qr-codes', $this->buildAdminRouteParams())
            ->with('issued_point_code_id', $code->id)->with('success', 'ポイント付与QRを発行しました。画像を保存してご利用ください。');
    }
}
