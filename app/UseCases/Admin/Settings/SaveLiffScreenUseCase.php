<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\SaveLiffScreenInput;
use App\UseCases\Admin\Settings\Data\SaveLiffScreenResult;
use App\Util\StrHelper;
use Illuminate\Support\Str;

class SaveLiffScreenUseCase
{
    /**
     * 現在の画面を保存（上限10件・超えたら古いものを削除）。実行結果を Result で返す。
     */
    public function handle(int $projectId, SaveLiffScreenInput $input): SaveLiffScreenResult
    {
        $trimmedName = trim($input->name);
        $screens = ShopSetting::getLiffSavedScreens($projectId);

        if (StrHelper::isEmptyStringOrNull($trimmedName)) {
            return new SaveLiffScreenResult(false, count($screens));
        }

        $layouts = $this->decodeJsonArray($input->layoutsJson);
        $tabSettings = $this->decodeJsonArray($input->tabSettingsJson);

        /** @var array{id: string, name: string, layouts: array<string, array<int, array{type: string, title?: string, body?: string, source?: string}>>, tab_settings: array<string, array{background_color?: string, background_image?: string}>, created_at: string} $newItem */
        $newItem = [
            'id' => Str::uuid()->toString(),
            'name' => $trimmedName,
            'layouts' => $layouts,
            'tab_settings' => $tabSettings,
            'created_at' => now()->toIso8601String(),
        ];
        array_unshift($screens, $newItem);
        $screens = array_slice($screens, 0, ShopSetting::LIFF_SAVED_SCREENS_MAX);
        ShopSetting::setLiffSavedScreens($screens, $projectId);

        return new SaveLiffScreenResult(true, count($screens));
    }

    /**
     * JSON オブジェクトを連想配列に変換。layouts 用・tab_settings 用のいずれにも使用
     *
     * @return array<string, array> キーはタブキー、値はブロック配列または画面設定（background_color, background_image など）
     */
    private function decodeJsonArray(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
