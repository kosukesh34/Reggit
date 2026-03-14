<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Rfm\AdminRfmIndexUseCase;
use Illuminate\Http\Request;

class RfmController extends AdminBaseController
{
    private AdminRfmIndexUseCase $indexUseCase;

    public function __construct(AdminRfmIndexUseCase $indexUseCase)
    {
        $this->indexUseCase = $indexUseCase;
    }

    public function index(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $filterSegment = (string) $request->input('segment', '');
        $view = $this->indexUseCase->handle($projectId, $filterSegment);

        return view('admin.rfm', ['view' => $view]);
    }
}
