<?php

namespace App\Http\Controllers;

use App\UseCases\Stamp\StampIndexUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * スタンプ一覧 Web API。責務は UseCase に委譲する。
 */
class StampController extends Controller
{
    private StampIndexUseCase $stampIndexUseCase;

    public function __construct(StampIndexUseCase $stampIndexUseCase)
    {
        $this->stampIndexUseCase = $stampIndexUseCase;
    }

    public function index(Request $request)
    {
        try {
            $output = $this->stampIndexUseCase->handle($request);

            if ($output->requiresMembership) {
                return response()->json([
                    'stamp_cards' => [],
                    'message' => '会員登録が必要です',
                    'default_image_url' => $output->defaultImageUrl,
                ]);
            }

            return response()->json([
                'stamp_cards' => $output->stampCardsToArray(),
                'default_image_url' => $output->defaultImageUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Stamps index error: ' . $e->getMessage());

            return response()->json(['error' => 'スタンプの取得に失敗しました'], 500);
        }
    }
}
