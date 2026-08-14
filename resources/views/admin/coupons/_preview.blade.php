@php
    $couponPreviewBaseUrl = admin_front_url();
    $couponPreviewUrl = $couponPreviewBaseUrl !== null
        ? $couponPreviewBaseUrl.(str_contains($couponPreviewBaseUrl, '?') ? '&' : '?').'designer_live_preview=1&designer_embed=1&coupon_form_preview=1#/coupon'
        : null;
@endphp

<div class="cp-real-preview-stage" aria-label="実際の会員アプリのプレビュー">
    @if($couponPreviewUrl !== null)
        <div class="cp-device-shell">
            <span class="cp-device-button cp-device-button--silent" aria-hidden="true"></span>
            <span class="cp-device-button cp-device-button--volume-up" aria-hidden="true"></span>
            <span class="cp-device-button cp-device-button--volume-down" aria-hidden="true"></span>
            <span class="cp-device-button cp-device-button--power" aria-hidden="true"></span>
            <div class="cp-real-preview-frame">
                <div class="cp-real-preview-screen">
                    <iframe
                        id="couponLivePreview"
                        title="実際の会員アプリのクーポン画面"
                        src="{{ $couponPreviewUrl }}"
                        loading="eager"
                    ></iframe>
                </div>
            </div>
        </div>
        <p class="cp-real-preview-note"><i class="fas fa-circle-check" aria-hidden="true"></i> 店舗アプリと同じ画面・デザインです</p>
    @else
        <div class="cp-real-preview-unavailable">
            <i class="fas fa-mobile-screen" aria-hidden="true"></i>
            <p>店舗アプリのURLを取得できませんでした。</p>
        </div>
    @endif
</div>

<script>
(function() {
    function field(id) {
        return document.getElementById(id);
    }
    function getType() {
        var checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'percent';
    }
    function getImageFit() {
        var checked = document.querySelector('input[name="image_fit"]:checked');
        return checked ? checked.value : 'cover';
    }
    function getDiscountText() {
        var valueEl = field('value');
        if (!valueEl) return '';
        var value = Number(valueEl.value);
        if (!Number.isFinite(value) || value <= 0) return '';
        var display = Number.isInteger(value)
            ? value.toLocaleString('ja-JP')
            : value.toLocaleString('ja-JP', { maximumFractionDigits: 2 });
        return getType() === 'percent' ? display + '% OFF' : display + '円 OFF';
    }
    function dateText(value, withYear) {
        if (!value) return '';
        var date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleDateString('ja-JP', withYear
            ? { year: 'numeric', month: 'short', day: 'numeric' }
            : { month: 'numeric', day: 'numeric' });
    }
    function setText(id, value) {
        var el = field(id);
        if (el) el.textContent = value;
    }
    function currentImageUrl() {
        var urlInput = field('image_url');
        var previewWrap = field('coupon_image_preview');
        var previewImage = field('coupon_image_preview_img');
        if (previewWrap && !previewWrap.hidden && previewImage && previewImage.getAttribute('src')) {
            return previewImage.getAttribute('src') || '';
        }
        return urlInput && urlInput.value ? urlInput.value.trim() : '';
    }
    function currentPreviewCoupon() {
        var nameInput = field('name');
        var codeInput = field('code');
        var descriptionInput = field('description');
        var startInput = field('start_at');
        var endInput = field('end_at');
        var useDisplayInput = field('use_display');
        var startAt = startInput ? startInput.value : '';
        var now = Date.now();
        var startsAt = startAt ? new Date(startAt).getTime() : NaN;

        return {
            id: '__reggit_coupon_form_preview__',
            coupon_name: (nameInput && nameInput.value ? nameInput.value : '').trim() || 'クーポン名が入ります',
            description: (descriptionInput && descriptionInput.value ? descriptionInput.value : '').trim() || 'お店からのメッセージや利用時の注意事項が表示されます。',
            code: (codeInput && codeInput.value ? codeInput.value : '').trim() || 'COUPON-CODE',
            image_url: currentImageUrl(),
            image_fit: getImageFit(),
            valid_from: startAt,
            valid_until: endInput ? endInput.value : '',
            issued_at: new Date().toISOString(),
            status: Number.isFinite(startsAt) && startsAt > now ? 'upcoming' : 'available',
            use_display: useDisplayInput ? useDisplayInput.value : 'barcode'
        };
    }
    function updateSummary() {
        var startInput = field('start_at');
        var endInput = field('end_at');
        var activeInput = field('is_active');
        var startAt = startInput ? startInput.value : '';
        var endAt = endInput ? endInput.value : '';
        var isActive = !activeInput || activeInput.checked;
        var period = '期限なし';
        if (startAt && endAt) period = dateText(startAt, false) + ' → ' + dateText(endAt, false);
        else if (startAt) period = dateText(startAt, true) + 'から';
        else if (endAt) period = dateText(endAt, true) + 'まで';
        setText('couponSummaryDiscount', getDiscountText() || '未設定');
        setText('couponSummaryPeriod', period);
        setText('couponSummaryStatus', isActive ? '有効' : '下書き');
    }
    function sendPreview() {
        updateSummary();
        var frame = field('couponLivePreview');
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({
            type: 'reggit:coupon-form-preview:update',
            coupon: currentPreviewCoupon()
        }, window.location.origin);
    }
    function onReady() {
        [
            'name', 'code', 'description', 'image_url', 'start_at', 'end_at',
            'value', 'is_active', 'use_display'
        ].forEach(function(id) {
            var el = field(id);
            if (el) {
                el.addEventListener('input', sendPreview);
                el.addEventListener('change', sendPreview);
            }
        });
        document.querySelectorAll('input[name="type"]').forEach(function(input) {
            input.addEventListener('change', sendPreview);
        });
        document.querySelectorAll('input[name="image_fit"]').forEach(function(input) {
            input.addEventListener('change', sendPreview);
        });
        var imageFile = field('coupon_image');
        if (imageFile) imageFile.addEventListener('admin-image-preview', sendPreview);
        var frame = field('couponLivePreview');
        if (frame) frame.addEventListener('load', sendPreview);
        window.addEventListener('message', function(event) {
            if (event.origin === window.location.origin && event.data && event.data.type === 'reggit:coupon-form-preview:ready') {
                sendPreview();
            }
        });
        sendPreview();
    }
    window.updateCouponPreview = sendPreview;
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', onReady) : onReady();
})();
</script>
