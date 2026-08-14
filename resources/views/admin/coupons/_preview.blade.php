<div class="cp-preview-stage" aria-label="会員アプリのプレビュー">
    <div class="cp-device">
        <div class="cp-device-status" aria-hidden="true">
            <span>9:41</span>
            <span><i class="fas fa-signal"></i><i class="fas fa-wifi"></i><i class="fas fa-battery-full"></i></span>
        </div>
        <div class="cp-device-bar">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
            <strong>マイクーポン</strong>
            <i class="fas fa-ellipsis" aria-hidden="true"></i>
        </div>
        <div class="cp-device-canvas">
            <p class="cp-preview-kicker"><span>MY COUPON</span><strong>利用できるクーポン</strong></p>
            <div id="coupon-preview-inner"></div>
        </div>
        <span class="cp-device-home" aria-hidden="true"></span>
    </div>
</div>

<script>
(function() {
    function esc(s) {
        if (s === null || s === undefined) return '';
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }
    function field(id) {
        return document.getElementById(id);
    }
    function getType() {
        var checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'percent';
    }
    function getDiscountText() {
        var valueEl = field('value');
        if (!valueEl) return '';
        var val = parseFloat(valueEl.value);
        if (isNaN(val) || val <= 0) return '';
        var display = Number.isInteger(val) ? val.toLocaleString('ja-JP') : val.toLocaleString('ja-JP', { maximumFractionDigits: 2 });
        return getType() === 'percent' ? display + '% OFF' : display + '円 OFF';
    }
    function dateText(value, withYear) {
        if (!value) return '';
        try {
            return new Date(value).toLocaleDateString('ja-JP', withYear
                ? { year: 'numeric', month: 'short', day: 'numeric' }
                : { month: 'numeric', day: 'numeric' });
        } catch (e) {
            return '';
        }
    }
    function selectedTriggerTitle() {
        var checked = document.querySelector('input[name="issue_trigger"]:checked');
        if (!checked) return 'あとから配布';
        var card = checked.closest('.cp-choice');
        var title = card ? card.querySelector('.cp-choice-title') : null;
        return title ? title.textContent.trim() : 'あとから配布';
    }
    function setText(id, value) {
        var el = field(id);
        if (el) el.textContent = value;
    }
    function updateSummary(discountText, startAt, endAt, isActive) {
        var period = '期限なし';
        if (startAt && endAt) period = dateText(startAt, false) + ' → ' + dateText(endAt, false);
        else if (startAt) period = dateText(startAt, true) + 'から';
        else if (endAt) period = dateText(endAt, true) + 'まで';
        setText('couponSummaryDiscount', discountText || '未設定');
        setText('couponSummaryTrigger', selectedTriggerTitle());
        setText('couponSummaryPeriod', period);
        setText('couponSummaryStatus', isActive ? '有効' : '下書き');
    }
    function update() {
        var nameEl = field('name');
        var name = (nameEl && nameEl.value ? nameEl.value : '').trim() || 'クーポン名が入ります';
        var codeEl = field('code');
        var code = (codeEl && codeEl.value ? codeEl.value : '').trim() || 'COUPON-CODE';
        var descEl = field('description');
        var description = (descEl && descEl.value ? descEl.value : '').trim() || 'お店からのメッセージや利用時の注意事項がここに表示されます。';
        var imgEl = field('image_url');
        var previewWrap = field('coupon_image_preview');
        var previewImg = field('coupon_image_preview_img');
        var imageUrl = (imgEl && imgEl.value ? imgEl.value : '').trim();
        if (previewWrap && !previewWrap.hidden && previewImg && previewImg.getAttribute('src')) {
            imageUrl = previewImg.getAttribute('src') || imageUrl;
        }
        var startAtEl = field('start_at');
        var endAtEl = field('end_at');
        var startAt = startAtEl ? startAtEl.value : '';
        var endAt = endAtEl ? endAtEl.value : '';
        var isActiveEl = field('is_active');
        var isActive = !isActiveEl || isActiveEl.checked;
        var discountText = getDiscountText();
        var validText = endAt ? dateText(endAt, true) + 'まで' : '期限なし';
        var el = field('coupon-preview-inner');
        if (!el) return;
        var media = imageUrl
            ? '<div class="cp-ticket-media"><img src="' + esc(imageUrl) + '" alt=""><div class="cp-ticket-shade"></div><span class="cp-ticket-status">' + (isActive ? '利用可能' : '準備中') + '</span></div>'
            : '<div class="cp-ticket-media cp-ticket-media--empty"><span class="cp-ticket-art">' + (discountText ? esc(discountText) : 'SPECIAL OFFER') + '</span><i class="fas fa-ticket" aria-hidden="true"></i><span class="cp-ticket-status">' + (isActive ? '利用可能' : '準備中') + '</span></div>';
        el.innerHTML = '<article class="cp-ticket">' + media +
            '<div class="cp-ticket-body">' +
                '<p class="cp-ticket-discount">' + esc(discountText || '特典を設定') + '</p>' +
                '<h3 class="cp-ticket-name">' + esc(name) + '</h3>' +
                '<p class="cp-ticket-desc">' + esc(description) + '</p>' +
                '<div class="cp-ticket-meta"><span><i class="far fa-clock" aria-hidden="true"></i>' + esc(validText) + '</span><span>' + esc(code) + '</span></div>' +
            '</div>' +
            '<div class="cp-ticket-cut"><span></span></div>' +
            '<div class="cp-ticket-action"><span>クーポンを使う</span><i class="fas fa-arrow-right" aria-hidden="true"></i></div>' +
            '</article>';
        updateSummary(discountText, startAt, endAt, isActive);
    }
    window.updateCouponPreview = update;
    function on() {
        ['name', 'code', 'description', 'image_url', 'start_at', 'end_at', 'value', 'is_active'].forEach(function(id) {
            var e = field(id);
            if (e) {
                e.addEventListener('input', update);
                e.addEventListener('change', update);
            }
        });
        document.querySelectorAll('input[name="type"], input[name="issue_trigger"]').forEach(function(input) {
            input.addEventListener('change', update);
        });
        var imageFile = field('coupon_image');
        if (imageFile) imageFile.addEventListener('admin-image-preview', update);
        update();
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', on) : on();
})();
</script>
