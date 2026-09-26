<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Gallery\GalleryItemDestroyUseCase;
use App\UseCases\Admin\Gallery\GalleryItemStoreUseCase;
use App\UseCases\Admin\Gallery\GalleryItemUpdateUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ItemMutationController extends AdminBaseController
{
    public function __construct(private PublicImageStorageService $publicImageStorage) {}

    public function store(Request $request, GalleryItemStoreUseCase $useCase): RedirectResponse
    {
        $validated = $request->validate($this->rules(imageRequired: true));
        $imageFile = $request->file('image');
        if (! $imageFile instanceof UploadedFile) {
            return redirect()->back()->withErrors(['image' => '画像を選択してください'])->withInput();
        }
        $validated['image_url'] = $this->storeImage($imageFile);
        $useCase->handle($this->normalizedPayload($request, $validated), $this->resolveAdminProjectId());

        return $this->redirectToIndex()->with('success', '画像を追加しました');
    }

    public function update(Request $request, int $id, GalleryItemUpdateUseCase $useCase): RedirectResponse
    {
        $validated = $request->validate($this->rules(imageRequired: false));
        $imageFile = $request->file('image');
        if ($imageFile instanceof UploadedFile) {
            $validated['image_url'] = $this->storeImage($imageFile);
        }
        $useCase->handle($id, $this->normalizedPayload($request, $validated));

        return $this->redirectToIndex()->with('success', '画像を更新しました');
    }

    public function destroy(int $id, GalleryItemDestroyUseCase $useCase): RedirectResponse
    {
        $useCase->handle($id);

        return $this->redirectToIndex()->with('success', '画像を削除しました');
    }

    /** @return array<string, string> */
    private function rules(bool $imageRequired): array
    {
        return [
            'category_id' => 'nullable|exists:gallery_categories,id',
            'title' => 'nullable|string|max:150',
            'image' => ($imageRequired ? 'required' : 'nullable').'|image|mimes:png,jpg,jpeg,gif,webp|max:2048',
            'image_url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:1000',
            'size' => 'nullable|in:small,medium,large',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ];
    }

    /** @param array<string, mixed> $validated */
    private function normalizedPayload(Request $request, array $validated): array
    {
        unset($validated['image']);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function storeImage(UploadedFile $image): string
    {
        return $this->publicImageStorage->store(
            $image,
            $this->resolveAdminProjectId(),
            PublicImageStorageService::DIR_GALLERY,
        );
    }

    private function redirectToIndex(): RedirectResponse
    {
        return redirect()->route('admin.gallery.items', $this->buildAdminRouteParams());
    }
}
