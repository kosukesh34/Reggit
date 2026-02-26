{{-- アプリ表示プレビュー（全デザイン・配色反映） --}}
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-mobile-alt"></i> アプリでの表示プレビュー</h3>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">「配色」とフォームの名前・画像・有効期限を変更するとプレビューが更新されます。表示デザインは11種類から選択できます。</p>
        <div class="coupon-preview">
            <div id="coupon-preview-inner" class="coupon-preview-grid"></div>
        </div>
    </div>
</div>

@push('styles')
<style>
.coupon-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
    align-items: start;
}
.coupon-preview-item {
    display: flex;
    flex-direction: column;
}
.coupon-preview-item .coupon-preview-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 6px;
}
.coupon-preview-item .cp-card {
    background: #fff;
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 12px;
    box-shadow: var(--shadow);
}
.coupon-preview-item .cp-card.cp-available { border-color: var(--success); }
.coupon-preview-item .cp-card[data-accent] { --cp-accent: var(--success); }
.coupon-preview-item .cp-image {
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 8px;
    background: var(--bg-secondary);
}
.coupon-preview-item .cp-image img {
    display: block;
    width: 100%;
    height: 100px;
    object-fit: cover;
}
.coupon-preview-item .cp-image.cp-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 80px;
    font-size: 1.75rem;
}
.coupon-preview-item .cp-card[data-accent] .cp-image.cp-icon { color: var(--cp-accent); }
.coupon-preview-item .cp-name i { margin-right: 4px; font-size: 0.85em; }
.coupon-preview-item .cp-card[data-accent] .cp-name i { color: var(--cp-accent); }
.coupon-preview-item .cp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
    gap: 6px;
}
.coupon-preview-item .cp-name {
    font-weight: 700;
    font-size: 13px;
    color: var(--text-primary);
    flex: 1;
    min-width: 0;
}
.coupon-preview-item .cp-status {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    background: #d4edda;
    color: #155724;
    flex-shrink: 0;
}
.coupon-preview-item .cp-valid {
    font-size: 11px;
    color: var(--text-tertiary);
    margin: 0;
}
/* デザイン別 */
.coupon-preview-item .cp-card.cp-modern {
    border-radius: 14px;
    border: none;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.coupon-preview-item .cp-card.cp-modern.cp-available[data-accent] { box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
.cp-modern.cp-available { box-shadow: 0 4px 16px rgba(5, 150, 105, 0.15); }
.coupon-preview-item .cp-card.cp-minimal {
    padding: 10px;
    border-width: 1px;
    border-radius: 8px;
}
.coupon-preview-item .cp-card.cp-gradient {
    border: none;
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
}
.coupon-preview-item .cp-card.cp-gradient.cp-available {
    background: linear-gradient(135deg, #f0fff4 0%, #e6ffed 100%);
}
.coupon-preview-item .cp-card.cp-gradient.cp-available[data-accent] {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cp-accent) 12%, #fff) 0%, color-mix(in srgb, var(--cp-accent) 6%, #fff) 100%);
}
.coupon-preview-item .cp-card.cp-cardstyle {
    border: 1px solid var(--border);
    background: #fafafa;
}
.coupon-preview-item .cp-card.cp-cardstyle .cp-header {
    padding: 6px 0;
    border-bottom: 1px dashed var(--border);
}
.coupon-preview-item .cp-card.cp-cardstyle.cp-available[data-accent] { border-color: var(--cp-accent); background: #fff; }
/* バーコード風 */
.coupon-preview-item .cp-card.cp-barcode {
    border-left: 4px solid var(--cp-accent, var(--primary));
    background: #fafafa;
}
.coupon-preview-item .cp-card.cp-barcode .cp-barcode-line {
    height: 4px;
    margin: 2px 0;
    background: repeating-linear-gradient(90deg, #333 0, #333 2px, transparent 2px, transparent 4px);
    background-size: 100% 4px;
}
.coupon-preview-item .cp-card.cp-barcode .cp-code-text {
    font-family: monospace;
    font-size: 10px;
    letter-spacing: 1px;
    color: var(--text-secondary);
    margin-top: 4px;
}
/* QR風 */
.coupon-preview-item .cp-card.cp-qr {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: flex-start;
}
.coupon-preview-item .cp-card.cp-qr .cp-qr-block {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    max-width: 80px;
    margin: 0 auto 8px;
}
.coupon-preview-item .cp-card.cp-qr .cp-qr-dot {
    width: 6px;
    height: 6px;
    background: #333;
    border-radius: 1px;
}
.coupon-preview-item .cp-card.cp-qr .cp-header { width: 100%; margin-top: 0; }
/* ストライプ */
.coupon-preview-item .cp-card.cp-stripe {
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
.coupon-preview-item .cp-card.cp-stripe .cp-valid { background: rgba(255,255,255,0.95); padding: 4px; border-radius: 6px; }
/* チケット風 */
.coupon-preview-item .cp-card.cp-ticket {
    border: 2px dashed var(--cp-accent, var(--primary));
    border-radius: 4px;
    background: linear-gradient(#fff 0%, #f8f9fa 100%);
}
.coupon-preview-item .cp-card.cp-ticket .cp-header {
    border-bottom: 1px dashed var(--border);
    padding-bottom: 6px;
}
/* ヴィンテージ */
.coupon-preview-item .cp-card.cp-vintage {
    background: #f5f0e6;
    border: 1px solid #d4c4a0;
    border-radius: 4px;
    color: #5c4a32;
}
.coupon-preview-item .cp-card.cp-vintage .cp-name { color: #3d3225; }
.coupon-preview-item .cp-card.cp-vintage .cp-status { background: #e8dfc8; color: #5c4a32; }
.coupon-preview-item .cp-card.cp-vintage.cp-available[data-accent] { border-color: var(--cp-accent); }
/* ネオン */
.coupon-preview-item .cp-card.cp-neon {
    background: #1a1a2e;
    border: 2px solid var(--cp-accent, #0f0);
    border-radius: 12px;
    color: #eee;
    box-shadow: 0 0 12px color-mix(in srgb, var(--cp-accent, #0f0) 0.5);
}
.coupon-preview-item .cp-card.cp-neon .cp-name { color: #fff; }
.coupon-preview-item .cp-card.cp-neon .cp-name i { color: var(--cp-accent, #0f0); }
.coupon-preview-item .cp-card.cp-neon .cp-image.cp-icon { color: var(--cp-accent, #0f0); }
.coupon-preview-item .cp-card.cp-neon .cp-status {
    background: color-mix(in srgb, var(--cp-accent, #0f0) 0.3);
    color: var(--cp-accent, #0f0);
}
.coupon-preview-item .cp-card.cp-neon .cp-valid { color: #aaa; }
</style>
@endpush

<script>
(function() {
    var LABELS = {
        classic: 'クラシック', modern: 'モダン', minimal: 'ミニマル', gradient: 'グラデーション', card: 'カード風',
        barcode: 'バーコード風', qr: 'QRコード風', stripe: 'ストライプ', ticket: 'チケット風', vintage: 'ヴィンテージ', neon: 'ネオン'
    };
    var DESIGNS = ['classic', 'modern', 'minimal', 'gradient', 'card', 'barcode', 'qr', 'stripe', 'ticket', 'vintage', 'neon'];

    function esc(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function getThemeColor() {
        var el = document.getElementById('theme_color');
        if (!el || !el.value) return '';
        var v = el.value.trim();
        return /^#[0-9A-Fa-f]{6}$/.test(v) ? v : '';
    }
    function card(name, imageUrl, validUntil, design, accent) {
        var accentAttr = accent ? ' data-accent="1" style="--cp-accent: ' + esc(accent) + '"' : '';
        var img = imageUrl
            ? '<div class="cp-image"><img src="' + esc(imageUrl) + '" alt=""></div>'
            : '<div class="cp-image cp-icon"><i class="fas fa-ticket-alt"></i></div>';
        var cls = 'cp-card cp-available cp-' + (design === 'card' ? 'cardstyle' : design);
        var valid = validUntil ? '<p class="cp-valid">有効期限: ' + esc(validUntil) + '</p>' : '';

        if (design === 'barcode') {
            img = '<div class="cp-barcode-line"></div><div class="cp-barcode-line"></div><div class="cp-code-text">' + esc(name) + '</div>';
            return '<div class="' + cls + '"' + accentAttr + '>' + img +
                '<div class="cp-header"><span class="cp-name"><i class="fas fa-barcode"></i>' + esc(name) + '</span><span class="cp-status">利用可能</span></div>' + valid + '</div>';
        }
        if (design === 'qr') {
            var qrDots = [];
            for (var i = 0; i < 25; i++) qrDots.push('<span class="cp-qr-dot"></span>');
            img = '<div class="cp-qr-block">' + qrDots.join('') + '</div>';
            return '<div class="' + cls + '"' + accentAttr + '>' + img +
                '<div class="cp-header"><span class="cp-name"><i class="fas fa-qrcode"></i>' + esc(name) + '</span><span class="cp-status">利用可能</span></div>' + valid + '</div>';
        }
        return '<div class="' + cls + '"' + accentAttr + '>' + img +
            '<div class="cp-header"><span class="cp-name"><i class="fas fa-ticket-alt"></i>' + esc(name) + '</span><span class="cp-status">利用可能</span></div>' + valid + '</div>';
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
        var el = document.getElementById('coupon-preview-inner');
        if (!el) return;
        el.innerHTML = DESIGNS.map(function(d) {
            return '<div class="coupon-preview-item"><span class="coupon-preview-label">' + esc(LABELS[d]) + '</span>' + card(name, imageUrl, validUntil, d, accent) + '</div>';
        }).join('');
    }
    window.updateCouponPreview = update;
    function on() {
        ['name', 'image_url', 'end_at', 'theme_color', 'theme_color_display'].forEach(function(id) {
            var e = document.getElementById(id);
            if (e) { e.addEventListener('input', update); e.addEventListener('change', update); }
        });
        update();
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', on) : on();
})();
</script>
