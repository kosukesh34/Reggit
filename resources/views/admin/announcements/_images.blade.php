<label for="ann_image" class="form-label">お知らせの画像（最大10枚）</label>
{{-- 透明な file input を枠全体に重ね、クリックとドラッグ＆ドロップの両方をブラウザ標準で受ける --}}
<div class="coupon-image-dropzone ann-image-dropzone" id="annImageDropzone">
    <input type="file" name="image_files[]" id="ann_image" accept="image/jpeg,image/png" multiple aria-describedby="annImageHint">
    <span class="coupon-image-dropzone-icon" aria-hidden="true"><i class="fas fa-cloud-upload-alt"></i></span>
    <span class="coupon-image-dropzone-text">クリックまたはドラッグ＆ドロップで画像を選択</span>
    <span class="coupon-image-dropzone-hint" id="annImageHint">JPEG・PNG、1枚2MBまで。選択順に表示します。新しく選択すると現在の画像をすべて置き換えます。</span>
</div>
<div id="announcementImagesPreview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
    @foreach($currentImages as $image)
        <img src="{{ $image }}" alt="お知らせの画像 {{ $loop->iteration }}" style="width:100px;height:100px;object-fit:contain">
    @endforeach
</div>
@if(count($currentImages))
    <label><input type="checkbox" name="clear_images" value="1">画像をすべて削除する</label>
@endif
@error('image_files')<span class="invalid-feedback">{{ $message }}</span>@enderror
@error('image_files.*')<span class="invalid-feedback">{{ $message }}</span>@enderror
<script>
['dragenter', 'dragleave', 'drop'].forEach(type => document.getElementById('ann_image').addEventListener(type, event => {
    document.getElementById('annImageDropzone').classList.toggle('is-dragover', event.type === 'dragenter');
}));
document.getElementById('ann_image').addEventListener('change', function () {
    this.setCustomValidity(this.files.length > 10 ? '画像は最大10枚まで選択できます。' : '');
    this.reportValidity();
    const preview = document.getElementById('announcementImagesPreview');
    preview.querySelectorAll('img').forEach(image => { if (image.src.startsWith('blob:')) URL.revokeObjectURL(image.src); });
    preview.replaceChildren();
    Array.from(this.files).slice(0, 10).forEach(file => {
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = file.name;
        image.style.cssText = 'width:100px;height:100px;object-fit:contain';
        preview.append(image);
    });
});
</script>
