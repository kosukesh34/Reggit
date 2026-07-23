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

const CAPSULE_COLORS = ['#FF6F91', '#FFC93C', '#7B9BFF', '#5FD3B4', '#FF9A5A', '#B98CE0'];

type Prize = (typeof DEMO_COUPON_PRIZES)[number];

/** デモ用デザインラボ（スクラッチ／ガチャ／画面プレビュー） */
export class DesignLabManager {
    mount(container: HTMLElement): void {
        const needsRemount = container.dataset.designLabMounted !== '1'
            || container.querySelector('.pp-gacha__dome') === null
            || container.querySelector('.pp-scratch__circle') === null;
        if (!needsRemount) {
            this._refreshWonList(container);
            this._focusPlayTarget(container);
            return;
        }
        container.dataset.designLabMounted = '1';
        container.innerHTML = this._html();
        this._bind(container);
        this._fillDomeCapsules(container);
        this._initScratch(container);
        this._refreshWonList(container);
        this._focusPlayTarget(container);
    }

    private _focusPlayTarget(container: HTMLElement): void {
        let focus: string | null = null;
        try {
            focus = window.sessionStorage.getItem('liff_demo_play_focus');
            window.sessionStorage.removeItem('liff_demo_play_focus');
        } catch {
            focus = null;
        }
        const target = focus === 'scratch'
            ? container.querySelector('.pp-scratch')
            : (focus === 'gacha' ? container.querySelector('.pp-gacha') : null);
        if (target === null) {
            return;
        }
        window.requestAnimationFrame(() => {
            (target as HTMLElement).scrollIntoView({ block: 'center', behavior: 'smooth' });
        });
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
                    <p class="design-lab__eyebrow">Demo Play</p>
                    <h1 class="design-lab__title">ガチャ・スクラッチ</h1>
                    <p class="design-lab__lead">遊んでクーポンを獲得できます。獲得したクーポンはクーポン一覧に保存されます。</p>
                </header>

                <section class="design-lab__section" aria-label="画面イメージ">
                    <h2 class="design-lab__section-title">お客様の手元で完結する体験</h2>
                    <p class="design-lab__section-lead">ホーム・注文・会員・スタンプ・順番待ちのイメージです。</p>
                    <div class="design-lab__rail">${screens}</div>
                </section>

                <section class="design-lab__section" aria-label="ガチャ">
                    <h2 class="design-lab__section-title">ガチャガチャ</h2>
                    <p class="design-lab__section-lead">レバーを回してカプセルを取り出し、中身を開けます</p>
                    <div class="pp-play-card">
                        <div class="pp-play-card__top">
                            <span class="pp-play-card__title">クーポンガチャ</span>
                            <span class="pp-play-card__chip">デモ</span>
                        </div>
                        <p class="pp-play-card__rule">回すとカプセルが出ます。中身は開くまで見えません。</p>
                        <div class="pp-gacha" id="designGachaStage" data-state="idle">
                            <div class="pp-gacha__machine">
                                <span class="pp-gacha__price">1回 無料</span>
                                <div class="pp-gacha__dome" id="designGachaDome"><div class="pp-gacha__gloss"></div></div>
                                <div class="pp-gacha__body">
                                    <span class="pp-gacha__rivet pp-gacha__rivet--l"></span>
                                    <span class="pp-gacha__rivet pp-gacha__rivet--r"></span>
                                    <div class="pp-gacha__slot"></div>
                                    <div class="pp-gacha__knob-wrap">
                                        <button type="button" class="pp-gacha__knob" id="designGachaKnob" aria-label="レバーを回す"></button>
                                    </div>
                                    <div class="pp-gacha__flap"></div>
                                </div>
                                <div class="pp-gacha__tray"></div>
                                <div class="pp-gacha__fall" id="designGachaFall" aria-hidden="true">
                                    <span class="pp-gacha__fall-half pp-gacha__fall-half--top"><i></i></span>
                                    <span class="pp-gacha__fall-half pp-gacha__fall-half--bot"><i></i></span>
                                </div>
                            </div>
                            <button type="button" class="pp-cta" id="designGachaBtn">レバーを回す</button>
                        </div>
                    </div>
                </section>

                <section class="design-lab__section" aria-label="スクラッチ">
                    <h2 class="design-lab__section-title">スクラッチ</h2>
                    <p class="design-lab__section-lead">円の銀面をこすってクーポンを開けます</p>
                    <div class="pp-play-card">
                        <div class="pp-play-card__top">
                            <span class="pp-play-card__title">スクラッチくじ</span>
                            <span class="pp-play-card__chip">デモ</span>
                        </div>
                        <p class="pp-play-card__rule">銀面をこすって結果を確認しましょう。中身は削るまで見えません。</p>
                        <div class="pp-scratch" id="designScratchStage" data-state="sealed">
                            <div class="pp-scratch__circle" id="designScratchCircle">
                                <div class="pp-scratch__prize" id="designScratchPrize">
                                    <p class="pp-scratch__prize-eyebrow" id="designScratchEyebrow" hidden>あたり</p>
                                    <p class="pp-scratch__prize-name" id="designScratchName"></p>
                                    <p class="pp-scratch__prize-desc" id="designScratchDesc"></p>
                                </div>
                                <canvas class="pp-scratch__canvas" id="designScratchCanvas" width="360" height="360" aria-label="スクラッチ円"></canvas>
                                <div class="pp-scratch__shine" aria-hidden="true"></div>
                            </div>
                            <div class="pp-scratch__progress-row">
                                <div class="pp-scratch__track"><div class="pp-scratch__fill" id="designScratchFill"></div></div>
                                <span class="pp-scratch__pct" id="designScratchProgress">0%</span>
                            </div>
                            <p class="pp-scratch__hint" id="designScratchHint">円の中をこすってください</p>
                            <button type="button" class="pp-cta pp-cta--ghost" id="designScratchReset">新しいカード</button>
                        </div>
                    </div>
                </section>

                <section class="design-lab__section" aria-label="獲得クーポン">
                    <h2 class="design-lab__section-title">獲得したクーポン</h2>
                    <ul class="design-lab__won" id="designWonList"></ul>
                    <button type="button" class="design-lab__btn design-lab__btn--ghost" data-design-nav="coupons">クーポン一覧へ</button>
                </section>
            </div>
        `;
    }

    private _fillDomeCapsules(container: HTMLElement): void {
        const dome = container.querySelector('#designGachaDome');
        if (dome === null) {
            return;
        }
        for (let i = 0; i < 12; i += 1) {
            const ball = document.createElement('span');
            ball.className = 'pp-gacha__ball';
            const size = 20 + Math.floor(Math.random() * 10);
            ball.style.width = `${size}px`;
            ball.style.height = `${size}px`;
            ball.style.left = `${10 + Math.random() * 148}px`;
            ball.style.top = `${52 + Math.random() * 70}px`;
            ball.style.setProperty('--ball-color', CAPSULE_COLORS[i % CAPSULE_COLORS.length]);
            dome.appendChild(ball);
        }
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
            if (target.closest('#designGachaBtn') || target.closest('#designGachaKnob')) {
                this._runGacha(container);
            }
        });
    }

    private _initScratch(container: HTMLElement): void {
        const canvas = container.querySelector('#designScratchCanvas') as HTMLCanvasElement | null;
        const prizeEl = container.querySelector('#designScratchPrize') as HTMLElement | null;
        const nameEl = container.querySelector('#designScratchName') as HTMLElement | null;
        const descEl = container.querySelector('#designScratchDesc') as HTMLElement | null;
        const progressEl = container.querySelector('#designScratchProgress') as HTMLElement | null;
        const fillEl = container.querySelector('#designScratchFill') as HTMLElement | null;
        const stage = container.querySelector('#designScratchStage') as HTMLElement | null;
        if (canvas === null || prizeEl === null || nameEl === null || descEl === null || stage === null) {
            return;
        }
        const prize = pickRandomPrize();
        nameEl.textContent = '';
        descEl.textContent = '';
        prizeEl.dataset.prizeIndex = String(DEMO_COUPON_PRIZES.indexOf(prize));
        prizeEl.dataset.awarded = '0';
        prizeEl.dataset.prizeName = prize.coupon_name;
        prizeEl.dataset.prizeDesc = prize.description;
        const eyebrow = container.querySelector('#designScratchEyebrow') as HTMLElement | null;
        if (eyebrow !== null) {
            eyebrow.hidden = true;
        }
        stage.dataset.state = 'sealed';
        if (progressEl !== null) {
            progressEl.textContent = '0%';
        }
        if (fillEl !== null) {
            fillEl.style.width = '0%';
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
        canvas.style.opacity = '1';
        canvas.style.pointerEvents = '';
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);

        const radius = cssSize / 2;
        const cx = radius;
        const cy = radius;
        const brush = Math.max(16, cssSize * 0.09);
        const arcRadius = Math.max(1, radius - 1);

        const paintFoil = () => {
            const drawClipped = (drawBody: () => void) => {
                ctx.save();
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                ctx.clearRect(0, 0, cssSize, cssSize);
                ctx.beginPath();
                ctx.arc(cx, cy, arcRadius, 0, Math.PI * 2);
                ctx.closePath();
                ctx.clip();
                drawBody();
                ctx.fillStyle = 'rgba(26,26,26,0.55)';
                ctx.font = `900 ${Math.max(13, cssSize * 0.07)}px "Noto Sans JP", sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('こすってね', cx, cy - 8);
                ctx.font = `700 ${Math.max(10, cssSize * 0.045)}px "Noto Sans JP", sans-serif`;
                ctx.fillStyle = 'rgba(26,26,26,0.4)';
                ctx.fillText('SCRATCH', cx, cy + 14);
                ctx.restore();
            };

            const paintGradient = () => {
                drawClipped(() => {
                    const base = ctx.createLinearGradient(0, 0, cssSize, cssSize);
                    base.addColorStop(0, '#DCE0E8');
                    base.addColorStop(0.5, '#AEB4C0');
                    base.addColorStop(1, '#8A909C');
                    ctx.fillStyle = base;
                    ctx.fillRect(0, 0, cssSize, cssSize);
                    ctx.strokeStyle = 'rgba(255,255,255,0.35)';
                    ctx.lineWidth = 2;
                    for (let i = -cssSize; i < cssSize; i += 6) {
                        ctx.beginPath();
                        ctx.moveTo(i, cssSize);
                        ctx.lineTo(i + cssSize, 0);
                        ctx.stroke();
                    }
                });
            };

            const foil = new Image();
            foil.onload = () => {
                if (prizeEl.dataset.awarded === '1') {
                    return;
                }
                drawClipped(() => {
                    ctx.drawImage(foil, 0, 0, cssSize, cssSize);
                });
            };
            foil.onerror = paintGradient;
            foil.src = '/images/liff/scratch-foil.jpg';
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

        const scratchAt = (clientX: number, clientY: number, isStart: boolean) => {
            const box = canvas.getBoundingClientRect();
            const x = clientX - box.left;
            const y = clientY - box.top;
            const dx = x - cx;
            const dy = y - cy;
            if ((dx * dx) + (dy * dy) > (radius + brush) * (radius + brush)) {
                lastX = x;
                lastY = y;
                return;
            }
            eraseStroke(x, y, isStart ? x : lastX, isStart ? y : lastY);
            lastX = x;
            lastY = y;
            if (!checkQueued) {
                checkQueued = true;
                window.requestAnimationFrame(() => {
                    checkQueued = false;
                    this._maybeAwardScratch(container, canvas, prizeEl, progressEl, fillEl, cx, cy, radius, dpr);
                });
            }
        };

        const onStart = (e: PointerEvent) => {
            if (prizeEl.dataset.awarded === '1') {
                return;
            }
            drawing = true;
            try {
                canvas.setPointerCapture(e.pointerId);
            } catch {
                // synthetic / unsupported pointer capture
            }
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
        fillEl: HTMLElement | null,
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
        const radiusSq = (radius * dpr) * (radius * dpr);
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
                if (data[((y * width) + x) * 4 + 3] < 128) {
                    cleared += 1;
                }
            }
        }
        if (total === 0) {
            return;
        }
        const ratio = cleared / total;
        const pct = Math.min(100, Math.round(ratio * 100));
        if (progressEl !== null) {
            progressEl.textContent = `${pct}%`;
        }
        if (fillEl !== null) {
            fillEl.style.width = `${pct}%`;
        }
        if (ratio < 0.5) {
            return;
        }
        prizeEl.dataset.awarded = '1';
        canvas.style.transition = 'opacity 0.4s ease';
        canvas.style.opacity = '0';
        canvas.style.pointerEvents = 'none';
        const shine = container.querySelector('.pp-scratch__shine') as HTMLElement | null;
        if (shine !== null) {
            shine.hidden = true;
        }
        if (progressEl !== null) {
            progressEl.textContent = '100%';
        }
        if (fillEl !== null) {
            fillEl.style.width = '100%';
        }
        const stage = container.querySelector('#designScratchStage') as HTMLElement | null;
        if (stage !== null) {
            stage.dataset.state = 'revealed';
        }
        const hint = container.querySelector('#designScratchHint') as HTMLElement | null;
        if (hint !== null) {
            hint.textContent = 'クーポンを獲得しました';
        }
        const prizeIndex = Number(prizeEl.dataset.prizeIndex);
        const prize = (Number.isInteger(prizeIndex) && prizeIndex >= 0 && prizeIndex < DEMO_COUPON_PRIZES.length)
            ? DEMO_COUPON_PRIZES[prizeIndex]
            : DEMO_COUPON_PRIZES[0];
        const nameEl = container.querySelector('#designScratchName') as HTMLElement | null;
        const descEl = container.querySelector('#designScratchDesc') as HTMLElement | null;
        const eyebrow = container.querySelector('#designScratchEyebrow') as HTMLElement | null;
        if (eyebrow !== null) {
            eyebrow.hidden = false;
        }
        if (nameEl !== null) {
            nameEl.textContent = prize.coupon_name;
        }
        if (descEl !== null) {
            descEl.textContent = prize.description;
        }
        const coupon = saveDemoWonCoupon(prize, 'scratch');
        ToastManager.success(`${coupon.coupon_name} を獲得しました`);
        this._refreshWonList(container);
        this._notifyCouponsUpdated(coupon.coupon_name);
    }

    private _notifyCouponsUpdated(couponName: string): void {
        const couponsManager = window.app?.couponsManager as
            | { onCouponReceived?: (opts: { message?: string; silent?: boolean }) => void | Promise<void> }
            | undefined;
        if (couponsManager !== undefined && typeof couponsManager.onCouponReceived === 'function') {
            void couponsManager.onCouponReceived({
                message: `${couponName} を獲得しました`,
                silent: false,
            });
        }
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
            fresh.style.transition = '';
            old.replaceWith(fresh);
        }
        const shine = container.querySelector('.pp-scratch__shine') as HTMLElement | null;
        if (shine !== null) {
            shine.hidden = false;
        }
        const hint = container.querySelector('#designScratchHint') as HTMLElement | null;
        if (hint !== null) {
            hint.textContent = '円の中をこすってください';
        }
        this._initScratch(container);
    }

    private _runGacha(container: HTMLElement): void {
        const stage = container.querySelector('#designGachaStage') as HTMLElement | null;
        const knob = container.querySelector('#designGachaKnob') as HTMLElement | null;
        const fall = container.querySelector('#designGachaFall') as HTMLElement | null;
        const btn = container.querySelector('#designGachaBtn') as HTMLButtonElement | null;
        if (stage === null || fall === null || btn === null || btn.disabled) {
            return;
        }
        if (stage.dataset.state === 'spinning') {
            return;
        }
        btn.disabled = true;
        stage.dataset.state = 'spinning';
        if (knob !== null) {
            knob.classList.remove('is-spin');
            void knob.offsetWidth;
            knob.classList.add('is-spin');
        }
        const color = CAPSULE_COLORS[Math.floor(Math.random() * CAPSULE_COLORS.length)];
        fall.style.setProperty('--cap-color', color);
        fall.classList.remove('is-drop', 'is-open');
        void fall.offsetWidth;
        fall.classList.add('is-drop');
        const prize = pickRandomPrize();
        window.setTimeout(() => {
            fall.classList.add('is-open');
        }, 950);
        window.setTimeout(() => {
            stage.dataset.state = 'idle';
            fall.classList.remove('is-drop', 'is-open');
            const coupon = saveDemoWonCoupon(prize, 'gacha');
            this._showPrizeModal(prize);
            this._refreshWonList(container);
            this._notifyCouponsUpdated(coupon.coupon_name);
            ToastManager.success(`${coupon.coupon_name} を獲得しました`);
            btn.disabled = false;
            btn.textContent = 'もう一度回す';
        }, 1350);
    }

    private _showPrizeModal(prize: Prize): void {
        document.getElementById('designPrizeModal')?.remove();
        const overlay = document.createElement('div');
        overlay.id = 'designPrizeModal';
        overlay.className = 'pp-prize-modal';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', '当選結果');
        overlay.innerHTML = `
            <div class="pp-prize-modal__backdrop" data-prize-close></div>
            <div class="pp-prize-modal__panel">
                <span class="pp-prize-modal__ribbon">あたり</span>
                <p class="pp-prize-modal__name">${DomHelper.escapeHtml(prize.coupon_name)}</p>
                <p class="pp-prize-modal__desc">${DomHelper.escapeHtml(prize.description)}</p>
                <button type="button" class="pp-cta" data-prize-close>受け取る</button>
                <p class="pp-prize-modal__sub">獲得したクーポンは一覧に保存されます</p>
            </div>
        `;
        const close = () => {
            overlay.classList.add('is-out');
            window.setTimeout(() => overlay.remove(), 220);
        };
        overlay.addEventListener('click', (event) => {
            const t = event.target as HTMLElement | null;
            if (t !== null && t.closest('[data-prize-close]')) {
                close();
            }
        });
        document.body.appendChild(overlay);
        void overlay.offsetWidth;
        overlay.classList.add('is-in');
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
