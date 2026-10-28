<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ProjectKey;
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
        $projectKey = trim((string) $request->query('project_key', ''));
        if ($projectKey === '') {
            $projectKey = (string) (ProjectKey::fromConfig() ?? '');
        }
        if ($projectKey === '') {
            return response()->json(['error' => 'project_key は必須です。'], 400);
        }
        $view = $this->getAppConfigUseCase->handle($projectKey, $request->query('surface') === 'liff');

        return response()->json($view->toArray());
    }
}
