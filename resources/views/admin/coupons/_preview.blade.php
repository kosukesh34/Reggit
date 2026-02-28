{{-- 会員に配布されるクーポンの見た目（選択中デザイン1枚を表示） --}}
<div class="card mt-3 coupon-preview-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-mobile-alt"></i> 配布されるクーポンの見た目</h3>
        <p class="card-desc text-muted small mb-0 mt-1">会員アプリに表示される実際のクーポンです。デザイン・色・名前・画像・割引内容を変更すると即時反映されます。</p>
    </div>
    <div class="card-body">
        <div class="coupon-preview-single-wrap">
            <div id="coupon-preview-inner" class="coupon-preview-single"></div>
        </div>
    </div>
</div>

@push('styles')
<style>
.coupon-preview-single-wrap {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 200px;
    padding: 1rem 0;
}
.coupon-preview-single {
    max-width: 320px;
    width: 100%;
}
.coupon-preview-single .cp-card {
    padding: 16px;
    min-height: 160px;
}
.coupon-preview-single .cp-image img {
    height: 140px;
}
.coupon-preview-single .cp-image.cp-icon {
    min-height: 100px;
    font-size: 2.25rem;
}
.coupon-preview-single .cp-name {
    font-size: 15px;
}
.coupon-preview-single .cp-valid {
    font-size: 12px;
}
.coupon-preview-single .cp-discount {
    font-size: 13px;
    font-weight: 700;
    color: var(--cp-accent, var(--success));
    margin: 6px 0 0;
}
.coupon-preview-single .cp-card.cp-vintage .cp-discount { color: #5c4a32; }
.coupon-preview-single .cp-card.cp-neon .cp-discount { color: var(--cp-accent, #0f0); }
.coupon-preview-item .cp-card,
.coupon-preview-single .cp-card {
    background: #fff;
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 12px;
    box-shadow: var(--shadow);
}
.coupon-preview-item .cp-card.cp-available,
.coupon-preview-single .cp-card.cp-available { border-color: var(--success); }
.coupon-preview-item .cp-card[data-accent],
.coupon-preview-single .cp-card[data-accent] { --cp-accent: var(--success); }
.coupon-preview-item .cp-image,
.coupon-preview-single .cp-image {
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 8px;
    background: var(--bg-secondary);
}
.coupon-preview-item .cp-image img,
.coupon-preview-single .cp-image img {
    display: block;
    width: 100%;
    height: 100px;
    object-fit: cover;
}
.coupon-preview-item .cp-image.cp-icon,
.coupon-preview-single .cp-image.cp-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 80px;
    font-size: 1.75rem;
}
.coupon-preview-item .cp-card[data-accent] .cp-image.cp-icon,
.coupon-preview-single .cp-card[data-accent] .cp-image.cp-icon { color: var(--cp-accent); }
.coupon-preview-item .cp-name i,
.coupon-preview-single .cp-name i { margin-right: 4px; font-size: 0.85em; }
.coupon-preview-item .cp-card[data-accent] .cp-name i,
.coupon-preview-single .cp-card[data-accent] .cp-name i { color: var(--cp-accent); }
.coupon-preview-item .cp-header,
.coupon-preview-single .cp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
    gap: 6px;
}
.coupon-preview-item .cp-name,
.coupon-preview-single .cp-name {
    font-weight: 700;
    font-size: 13px;
    color: var(--text-primary);
    flex: 1;
    min-width: 0;
}
.coupon-preview-item .cp-status,
.coupon-preview-single .cp-status {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    background: #d4edda;
    color: #155724;
    flex-shrink: 0;
}
.coupon-preview-item .cp-valid,
.coupon-preview-single .cp-valid {
    font-size: 11px;
    color: var(--text-tertiary);
    margin: 0;
}
/* デザイン別 */
.coupon-preview-item .cp-card.cp-modern,
.coupon-preview-single .cp-card.cp-modern {
    border-radius: 14px;
    border: none;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.coupon-preview-item .cp-card.cp-modern.cp-available[data-accent],
.coupon-preview-single .cp-card.cp-modern.cp-available[data-accent] { box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
.cp-modern.cp-available { box-shadow: 0 4px 16px rgba(5, 150, 105, 0.15); }
.coupon-preview-item .cp-card.cp-minimal,
.coupon-preview-single .cp-card.cp-minimal {
    padding: 10px;
    border-width: 1px;
    border-radius: 8px;
}
.coupon-preview-item .cp-card.cp-gradient,
.coupon-preview-single .cp-card.cp-gradient {
    border: none;
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
}
.coupon-preview-item .cp-card.cp-gradient.cp-available,
.coupon-preview-single .cp-card.cp-gradient.cp-available {
    background: linear-gradient(135deg, #f0fff4 0%, #e6ffed 100%);
}
.coupon-preview-item .cp-card.cp-gradient.cp-available[data-accent],
.coupon-preview-single .cp-card.cp-gradient.cp-available[data-accent] {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cp-accent) 12%, #fff) 0%, color-mix(in srgb, var(--cp-accent) 6%, #fff) 100%);
}
.coupon-preview-item .cp-card.cp-cardstyle,
.coupon-preview-single .cp-card.cp-cardstyle {
    border: 1px solid var(--border);
    background: #fafafa;
}
.coupon-preview-item .cp-card.cp-cardstyle .cp-header,
.coupon-preview-single .cp-card.cp-cardstyle .cp-header {
    padding: 6px 0;
    border-bottom: 1px dashed var(--border);
}
.coupon-preview-item .cp-card.cp-cardstyle.cp-available[data-accent],
.coupon-preview-single .cp-card.cp-cardstyle.cp-available[data-accent] { border-color: var(--cp-accent); background: #fff; }
/* バーコード風 */
.coupon-preview-item .cp-card.cp-barcode,
.coupon-preview-single .cp-card.cp-barcode {
    border-left: 4px solid var(--cp-accent, var(--primary));
    background: #fafafa;
}
.coupon-preview-item .cp-card.cp-barcode .cp-barcode-line,
.coupon-preview-single .cp-card.cp-barcode .cp-barcode-line {
    height: 4px;
    margin: 2px 0;
    background: repeating-linear-gradient(90deg, #333 0, #333 2px, transparent 2px, transparent 4px);
    background-size: 100% 4px;
}
.coupon-preview-item .cp-card.cp-barcode .cp-code-text,
.coupon-preview-single .cp-card.cp-barcode .cp-code-text {
    font-family: monospace;
    font-size: 10px;
    letter-spacing: 1px;
    color: var(--text-secondary);
    margin-top: 4px;
}
/* QR風 */
.coupon-preview-item .cp-card.cp-qr,
.coupon-preview-single .cp-card.cp-qr {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: flex-start;
}
.coupon-preview-item .cp-card.cp-qr .cp-qr-block,
.coupon-preview-single .cp-card.cp-qr .cp-qr-block {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    max-width: 80px;
    margin: 0 auto 8px;
}
.coupon-preview-item .cp-card.cp-qr .cp-qr-dot,
.coupon-preview-single .cp-card.cp-qr .cp-qr-dot {
    width: 6px;
    height: 6px;
    background: #333;
    border-radius: 1px;
}
.coupon-preview-item .cp-card.cp-qr .cp-header,
.coupon-preview-single .cp-card.cp-qr .cp-header { width: 100%; margin-top: 0; }
/* ストライプ */
.coupon-preview-item .cp-card.cp-stripe,
.coupon-preview-single .cp-card.cp-stripe {
    border: none;
    background: repeating-linear-gradient(
        -45deg,
        #fff,
        #fff 8px,
        var(--cp-accent, var(--primary)) 8px,
        color-mix(in srgb, var(--cp-accent, var(--primary)) 0.12, #fff) 16px
    );
}
.coupon-preview-item .cp-card.cp-stripe .cp-header,
.coupon-preview-item .cp-card.cp-stripe .cp-image,
.coupon-preview-item .cp-card.cp-stripe .cp-valid,
.coupon-preview-single .cp-card.cp-stripe .cp-header,
.coupon-preview-single .cp-card.cp-stripe .cp-image,
.coupon-preview-single .cp-card.cp-stripe .cp-valid,
.coupon-preview-single .cp-card.cp-stripe .cp-discount { background: rgba(255,255,255,0.95); padding: 4px; border-radius: 6px; }
/* チケット風 */
.coupon-preview-item .cp-card.cp-ticket,
.coupon-preview-single .cp-card.cp-ticket {
    border: 2px dashed var(--cp-accent, var(--primary));
    border-radius: 4px;
    background: linear-gradient(#fff 0%, #f8f9fa 100%);
}
.coupon-preview-item .cp-card.cp-ticket .cp-header,
.coupon-preview-single .cp-card.cp-ticket .cp-header {
    border-bottom: 1px dashed var(--border);
    padding-bottom: 6px;
}
/* ヴィンテージ */
.coupon-preview-item .cp-card.cp-vintage,
.coupon-preview-single .cp-card.cp-vintage {
    background: #f5f0e6;
    border: 1px solid #d4c4a0;
    border-radius: 4px;
    color: #5c4a32;
}
.coupon-preview-item .cp-card.cp-vintage .cp-name,
.coupon-preview-single .cp-card.cp-vintage .cp-name { color: #3d3225; }
.coupon-preview-item .cp-card.cp-vintage .cp-status,
.coupon-preview-single .cp-card.cp-vintage .cp-status { background: #e8dfc8; color: #5c4a32; }
.coupon-preview-item .cp-card.cp-vintage.cp-available[data-accent],
.coupon-preview-single .cp-card.cp-vintage.cp-available[data-accent] { border-color: var(--cp-accent); }
/* ネオン */
.coupon-preview-item .cp-card.cp-neon,
.coupon-preview-single .cp-card.cp-neon {
    background: #1a1a2e;
    border: 2px solid var(--cp-accent, #0f0);
    border-radius: 12px;
    color: #eee;
    box-shadow: 0 0 12px color-mix(in srgb, var(--cp-accent, #0f0) 0.5);
}
.coupon-preview-item .cp-card.cp-neon .cp-name,
.coupon-preview-single .cp-card.cp-neon .cp-name { color: #fff; }
.coupon-preview-item .cp-card.cp-neon .cp-name i,
.coupon-preview-single .cp-card.cp-neon .cp-name i { color: var(--cp-accent, #0f0); }
.coupon-preview-item .cp-card.cp-neon .cp-image.cp-icon,
.coupon-preview-single .cp-card.cp-neon .cp-image.cp-icon { color: var(--cp-accent, #0f0); }
.coupon-preview-item .cp-card.cp-neon .cp-status,
.coupon-preview-single .cp-card.cp-neon .cp-status {
    background: color-mix(in srgb, var(--cp-accent, #0f0) 0.3);
    color: var(--cp-accent, #0f0);
}
.coupon-preview-item .cp-card.cp-neon .cp-valid,
.coupon-preview-single .cp-card.cp-neon .cp-valid { color: #aaa; }
</style>
@endpush

<script>
(function() {
    function esc(s) {
        if (s === null || s === undefined) return '';
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }
    function getThemeColor() {
        var el = document.getElementById('theme_color');
        if (!el || !el.value) return '';
        var v = el.value.trim();
        return /^#[0-9A-Fa-f]{6}$/.test(v) ? v : '';
    }
    function getSelectedDesign() {
        var radio = document.querySelector('input[name="design_template"]:checked');
        return (radio && radio.value) ? radio.value : 'classic';
    }
    function getDiscountText() {
        var typeEl = document.getElementById('type');
        var valueEl = document.getElementById('value');
        if (!typeEl || !valueEl) return '';
        var type = typeEl.value;
        var val = parseFloat(valueEl.value);
        if (isNaN(val) || val <= 0) return '';
        if (type === 'percent') return val + '%オフ';
        if (type === 'fixed') return val + '円オフ';
        return '';
    }
    function card(name, imageUrl, validUntil, design, accent, discountText) {
        var accentAttr = accent ? ' data-accent="1" style="--cp-accent: ' + esc(accent) + '"' : '';
        var img = imageUrl
            ? '<div class="cp-image"><img src="' + esc(imageUrl) + '" alt=""></div>'
            : '<div class="cp-image cp-icon"><i class="fas fa-ticket-alt"></i></div>';
        var cls = 'cp-card cp-available cp-' + (design === 'card' ? 'cardstyle' : design);
        var valid = validUntil ? '<p class="cp-valid">有効期限: ' + esc(validUntil) + '</p>' : '';
        var discount = discountText ? '<p class="cp-discount">' + esc(discountText) + '</p>' : '';

        if (design === 'barcode') {
            img = '<div class="cp-barcode-line"></div><div class="cp-barcode-line"></div><div class="cp-code-text">' + esc(name) + '</div>';
            return '<div class="' + cls + '"' + accentAttr + '>' + img +
                '<div class="cp-header"><span class="cp-name"><i class="fas fa-barcode"></i>' + esc(name) + '</span><span class="cp-status">利用可能</span></div>' + discount + valid + '</div>';
        }
        if (design === 'qr') {
            var qrDots = [];
            for (var i = 0; i < 25; i++) qrDots.push('<span class="cp-qr-dot"></span>');
            img = '<div class="cp-qr-block">' + qrDots.join('') + '</div>';
            return '<div class="' + cls + '"' + accentAttr + '>' + img +
                '<div class="cp-header"><span class="cp-name"><i class="fas fa-qrcode"></i>' + esc(name) + '</span><span class="cp-status">利用可能</span></div>' + discount + valid + '</div>';
        }
        return '<div class="' + cls + '"' + accentAttr + '>' + img +
            '<div class="cp-header"><span class="cp-name"><i class="fas fa-ticket-alt"></i>' + esc(name) + '</span><span class="cp-status">利用可能</span></div>' + discount + valid + '</div>';
    }
    function update() {
        var nameEl = document.getElementById('name');
        var name = (nameEl && nameEl.value ? nameEl.value : '').trim() || 'クーポン名';
        var imgEl = document.getElementById('image_url');
        var imageUrl = (imgEl && imgEl.value ? imgEl.value : '').trim();
        var endAtEl = document.getElementById('end_at');
        var endAt = endAtEl ? endAtEl.value : '';
        var validUntil = '';
        if (endAt) {
            try { validUntil = new Date(endAt).toLocaleDateString('ja-JP', { year: 'numeric', month: 'short', day: 'numeric' }); } catch (e) {}
        }
        var accent = getThemeColor();
        var design = getSelectedDesign();
        var discountText = getDiscountText();
        var el = document.getElementById('coupon-preview-inner');
        if (!el) return;
        var html = card(name, imageUrl, validUntil, design, accent, discountText);
        el.innerHTML = html;
        var lineEl = document.getElementById('line-coupon-preview-inner');
        if (lineEl) lineEl.innerHTML = html;
    }
    window.updateCouponPreview = update;
    function on() {
        var ids = ['name', 'description', 'image_url', 'end_at', 'theme_color', 'theme_color_display', 'type', 'value'];
        ids.forEach(function(id) {
            var e = document.getElementById(id);
            if (e) { e.addEventListener('input', update); e.addEventListener('change', update); }
        });
        document.querySelectorAll('input[name="design_template"]').forEach(function(radio) {
            radio.addEventListener('change', update);
        });
        update();
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', on) : on();
})();
</script>
