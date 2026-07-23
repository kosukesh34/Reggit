import { DomHelper } from '../../shared/utils/domHelper';
import { ToastManager } from '../../shared/utils/toast';
import {
    DEMO_COUPON_PRIZES,
    pickRandomPrize,
    readDemoWonCoupons,
    saveDemoWonCoupon,
    type DemoCoupon,
} from '../demo/demoCoupons';

const SCREEN_ITEMS: Array<{ src: string; caption: string }> = [
    { src: '/images/landing/app/screen-home.png', caption: 'ホーム' },
    { src: '/images/landing/app/screen-order.png', caption: '注文' },
    { src: '/images/landing/app/screen-member.png', caption: '会員証' },
    { src: '/images/landing/app/screen-stamps.png', caption: 'スタンプ' },
    { src: '/images/landing/app/screen-queue.png', caption: '順番待ち' },
];

/** デモ用デザインラボ（スクラッチ／ガチャ／画面プレビュー） */
export class DesignLabManager {
    mount(container: HTMLElement): void {
        const needsRemount = container.dataset.designLabMounted !== '1'
            || container.querySelector('.gacha-machine') === null
            || container.querySelector('.design-lab__scratch-circle') === null;
        if (!needsRemount) {
            this._refreshWonList(container);
            return;
        }
        container.dataset.designLabMounted = '1';
        container.innerHTML = this._html();
        this._bind(container);
        this._initScratch(container);
        this._refreshWonList(container);
    }

    private _html(): string {
        const screens = SCREEN_ITEMS.map((item) => `
            <figure class="design-lab__screen">
                <img src="${item.src}?v=2" alt="${DomHelper.escapeHtml(item.caption)}" width="390" height="844" loading="lazy">
                <figcaption>${DomHelper.escapeHtml(item.caption)}</figcaption>
            </figure>
        `).join('');

        return `
            <div class="design-lab">
                <header class="design-lab__hero">
                    <p class="design-lab__eyebrow">Design Lab</p>
                    <h1 class="design-lab__title">デザイン変更</h1>
                    <p class="design-lab__lead">LP掲載イメージの体験と、クーポン配布ゲームです。</p>
                </header>

                <section class="design-lab__section" aria-label="画面イメージ">
                    <h2 class="design-lab__section-title">お客様の手元で完結する体験</h2>
                    <p class="design-lab__section-lead">ホーム・注文・会員・スタンプ・順番待ちのイメージです。</p>
                    <div class="design-lab__rail">${screens}</div>
                </section>

                <section class="design-lab__section" aria-label="スクラッチ">
                    <h2 class="design-lab__section-title">スクラッチ</h2>
                    <p class="design-lab__section-lead">丸い部分をこすってクーポンをゲット</p>
                    <div class="design-lab__scratch-card">
                        <div class="design-lab__scratch-info">
                            <p class="design-lab__scratch-info-label">Scratch Coupon</p>
                            <p class="design-lab__scratch-info-hint">銀面の円をこすって景品を開けよう</p>
                        </div>
                        <div class="design-lab__scratch-circle" id="designScratchCircle">
                            <div class="design-lab__scratch-prize" id="designScratchPrize">
                                <p class="design-lab__scratch-hint">?</p>
                            </div>
                            <canvas class="design-lab__scratch-canvas" id="designScratchCanvas" width="360" height="360" aria-label="スクラッチ円"></canvas>
                        </div>
                        <p class="design-lab__scratch-progress" id="designScratchProgress" aria-live="polite">0%</p>
                    </div>
                    <button type="button" class="design-lab__btn design-lab__btn--ghost" id="designScratchReset">リセット</button>
                </section>

                <section class="design-lab__section" aria-label="ガチャ">
                    <h2 class="design-lab__section-title">ガチャガチャ</h2>
                    <p class="design-lab__section-lead">回してクーポンをゲット</p>
                    <div class="gacha-machine" id="designGachaStage">
                        <div class="gacha-machine__top" aria-hidden="true">
                            <span class="gacha-machine__sign">COUPON GACHA</span>
                        </div>
                        <div class="gacha-machine__glass">
                            <div class="gacha-machine__capsules" aria-hidden="true">
                                <span class="gacha-machine__ball gacha-machine__ball--a"></span>
                                <span class="gacha-machine__ball gacha-machine__ball--b"></span>
                                <span class="gacha-machine__ball gacha-machine__ball--c"></span>
                                <span class="gacha-machine__ball gacha-machine__ball--d"></span>
                                <span class="gacha-machine__ball gacha-machine__ball--e"></span>
                            </div>
                            <div class="gacha-machine__prize" id="designGachaPrize" hidden>
                                <p class="gacha-machine__prize-eyebrow">あたり</p>
                                <p class="gacha-machine__prize-name" id="designGachaLabel">タップして回す</p>
                            </div>
                        </div>
                        <div class="gacha-machine__body">
                            <div class="gacha-machine__knob" id="designGachaCapsule" aria-hidden="true"></div>
                            <div class="gacha-machine__slot" aria-hidden="true">
                                <span class="gacha-machine__dispense" id="designGachaDispense"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="design-lab__btn gacha-machine__cta" id="designGachaBtn">ガチャを回す</button>
                </section>

                <section class="design-lab__section" aria-label="獲得クーポン">
                    <h2 class="design-lab__section-title">獲得したクーポン</h2>
                    <ul class="design-lab__won" id="designWonList"></ul>
                    <button type="button" class="design-lab__btn design-lab__btn--ghost" data-design-nav="coupons">クーポン一覧へ</button>
                </section>
            </div>
        `;
    }

    private _bind(container: HTMLElement): void {
        container.addEventListener('click', (event) => {
            const target = event.target as HTMLElement | null;
            if (target === null) {
                return;
            }
            const nav = target.closest('[data-design-nav]') as HTMLElement | null;
            if (nav !== null && nav.dataset.designNav) {
                if (typeof window.app?.switchTab === 'function') {
                    void window.app.switchTab(nav.dataset.designNav);
                }
                return;
            }
            if (target.closest('#designScratchReset')) {
                this._resetScratch(container);
                return;
            }
            if (target.closest('#designGachaBtn') || target.closest('#designGachaStage')) {
                this._runGacha(container);
            }
        });
    }

    private _initScratch(container: HTMLElement): void {
        const canvas = container.querySelector('#designScratchCanvas') as HTMLCanvasElement | null;
        const prizeEl = container.querySelector('#designScratchPrize') as HTMLElement | null;
        const progressEl = container.querySelector('#designScratchProgress') as HTMLElement | null;
        if (canvas === null || prizeEl === null) {
            return;
        }
        const prize = DEMO_COUPON_PRIZES[0];
        prizeEl.innerHTML = `
            <p class="design-lab__scratch-name">${DomHelper.escapeHtml(prize.coupon_name)}</p>
            <p class="design-lab__scratch-desc">${DomHelper.escapeHtml(prize.description)}</p>
        `;
        prizeEl.dataset.prizeIndex = '0';
        prizeEl.dataset.awarded = '0';
        if (progressEl !== null) {
            progressEl.textContent = '0%';
        }

        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (ctx === null) {
            return;
        }
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const cssSize = Math.max(1, Math.min(rect.width, rect.height));
        if (cssSize < 48) {
            return;
        }
        const pixelSize = Math.max(1, Math.round(cssSize * dpr));
        canvas.width = pixelSize;
        canvas.height = pixelSize;
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);

        const radius = cssSize / 2;
        const cx = radius;
        const cy = radius;
        const brush = Math.max(18, cssSize * 0.1);
        const arcRadius = Math.max(1, radius - 1);

        const paintFoil = () => {
            ctx.save();
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.clearRect(0, 0, cssSize, cssSize);
            ctx.beginPath();
            ctx.arc(cx, cy, arcRadius, 0, Math.PI * 2);
            ctx.closePath();
            ctx.clip();

            const base = ctx.createLinearGradient(0, 0, cssSize, cssSize);
            base.addColorStop(0, '#E8ECED');
            base.addColorStop(0.35, '#B8C0C4');
            base.addColorStop(0.7, '#9AA3A8');
            base.addColorStop(1, '#7E878C');
            ctx.fillStyle = base;
            ctx.fillRect(0, 0, cssSize, cssSize);

            const shine = ctx.createRadialGradient(cx * 0.55, cy * 0.4, 4, cx, cy, radius);
            shine.addColorStop(0, 'rgba(255,255,255,0.55)');
            shine.addColorStop(0.45, 'rgba(255,255,255,0.08)');
            shine.addColorStop(1, 'rgba(0,0,0,0.12)');
            ctx.fillStyle = shine;
            ctx.fillRect(0, 0, cssSize, cssSize);

            ctx.fillStyle = 'rgba(40,48,52,0.72)';
            ctx.font = `700 ${Math.max(13, cssSize * 0.075)}px "Manrope", "Noto Sans JP", sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('こすってね', cx, cy - 8);
            ctx.font = `600 ${Math.max(10, cssSize * 0.045)}px "Manrope", "Noto Sans JP", sans-serif`;
            ctx.fillStyle = 'rgba(40,48,52,0.45)';
            ctx.fillText('SCRATCH', cx, cy + 14);
            ctx.restore();
        };
        paintFoil();

        let drawing = false;
        let lastX = 0;
        let lastY = 0;
        let checkQueued = false;

        const eraseStroke = (x: number, y: number, fromX: number, fromY: number) => {
            ctx.save();
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.beginPath();
            ctx.arc(cx, cy, arcRadius, 0, Math.PI * 2);
            ctx.clip();
            ctx.globalCompositeOperation = 'destination-out';
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = brush * 2;
            ctx.beginPath();
            ctx.moveTo(fromX, fromY);
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(x, y, brush, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        };

        const pointInCircle = (x: number, y: number): boolean => {
            const dx = x - cx;
            const dy = y - cy;
            return (dx * dx) + (dy * dy) <= (radius + brush) * (radius + brush);
        };

        const scratchAt = (clientX: number, clientY: number, isStart: boolean) => {
            const box = canvas.getBoundingClientRect();
            const x = clientX - box.left;
            const y = clientY - box.top;
            if (!pointInCircle(x, y)) {
                lastX = x;
                lastY = y;
                return;
            }
            if (isStart) {
                eraseStroke(x, y, x, y);
            } else {
                eraseStroke(x, y, lastX, lastY);
            }
            lastX = x;
            lastY = y;
            if (!checkQueued) {
                checkQueued = true;
                window.requestAnimationFrame(() => {
                    checkQueued = false;
                    this._maybeAwardScratch(container, canvas, prizeEl, progressEl, cx, cy, radius, dpr);
                });
            }
        };

        const onStart = (e: PointerEvent) => {
            drawing = true;
            canvas.setPointerCapture(e.pointerId);
            scratchAt(e.clientX, e.clientY, true);
        };
        const onMove = (e: PointerEvent) => {
            if (!drawing) {
                return;
            }
            scratchAt(e.clientX, e.clientY, false);
        };
        const onEnd = () => {
            drawing = false;
        };
        canvas.addEventListener('pointerdown', onStart);
        canvas.addEventListener('pointermove', onMove);
        canvas.addEventListener('pointerup', onEnd);
        canvas.addEventListener('pointercancel', onEnd);
    }

    private _maybeAwardScratch(
        container: HTMLElement,
        canvas: HTMLCanvasElement,
        prizeEl: HTMLElement,
        progressEl: HTMLElement | null,
        cx: number,
        cy: number,
        radius: number,
        dpr: number,
    ): void {
        if (prizeEl.dataset.awarded === '1') {
            return;
        }
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (ctx === null) {
            return;
        }
        const { width, height } = canvas;
        const data = ctx.getImageData(0, 0, width, height).data;
        const centerX = cx * dpr;
        const centerY = cy * dpr;
        const radiusPx = radius * dpr;
        const radiusSq = radiusPx * radiusPx;
        let cleared = 0;
        let total = 0;
        const step = 6;
        for (let y = 0; y < height; y += step) {
            for (let x = 0; x < width; x += step) {
                const dx = x - centerX;
                const dy = y - centerY;
                if ((dx * dx) + (dy * dy) > radiusSq) {
                    continue;
                }
                total += 1;
                const alpha = data[((y * width) + x) * 4 + 3];
                if (alpha < 128) {
                    cleared += 1;
                }
            }
        }
        if (total === 0) {
            return;
        }
        const ratio = cleared / total;
        if (progressEl !== null) {
            progressEl.textContent = `${Math.min(100, Math.round(ratio * 100))}%`;
        }
        if (ratio < 0.5) {
            return;
        }
        prizeEl.dataset.awarded = '1';
        canvas.style.pointerEvents = 'none';
        canvas.style.opacity = '0';
        if (progressEl !== null) {
            progressEl.textContent = '100%';
        }
        const prize = DEMO_COUPON_PRIZES[0];
        const coupon = saveDemoWonCoupon(prize, 'scratch');
        ToastManager.success(`${coupon.coupon_name} を獲得しました`);
        this._refreshWonList(container);
    }

    private _resetScratch(container: HTMLElement): void {
        const old = container.querySelector('#designScratchCanvas') as HTMLCanvasElement | null;
        if (old !== null) {
            const fresh = old.cloneNode(false) as HTMLCanvasElement;
            fresh.className = old.className;
            fresh.id = old.id;
            fresh.width = 360;
            fresh.height = 360;
            fresh.setAttribute('aria-label', 'スクラッチ円');
            fresh.style.pointerEvents = '';
            fresh.style.opacity = '1';
            old.replaceWith(fresh);
        }
        const prizeEl = container.querySelector('#designScratchPrize') as HTMLElement | null;
        if (prizeEl !== null) {
            prizeEl.dataset.awarded = '0';
        }
        const progressEl = container.querySelector('#designScratchProgress') as HTMLElement | null;
        if (progressEl !== null) {
            progressEl.textContent = '0%';
        }
        this._initScratch(container);
    }

    private _runGacha(container: HTMLElement): void {
        const stage = container.querySelector('#designGachaStage') as HTMLElement | null;
        const label = container.querySelector('#designGachaLabel') as HTMLElement | null;
        const prizeBox = container.querySelector('#designGachaPrize') as HTMLElement | null;
        const dispense = container.querySelector('#designGachaDispense') as HTMLElement | null;
        const btn = container.querySelector('#designGachaBtn') as HTMLButtonElement | null;
        if (stage === null || label === null || btn === null || btn.disabled) {
            return;
        }
        btn.disabled = true;
        stage.classList.remove('is-win');
        stage.classList.add('is-spinning');
        if (prizeBox !== null) {
            prizeBox.hidden = true;
        }
        if (dispense !== null) {
            dispense.className = 'gacha-machine__dispense';
        }
        label.textContent = '抽選中…';
        window.setTimeout(() => {
            const prize = pickRandomPrize();
            const coupon = saveDemoWonCoupon(prize, 'gacha');
            stage.classList.remove('is-spinning');
            stage.classList.add('is-win');
            if (prizeBox !== null) {
                prizeBox.hidden = false;
            }
            if (dispense !== null) {
                dispense.classList.add('is-out');
            }
            label.textContent = coupon.coupon_name;
            ToastManager.success(`${coupon.coupon_name} を獲得しました`);
            this._refreshWonList(container);
            window.setTimeout(() => {
                btn.disabled = false;
                btn.textContent = 'もう一度回す';
            }, 900);
        }, 1400);
    }

    private _refreshWonList(container: HTMLElement): void {
        const list = container.querySelector('#designWonList');
        if (list === null) {
            return;
        }
        const won = readDemoWonCoupons();
        if (won.length === 0) {
            list.innerHTML = '<li class="design-lab__won-empty">まだありません</li>';
            return;
        }
        list.innerHTML = won.map((c: DemoCoupon) => `
            <li class="design-lab__won-item">
                <span class="design-lab__won-badge">${c.source === 'scratch' ? 'スクラッチ' : 'ガチャ'}</span>
                <span class="design-lab__won-name">${DomHelper.escapeHtml(c.coupon_name)}</span>
            </li>
        `).join('');
    }
}
