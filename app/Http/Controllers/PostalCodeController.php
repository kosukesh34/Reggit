<?php

namespace App\Http\Controllers;

use App\UseCases\PostalCode\PostalCodeSearchUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 郵便番号で住所を検索する。責務は UseCase に委譲する。
 */
class PostalCodeController extends Controller
{
    public function __construct(private readonly PostalCodeSearchUseCase $postalCodeSearchUseCase)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $postalCode = $request->input('postal_code')
            ?? $request->query('postal_code')
            ?? $request->input('q')
            ?? $request->query('q')
            ?? '';
        $postalCode = is_string($postalCode) ? trim($postalCode) : '';

        $output = $this->postalCodeSearchUseCase->handle($postalCode);

        $payload = ['addresses' => $output->addresses];
        if ($output->error !== null) {
            $payload['error'] = $output->error;
        }

        return response()->json($payload);
    }
}
