import { DomHelper } from '../../shared/utils/domHelper';
import { ToastManager } from '../../shared/utils/toast';
import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { isDemoDataMode } from '../demo/demoMode';
import {
    DEMO_COUPON_PRIZES,
    pickRandomPrize,
    readDemoWonCoupons,
    saveDemoWonCoupon,
    type DemoCoupon,
} from '../demo/demoCoupons';

const CAPSULE_COLORS = ['#FF6F91', '#FFC93C', '#7B9BFF', '#5FD3B4', '#FF9A5A', '#B98CE0'];

type Prize = { coupon_name: string; description: string; image_url?: string };

type LotteryEntitlement = {
    id: number;
    reward_type: string;
    expires_at: string | null;
};

type LotteryState = {
    gacha_enabled: boolean;
    scratch_enabled: boolean;
    entitlements: LotteryEntitlement[];
    prize_labels: { gacha: string[]; scratch: string[] };
};

/** ガチャ・スクラッチ（デモは localStorage、本番は DB entitlement） */
export class DesignLabManager {
    apiClient: ApiClient;
    private _state: LotteryState | null = null;
    private _playInFlight = false;

    constructor(apiClient: ApiClient) {
        this.apiClient = apiClient;
    }

    private _lotteryLog(event: string, payload: Record<string, unknown> = {}): void {
        const body = { event, ...payload, ts: Date.now() };
        // eslint-disable-next-line no-console
        console.info('[LotteryUI]', event, body);
        if (typeof window.__clientDebugLog === 'function') {
            window.__clientDebugLog('lottery_ui_' + event, body);
        }
    }

    async load(): Promise<void> {
        const section = document.getElementById('designlabSection') as HTMLElement | null;
        if (section === null) {
            this._lotteryLog('load_skip_no_section');
            return;
        }
        document.body.classList.add('liff-lottery-ui');
        if (!isDemoDataMode()) {
            await this._fetchState();
        }
        this.mount(section);
        this._lotteryLog('load_done', {
            pendingGacha: this._pending('gacha') !== null,
            entitlementCount: this._state?.entitlements?.length ?? 0,
        });
    }

    /**
     * ガチャ筐体だけのポップアップを開く。
     * @param options.force TEMP: 権利がなくても表示して見た目確認する
     */
    async openGachaPopup(options: { force?: boolean } = {}): Promise<void> {
        const force = options.force === true;
        this._lotteryLog('popup_open_start', { force, demo: isDemoDataMode() });
        this.closeGachaPopup();
        document.body.classList.add('liff-lottery-ui');

        if (!isDemoDataMode()) {
            await this._fetchState();
            const pending = this._pending('gacha');
            this._lotteryLog('popup_state', {
                force,
                pendingId: pending?.id ?? null,
                entitlementCount: this._state?.entitlements?.length ?? 0,
                gachaEnabled: this._state?.gacha_enabled ?? false,
            });
            if (pending === null && force !== true) {
                this._lotteryLog('popup_abort_no_pending');
                return;
            }
        }

        const overlay = document.createElement('div');
        overlay.id = 'ppGachaPopup';
        overlay.className = 'pp-gacha-popup';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'ガチャガチャ');
        overlay.innerHTML = `
            <div class="pp-gacha-popup__backdrop" data-gacha-popup-close></div>
            <div class="pp-gacha-popup__panel">
                <button type="button" class="pp-gacha-popup__close" data-gacha-popup-close aria-label="閉じる">×</button>
                ${this._gachaMachineHtml(isDemoDataMode() ? '体験' : 'プレイ可')}
            </div>
        `;
        overlay.addEventListener('click', (event) => {
            const target = event.target as HTMLElement | null;
            if (target !== null && target.closest('[data-gacha-popup-close]')) {
                this.closeGachaPopup();
            }
        });
        document.body.appendChild(overlay);
        void overlay.offsetWidth;
        overlay.classList.add('is-in');

        const panel = overlay.querySelector('.pp-gacha-popup__panel') as HTMLElement | null;
        if (panel === null) {
            this._lotteryLog('popup_abort_no_panel');
            return;
        }
        this._fillDomeCapsules(panel);
        this._bind(panel);
        this._applyAvailability(panel);
        if (force === true && this._pending('gacha') === null) {
            const btn = panel.querySelector('#designGachaBtn') as HTMLButtonElement | null;
            const chip = panel.querySelector('[data-gacha-chip]') as HTMLElement | null;
            if (btn !== null) {
                btn.disabled = false;
                btn.textContent = 'レバーを回す';
            }
            if (chip !== null) {
                chip.textContent = '体験';
            }
        }
        this._lotteryLog('popup_mounted', {
            force,
            hasDome: panel.querySelector('.pp-gacha__dome') !== null,
        });
    }

    closeGachaPopup(): void {
        const existing = document.getElementById('ppGachaPopup');
        if (existing === null) {
            return;
        }
        existing.classList.add('is-out');
        window.setTimeout(() => existing.remove(), 220);
    }

    mount(container: HTMLElement): void {
        const needsRemount = container.dataset.designLabMounted !== '1'
            || container.querySelector('.pp-gacha__dome') === null
            || container.querySelector('.pp-gacha__outlet .pp-gacha__chute') === null
            || container.querySelector('.pp-scratch__circle') === null;
        if (!needsRemount) {
            this._applyAvailability(container);
            this._refreshWonList(container);
            this._focusPlayTarget(container);
            return;
        }
        container.dataset.designLabMounted = '1';
        container.innerHTML = this._html();
        this._bind(container);
        this._fillDomeCapsules(container);
        this._initScratch(container);
        this._applyAvailability(container);
        this._refreshWonList(container);
        this._focusPlayTarget(container);
    }

    private async _fetchState(): Promise<void> {
        try {
            const response = await this.apiClient.get(AppConstants.API_ENDPOINTS.MEMBERS_LOTTERY);
            const raw = response && typeof response === 'object' && 'data' in response
                ? (response as { data?: LotteryState }).data
                : null;
            if (raw !== null && typeof raw === 'object' && Array.isArray(raw.entitlements)) {
                this._state = raw;
            } else if (response !== null && typeof response === 'object' && Array.isArray((response as LotteryState).entitlements)) {
                this._state = response as LotteryState;
            } else {
                this._state = {
                    gacha_enabled: false,
                    scratch_enabled: false,
                    entitlements: [],
                    prize_labels: { gacha: [], scratch: [] },
                };
            }
            this._lotteryLog('fetch_state_ok', {
                entitlementCount: this._state.entitlements.length,
                gachaEnabled: this._state.gacha_enabled,
                pendingGacha: this._pending('gacha')?.id ?? null,
            });
        } catch (error: unknown) {
            const message = error instanceof Error ? error.message : 'unknown';
            this._lotteryLog('fetch_state_error', { message });
            this._state = {
                gacha_enabled: false,
                scratch_enabled: false,
                entitlements: [],
                prize_labels: { gacha: [], scratch: [] },
            };
        }
    }

    private _pending(type: 'gacha' | 'scratch'): LotteryEntitlement | null {
        const list = this._state?.entitlements ?? [];
        return list.find((row) => row.reward_type === type) ?? null;
    }

    private _applyAvailability(container: HTMLElement): void {
        if (isDemoDataMode()) {
            return;
        }
        const gachaBtn = container.querySelector('#designGachaBtn') as HTMLButtonElement | null;
        const gachaChip = container.querySelector('[data-gacha-chip]') as HTMLElement | null;
        const scratchChip = container.querySelector('[data-scratch-chip]') as HTMLElement | null;
        const gachaPending = this._pending('gacha');
        const scratchPending = this._pending('scratch');
        if (gachaBtn !== null) {
            gachaBtn.disabled = gachaPending === null || this._playInFlight;
            gachaBtn.textContent = gachaPending === null ? '権利がありません' : 'レバーを回す';
        }
        if (gachaChip !== null) {
            gachaChip.textContent = gachaPending === null ? '権利なし' : 'プレイ可';
        }
        if (scratchChip !== null) {
            scratchChip.textContent = scratchPending === null ? '権利なし' : 'プレイ可';
        }
        const scratchCanvas = container.querySelector('#designScratchCanvas') as HTMLElement | null;
        if (scratchCanvas !== null) {
            scratchCanvas.style.pointerEvents = scratchPending === null ? 'none' : '';
            scratchCanvas.style.opacity = scratchPending === null ? '0.55' : '1';
        }
    }

    private _focusPlayTarget(container: HTMLElement): void {
        let focus: string | null = null;
        try {
            focus = window.sessionStorage.getItem('liff_demo_play_focus')
                || window.sessionStorage.getItem('liff_lottery_play_focus');
            window.sessionStorage.removeItem('liff_demo_play_focus');
            window.sessionStorage.removeItem('liff_lottery_play_focus');
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

    private _gachaMachineHtml(chipLabel: string): string {
        return `
            <div class="pp-play-card">
                <div class="pp-play-card__top">
                    <span class="pp-play-card__title">クーポンガチャ</span>
                    <span class="pp-play-card__chip" data-gacha-chip>${chipLabel}</span>
                </div>
                <p class="pp-play-card__rule">回すとカプセルが出ます。中身は開くまで見えません。</p>
                <div class="pp-gacha" id="designGachaStage" data-state="idle">
                    <div class="pp-gacha__stage">
                        <div class="pp-gacha__machine" id="designGachaMachine">
                            <span class="pp-gacha__price">1回 無料</span>
                            <div class="pp-gacha__dome">
                                <div class="pp-gacha__dome-glass" id="designGachaDome"></div>
                                <span class="pp-gacha__dome-shine" aria-hidden="true"></span>
                                <span class="pp-gacha__dome-shine pp-gacha__dome-shine--soft" aria-hidden="true"></span>
                                <span class="pp-gacha__dome-rim" aria-hidden="true"></span>
                                <span class="pp-gacha__collar" aria-hidden="true"></span>
                            </div>
                            <div class="pp-gacha__body">
                                <span class="pp-gacha__body-gloss" aria-hidden="true"></span>
                                <span class="pp-gacha__turn-hint" aria-hidden="true"></span>
                                <div class="pp-gacha__knob-wrap">
                                    <button type="button" class="pp-gacha__knob" id="designGachaKnob" aria-label="レバーを回す">
                                        <span class="pp-gacha__knob-plate" aria-hidden="true"></span>
                                        <span class="pp-gacha__knob-bar" aria-hidden="true"></span>
                                        <span class="pp-gacha__knob-hub" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="pp-gacha__outlet" aria-hidden="true">
                                    <span class="pp-gacha__flap"></span>
                                    <div class="pp-gacha__chute">
                                        <div class="pp-gacha__fall" id="designGachaFall">
                                            <span class="pp-gacha__fall-half pp-gacha__fall-half--top"><i></i></span>
                                            <span class="pp-gacha__fall-half pp-gacha__fall-half--bot"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pp-gacha__tray" aria-hidden="true"></div>
                            <div class="pp-gacha__stand" aria-hidden="true"></div>
                        </div>
                    </div>
                    <button type="button" class="pp-cta" id="designGachaBtn">レバーを回す</button>
                </div>
            </div>
        `;
    }

    private _html(): string {
        const isDemo = isDemoDataMode();
        const chip = isDemo ? '体験' : 'プレイ可';
        return `
            <div class="design-lab">
                <section class="design-lab__section" aria-label="ガチャ">
                    ${this._gachaMachineHtml(chip)}
                </section>

                <section class="design-lab__section" aria-label="スクラッチ">
                    <h2 class="design-lab__section-title">スクラッチ</h2>
                    <p class="design-lab__section-lead">円の銀面をこすってクーポンを開けます</p>
                    <div class="pp-play-card">
                        <div class="pp-play-card__top">
                            <span class="pp-play-card__title">スクラッチくじ</span>
                            <span class="pp-play-card__chip" data-scratch-chip>${chip}</span>
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
                            ${isDemo ? '<button type="button" class="pp-cta pp-cta--ghost" id="designScratchReset">やり直す</button>' : ''}
                        </div>
                    </div>
                </section>

                <section class="design-lab__section" aria-label="獲得クーポン">
                    <h2 class="design-lab__section-title">獲得したクーポン</h2>
                    <ul class="design-lab__won" id="designWonList"></ul>
                </section>
            </div>
        `;
    }

    private _fillDomeCapsules(container: HTMLElement): void {
        const dome = container.querySelector('#designGachaDome');
        if (dome === null) {
            return;
        }
        dome.querySelectorAll('.pp-gacha__ball').forEach((el) => el.remove());
        // 詰め込みすぎない山積み（下寄り・上は空ける）
        const layout: Array<{ left: number; top: number; size: number; rot: number; z: number }> = [
            { left: 24, top: 178, size: 52, rot: -14, z: 8 },
            { left: 86, top: 186, size: 56, rot: 10, z: 12 },
            { left: 148, top: 180, size: 52, rot: -8, z: 10 },
            { left: 44, top: 136, size: 50, rot: 18, z: 9 },
            { left: 110, top: 130, size: 54, rot: -16, z: 11 },
            { left: 170, top: 142, size: 48, rot: 12, z: 7 },
            { left: 72, top: 96, size: 46, rot: -10, z: 8 },
            { left: 128, top: 92, size: 44, rot: 16, z: 6 },
        ];
        layout.forEach((item, i) => {
            const ball = document.createElement('span');
            ball.className = 'pp-gacha__ball';
            ball.style.width = `${item.size}px`;
            ball.style.height = `${item.size}px`;
            ball.style.left = `${item.left}px`;
            ball.style.top = `${item.top}px`;
            ball.style.zIndex = String(item.z);
            ball.style.setProperty('--ball-color', CAPSULE_COLORS[i % CAPSULE_COLORS.length]);
            ball.style.setProperty('--ball-rot', `${item.rot}deg`);
            ball.style.setProperty('--ball-scale', String(0.92 + item.z * 0.006));
            ball.style.setProperty('--ball-bright', String(0.88 + item.z * 0.01));
            ball.style.setProperty('--ball-delay', `${(i % 5) * 0.05}s`);
            dome.appendChild(ball);
        });
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
                void this._runGacha(container);
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
        nameEl.textContent = '';
        descEl.textContent = '';
        prizeEl.dataset.awarded = '0';
        if (isDemoDataMode()) {
            const prize = pickRandomPrize();
            prizeEl.dataset.prizeIndex = String(DEMO_COUPON_PRIZES.indexOf(prize));
            prizeEl.dataset.prizeName = prize.coupon_name;
            prizeEl.dataset.prizeDesc = prize.description;
        } else {
            delete prizeEl.dataset.prizeIndex;
            delete prizeEl.dataset.prizeName;
            delete prizeEl.dataset.prizeDesc;
        }
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
                    void this._maybeAwardScratch(container, canvas, prizeEl, progressEl, fillEl, cx, cy, radius, dpr);
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

    private async _maybeAwardScratch(
        container: HTMLElement,
        canvas: HTMLCanvasElement,
        prizeEl: HTMLElement,
        progressEl: HTMLElement | null,
        fillEl: HTMLElement | null,
        cx: number,
        cy: number,
        radius: number,
        dpr: number,
    ): Promise<void> {
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

        let prize: Prize | null = null;
        if (isDemoDataMode()) {
            const prizeIndex = Number(prizeEl.dataset.prizeIndex);
            prize = (Number.isInteger(prizeIndex) && prizeIndex >= 0 && prizeIndex < DEMO_COUPON_PRIZES.length)
                ? DEMO_COUPON_PRIZES[prizeIndex]
                : DEMO_COUPON_PRIZES[0];
            saveDemoWonCoupon(prize, 'scratch');
        } else {
            const pending = this._pending('scratch');
            if (pending === null) {
                prizeEl.dataset.awarded = '0';
                canvas.style.opacity = '1';
                canvas.style.pointerEvents = '';
                canvas.style.transition = '';
                ToastManager.error('スクラッチの権利がありません');
                this._applyAvailability(container);
                return;
            }
            const result = await this._playEntitlement(pending.id);
            if (result === null) {
                prizeEl.dataset.awarded = '0';
                canvas.style.opacity = '1';
                canvas.style.pointerEvents = '';
                canvas.style.transition = '';
                this._applyAvailability(container);
                return;
            }
            prize = {
                coupon_name: result.coupon.name,
                description: result.coupon.description || '',
                image_url: result.coupon.image_url || undefined,
            };
        }

        const nameEl = container.querySelector('#designScratchName') as HTMLElement | null;
        const descEl = container.querySelector('#designScratchDesc') as HTMLElement | null;
        const eyebrow = container.querySelector('#designScratchEyebrow') as HTMLElement | null;
        if (eyebrow !== null) {
            eyebrow.hidden = false;
        }
        if (nameEl !== null && prize !== null) {
            nameEl.textContent = prize.coupon_name;
        }
        if (descEl !== null && prize !== null) {
            descEl.textContent = prize.description;
        }
        if (prize !== null) {
            ToastManager.success(`${prize.coupon_name} を獲得しました`);
            this._refreshWonList(container);
            this._notifyCouponsUpdated(prize.coupon_name);
        }
        this._applyAvailability(container);
    }
    private _notifyCouponsUpdated(name: string): void {
        const manager = (window.app as any)?.couponsManager;
        if (manager !== undefined && typeof manager.onCouponReceived === 'function') {
            manager.onCouponReceived({ message: `${name} を獲得しました`, silent: false });
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

    private async _runGacha(container: HTMLElement): Promise<void> {
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
        if (!isDemoDataMode() && this._pending('gacha') === null) {
            ToastManager.error('ガチャの権利がありません');
            return;
        }
        btn.disabled = true;
        stage.dataset.state = 'spinning';
        const machine = container.querySelector('#designGachaMachine') as HTMLElement | null;
        if (machine !== null) {
            machine.classList.remove('is-shake');
            void machine.offsetWidth;
            machine.classList.add('is-shake');
        }
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

        let prize: Prize | null = null;
        if (isDemoDataMode()) {
            prize = pickRandomPrize();
        } else {
            const pending = this._pending('gacha');
            if (pending !== null) {
                const result = await this._playEntitlement(pending.id);
                if (result !== null) {
                    prize = {
                        coupon_name: result.coupon.name,
                        description: result.coupon.description || '',
                        image_url: result.coupon.image_url || undefined,
                    };
                }
            }
        }

        window.setTimeout(() => {
            fall.classList.add('is-open');
        }, 1300);
        window.setTimeout(() => {
            stage.dataset.state = 'idle';
            machine?.classList.remove('is-shake');
            fall.classList.remove('is-drop', 'is-open');
            if (prize !== null) {
                if (isDemoDataMode()) {
                    saveDemoWonCoupon(prize as (typeof DEMO_COUPON_PRIZES)[number], 'gacha');
                }
                this._showPrizeModal(prize);
                this._refreshWonList(container);
                this._notifyCouponsUpdated(prize.coupon_name);
                ToastManager.success(`${prize.coupon_name} を獲得しました`);
            }
            this._applyAvailability(container);
            if (isDemoDataMode()) {
                btn.disabled = false;
                btn.textContent = 'もう一度回す';
            } else if (this._pending('gacha') === null) {
                btn.disabled = true;
                btn.textContent = '権利がありません';
                if (document.getElementById('ppGachaPopup') !== null) {
                    window.setTimeout(() => this.closeGachaPopup(), 1800);
                }
            } else {
                btn.disabled = false;
                btn.textContent = 'レバーを回す';
            }
        }, 1850);
    }
    private async _playEntitlement(id: number): Promise<{
        coupon: { id: number; name: string; description: string | null; image_url: string | null };
    } | null> {
        if (this._playInFlight) {
            return null;
        }
        this._playInFlight = true;
        try {
            const response = await this.apiClient.post(AppConstants.API_ENDPOINTS.MEMBERS_LOTTERY_PLAY(id), {});
            // apiClient が { success, data } で包む。play API 自体も { success, data } のことがある
            const outer = response && typeof response === 'object' ? response as {
                success?: boolean;
                data?: {
                    success?: boolean;
                    data?: { coupon?: { id: number; name: string; description: string | null; image_url: string | null } };
                    coupon?: { id: number; name: string; description: string | null; image_url: string | null };
                    message?: string;
                };
                message?: string;
            } : null;
            const nested = outer?.data;
            const coupon = nested?.coupon ?? nested?.data?.coupon;
            if (outer?.success !== true || coupon === undefined) {
                const message = nested?.message || outer?.message || 'プレイに失敗しました';
                this._lotteryLog('play_failed', { id, message });
                ToastManager.error(message);
                return null;
            }
            await this._fetchState();
            this._lotteryLog('play_ok', { id, couponName: coupon.name });
            return { coupon };
        } catch (error: any) {
            this._lotteryLog('play_error', { id, message: error?.message || 'unknown' });
            ToastManager.error(error?.message || 'プレイに失敗しました');
            return null;
        } finally {
            this._playInFlight = false;
        }
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
        if (!isDemoDataMode()) {
            list.innerHTML = '<li class="design-lab__won-empty">獲得クーポンはクーポン一覧で確認できます</li>';
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
