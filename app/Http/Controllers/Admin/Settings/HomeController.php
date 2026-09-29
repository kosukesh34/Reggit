<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsHomeRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Settings\GetHomeSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateHomeSettingsUseCase;

class HomeController extends AdminBaseController
{
    private GetHomeSettingsUseCase $getHomeSettingsUseCase;

    private UpdateHomeSettingsUseCase $updateHomeSettingsUseCase;

    public function __construct(
        GetHomeSettingsUseCase $getHomeSettingsUseCase,
        UpdateHomeSettingsUseCase $updateHomeSettingsUseCase,
        private PublicImageStorageService $publicImageStorage,
    ) {
        $this->getHomeSettingsUseCase = $getHomeSettingsUseCase;
        $this->updateHomeSettingsUseCase = $updateHomeSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $data = $this->getHomeSettingsUseCase->handle($projectId);

        return view('admin.settings.home', $data);
    }

    public function update(SettingsHomeRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        $raw = $validated['tabs'] ?? [];
        $navigationHeaderImageUrl = null;
        if ($request->hasFile('navigation_header_image')) {
            $navigationHeaderImageUrl = $this->publicImageStorage->store(
                $request->file('navigation_header_image'),
                $projectId,
                PublicImageStorageService::DIR_NAVIGATION_HEADER,
            );
        } elseif ($request->input('navigation_header_image_clear') === '1') {
            $navigationHeaderImageUrl = '';
        }
        $homeImageUrl = null;
        if ($request->hasFile('home_image')) {
            $homeImageUrl = $this->publicImageStorage->store(
                $request->file('home_image'),
                $projectId,
                PublicImageStorageService::DIR_HOME_IMAGE,
            );
        } elseif ($request->input('home_image_clear') === '1') {
            $homeImageUrl = '';
        }
        $homeImageUrls = null;
        if ($request->input('home_images_present') === '1') {
            $existing = $this->getHomeSettingsUseCase->handle($projectId)['homeImageUrls'];
            $removed = array_map('intval', $validated['home_image_remove'] ?? []);
            $homeImageUrls = array_values(array_filter(
                $existing,
                fn ($url, $index) => ! in_array($index, $removed, true),
                ARRAY_FILTER_USE_BOTH,
            ));
            $newImages = $request->file('home_images', []);
            if (count($homeImageUrls) + count($newImages) > 10) {
                return back()->withErrors(['home_images' => 'ホーム画像は最大10枚まで登録できます。'])->withInput();
            }
            foreach ($newImages as $image) {
                $homeImageUrls[] = $this->publicImageStorage->store(
                    $image,
                    $projectId,
                    PublicImageStorageService::DIR_HOME_IMAGE,
                );
            }
        }
        $this->updateHomeSettingsUseCase->handle(
            $projectId,
            is_array($raw) ? $raw : [],
            $navigationHeaderImageUrl,
            (string) ($validated['navigation_header_background_color'] ?? '#FFFFFF'),
            $homeImageUrl,
            (string) $validated['home_menu_layout'],
            (string) $validated['product_display_layout'],
            $request->boolean('native_navigation_present')
                ? (is_array($validated['native_navigation'] ?? null) ? $validated['native_navigation'] : [])
                : null,
            $homeImageUrls,
            array_key_exists('home_show_member_card', $validated) ? $request->boolean('home_show_member_card') : null,
            $validated['native_chrome_colors'] ?? null,
        );

        $url = route('admin.settings.home', $this->buildAdminRouteParams());
        if (isset($validated['design_tab'])) {
            $url .= '#design-'.$validated['design_tab'];
        }

        return redirect()->to($url)
            ->with('success', 'ホーム設定を更新しました');
    }
}
