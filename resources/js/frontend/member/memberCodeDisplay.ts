import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';

/** 会員証のバーコード・QRコードの描画、カードの裏返し、拡大表示。 */

declare const JsBarcode: any;
declare const QRCode: any;

export function generateBarcode(barcodeId, memberNumber) {
    if (typeof JsBarcode === 'undefined' || !memberNumber) return;
    setTimeout(() => {
        try {
            const el = document.getElementById(barcodeId);
            if (!el) return;
            drawFittedBarcode(el, memberNumber, {
                displayValue: !el.classList.contains('ios-wallet-card__barcode'),
                enlarged: false,
            });
        } catch (err) {
            console.error('Barcode generation error:', err);
        }
    }, AppConstants.ANIMATION_DURATION.BARCODE_DELAY);
}

/**
 * コンテナ幅に合わせて CODE128 を描画する（max-height による横縮みを防ぐ）
 * @param {HTMLElement} el
 * @param {string} memberNumber
 * @param {{ displayValue?: boolean, enlarged?: boolean }} options
 */
function drawFittedBarcode(el, memberNumber, options: { displayValue?: boolean; enlarged?: boolean } = {}) {
    const displayValue = options.displayValue !== false;
    const enlarged = options.enlarged === true;
    const wrap = el.closest(
        '.idnect-card-barcode-wrap, .member-code-enlarge-barcode-wrap, .ios-wallet-card__pass'
    ) || el.parentElement;
    let padX = enlarged ? 40 : 20;
    if (wrap) {
        const wrapStyle = window.getComputedStyle(wrap);
        padX = (parseFloat(wrapStyle.paddingLeft) || 0) + (parseFloat(wrapStyle.paddingRight) || 0);
    }
    const availableWidth = Math.max(enlarged ? 240 : 160, (wrap?.clientWidth || 280) - padX);
    const base = {
        format: 'CODE128',
        height: enlarged ? 160 : (displayValue ? 44 : 64),
        displayValue,
        fontSize: enlarged ? 20 : 11,
        textMargin: displayValue ? 3 : 0,
        margin: enlarged ? 8 : 0,
        background: 'transparent',
        lineColor: '#000000',
    };

    JsBarcode(el, memberNumber, { ...base, width: 1 });
    const probeWidth = parseFloat(el.getAttribute('width') || '0') || 0;
    const barWidth = probeWidth > 0
        ? Math.max(1, Math.min(enlarged ? 4.5 : 4, availableWidth / probeWidth))
        : (enlarged ? 3 : 2);
    JsBarcode(el, memberNumber, { ...base, width: barWidth });
    el.removeAttribute('width');
    el.removeAttribute('height');
    el.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    el.style.width = '100%';
    el.style.height = 'auto';
    el.style.maxWidth = '100%';
    el.style.background = 'transparent';
}

export function generateQR(containerId, memberNumber) {
    if (!memberNumber) return;
    setTimeout(() => {
        const container = document.getElementById(containerId);
        if (!container || typeof QRCode === 'undefined') return;
        try {
            new QRCode(container, {
                text: memberNumber,
                width: 120,
                height: 120,
                colorDark: '#000000',
                colorLight: '#ffffff',
            });
        } catch (err) {
            console.error('QR generation error:', err);
        }
    }, AppConstants.ANIMATION_DURATION.BARCODE_DELAY);
}

export function setupCardFlip() {
    const wrapper = document.querySelector('.idnect-card-wrapper');
    const card = document.querySelector('.idnect-card');
    if (!wrapper || !card) return;
    const flip = () => card.classList.toggle('is-flipped');
    wrapper.addEventListener('click', (e) => {
        e.preventDefault();
        if ((e.target as HTMLElement | null)?.closest('.member-card-edit-btn')) return;
        flip();
    });
    wrapper.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            flip();
        }
    });
}

export function setupCodeEnlargeButton() {
    const btn = document.querySelector('.member-card-enlarge-code-btn');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const memberNumber = (btn.getAttribute('data-member-number') || '').trim();
        const codeType = (btn.getAttribute('data-code-type') || 'barcode').toLowerCase();
        if (!memberNumber) return;
        showEnlargedCode(memberNumber, codeType === 'qrcode');
    });
}

/**
 * 会員証のバーコードまたはQRコードを拡大表示するモーダルを表示する。
 * @param {string} memberNumber - 会員番号
 * @param {boolean} isQR - true のときQRコード、false のときバーコード
 */
function showEnlargedCode(memberNumber, isQR) {
    const overlay = document.createElement('div');
    overlay.className = 'member-code-enlarge-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', isQR ? '会員証 QRコード拡大' : '会員証 バーコード拡大');
    const codeId = 'member-code-enlarge-' + Date.now();
    const title = isQR ? '会員証（QRコード）' : '会員証（バーコード）';
    overlay.innerHTML =
        '<div class="member-code-enlarge-backdrop"></div>' +
        '<div class="member-code-enlarge-content">' +
        '<div class="member-code-enlarge-header">' +
        '<h3 class="member-code-enlarge-title">' + DomHelper.escapeHtml(title) + '</h3>' +
        '<button type="button" class="member-code-enlarge-close" aria-label="閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>' +
        '</div>' +
        '<div class="member-code-enlarge-body">' +
        (isQR
            ? '<div id="' + codeId + '" class="member-code-enlarge-qr-wrap" aria-label="会員番号QRコード"></div>'
            : '<div class="member-code-enlarge-barcode-wrap"><svg id="' + codeId + '" class="member-code-enlarge-barcode-svg"></svg></div>') +
        '</div>' +
        '</div>';
    document.body.appendChild(overlay);

    const close = function () {
        overlay.remove();
    };
    overlay.querySelector('.member-code-enlarge-backdrop').addEventListener('click', close);
    overlay.querySelector('.member-code-enlarge-close').addEventListener('click', close);
    overlay.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            close();
        }
    });

    if (isQR) {
        if (typeof QRCode !== 'undefined') {
            try {
                const container = document.getElementById(codeId);
                if (container) {
                    new QRCode(container, {
                        text: memberNumber,
                        width: 280,
                        height: 280,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                    });
                }
            } catch (err) {
                console.error('QR enlarge error:', err);
            }
        }
    } else {
        if (typeof JsBarcode !== 'undefined') {
            try {
                const enlargeEl = document.getElementById(codeId);
                if (enlargeEl) {
                    drawFittedBarcode(enlargeEl, memberNumber, {
                        displayValue: true,
                        enlarged: true,
                    });
                }
            } catch (err) {
                console.error('Barcode enlarge error:', err);
            }
        }
    }
}
