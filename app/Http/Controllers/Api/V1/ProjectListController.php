<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\UseCases\AppConfig\Data\PublicProjectItem;
use App\UseCases\AppConfig\ListPublicProjectsUseCase;
use Illuminate\Http\JsonResponse;

/**
 * ネイティブ / iPad 端末向けのプロジェクト一覧（認証不要）。
 */
class ProjectListController extends Controller
{
    public function __invoke(ListPublicProjectsUseCase $useCase): JsonResponse
    {
        $items = array_map(
            static fn (PublicProjectItem $item): array => $item->toArray(),
            $useCase->handle(),
        );

        return response()->json(['projects' => $items]);
    }
}
