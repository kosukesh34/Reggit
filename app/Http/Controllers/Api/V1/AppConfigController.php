<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\UseCases\AppConfig\GetAppConfigUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ネイティブアプリ起動時に呼ばれるアプリ設定API。
 * タブ構成・機能フラグ・テーマ・デザイナー設定を返す。
 */
class AppConfigController extends Controller
{
    public function __construct(
        private GetAppConfigUseCase $getAppConfigUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $projectKey = (string) $request->query('project_key', 'default');
        $view = $this->getAppConfigUseCase->handle($projectKey);

        return response()->json($view->toArray());
    }
}
