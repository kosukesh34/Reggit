<?php

namespace App\Http\Controllers\Smaregi;

use App\Http\Controllers\Controller;
use App\UseCases\Smaregi\SmaregiIdpHandleUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SmaregiIdpController extends Controller
{
    public function __construct(
        private SmaregiIdpHandleUseCase $idpHandleUseCase
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $result = $this->idpHandleUseCase->handle($request);
        return response('', $result['status']);
    }
}
