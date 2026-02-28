<?php

namespace App\Http\Controllers;

use App\UseCases\Liff\LiffIndexUseCase;
use Illuminate\Http\Response as HttpResponse;

class LiffController extends Controller
{
    private LiffIndexUseCase $liffIndexUseCase;

    public function __construct(LiffIndexUseCase $liffIndexUseCase)
    {
        $this->liffIndexUseCase = $liffIndexUseCase;
    }

    public function index(): HttpResponse
    {
        return $this->liffIndexUseCase->handle();
    }
}
