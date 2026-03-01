<?php

namespace App\Http\Controllers\Api\V1;

use App\UseCases\Stamp\StampIndexUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class StampController extends BaseApiController
{
    public function __construct(private StampIndexUseCase $indexUseCase) {}

    public function index(Request $request)
    {
        try {
            $result = $this->indexUseCase->handle($request);
            if ($result->requiresMembership) {
                return $this->success([
                    'stamp_cards' => [],
                    'message' => '会員登録が必要です',
                ]);
            }

            return $this->success([
                'stamp_cards' => $result->stampCardsToArray(),
                'default_image_url' => $result->defaultImageUrl,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Stamp] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('スタンプの取得に失敗しました', 500);
        }
    }
}
