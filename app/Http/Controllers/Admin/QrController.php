<?php

namespace App\Http\Controllers\Admin;

use App\Support\AppScreenCatalog;
use App\UseCases\Admin\AcquisitionSource\AdminAcquisitionSourceIndexUseCase;
use App\UseCases\Admin\Qr\QrIndexUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrController extends AdminBaseController
{
    public function __invoke(Request $request, QrIndexUseCase $useCase, AdminAcquisitionSourceIndexUseCase $sources): View
    {
        return view('admin.qr.index', ['view' => $useCase->handle($this->resolveAdminProjectId(), $request->session()->has('issued_point_code_id') ? (int) $request->session()->get('issued_point_code_id') : null, $request->session()->has('issued_stamp_code_id') ? (int) $request->session()->get('issued_stamp_code_id') : null),
            'sources' => $sources->handle($this->resolveAdminProjectId(), $this->resolveAdminProjectKey()), 'screens' => AppScreenCatalog::tabs()]);
    }
}
