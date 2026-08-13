<?php

namespace App\Http\Controllers;

use App\UseCases\Liff\Data\LiffIndexInput;
use App\UseCases\Liff\LiffIndexUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

class LiffController extends Controller
{
    public function __construct(
        private LiffIndexUseCase $liffIndexUseCase,
    ) {}

    public function index(Request $request): HttpResponse
    {
        return $this->liffIndexUseCase->handle(new LiffIndexInput(
            (string) $request->userAgent(),
        ));
    }
}
