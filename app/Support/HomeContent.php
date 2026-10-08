<?php

namespace App\Support;

use App\Domain\Settings\Repositories\SettingValueReader;
use App\Models\GalleryItem;

final class HomeContent
{
    public static function forProject(SettingValueReader $settings, int $projectId): array
    {
        $content = (array) $settings->getValue('home_content', [], $projectId);
        $layout = in_array($content['layout'] ?? '', ['gallery', 'member_news', 'buttons_gallery'], true) ? $content['layout'] : 'legacy';
        $limit = isset($content['image_limit']) ? max(1, min(1000, (int) $content['image_limit'])) : null;
        $items = [];
        if (in_array($layout, ['gallery', 'buttons_gallery'], true)) {
            $query = GalleryItem::where('project_id', $projectId)->where('is_active', true)->orderBy('sort_order')->orderBy('id');
            if ($limit !== null) {
                $query->limit($limit);
            }
            $items = $query->get()->map(function ($item) use ($content) {
                $action = $content['actions'][$item->id] ?? [];
                $mode = in_array($action['mode'] ?? '', ['webview', 'external'], true) ? $action['mode'] : 'detail';
                $url = (string) ($action['url'] ?? '');
                if (! filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
                    $mode = 'detail';
                    $url = '';
                }

                return ['id' => $item->id, 'image_url' => $item->image_url, 'title' => $item->title, 'description' => $item->description, 'mode' => $mode, 'url' => $url];
            })->all();
        }

        return ['layout' => $layout, 'image_limit' => $limit, 'gallery_items' => $items];
    }
}
