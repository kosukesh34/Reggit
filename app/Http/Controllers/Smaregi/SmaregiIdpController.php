<?php

namespace App\Http\Controllers\Smaregi;

use App\Http\Controllers\Controller;
use App\Http\Support\SmaregiNotificationInputFactory;
use App\UseCases\Smaregi\SmaregiIdpHandleUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SmaregiIdpController extends Controller
{
    public function __construct(
        private SmaregiIdpHandleUseCase $idpHandleUseCase,
        private SmaregiNotificationInputFactory $inputFactory,
    ) {}

    public function __invoke(Request $request): Response
    {
        $result = $this->idpHandleUseCase->handle($this->inputFactory->fromRequest($request));

        return response('', $result['status']);
    }
}
