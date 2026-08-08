import { DomHelper } from '../../../shared/utils/domHelper';
import { ToastManager } from '../../../shared/utils/toast';
import type { ApiClient } from '../../../shared/utils/apiClient';
import { AppConstants } from '../../../shared/constants';
import { isDemoDataMode } from '../../demo/demoMode';
import {
    DEMO_COUPON_PRIZES,
    pickRandomPrize,
    readDemoWonCoupons,
    saveDemoWonCoupon,
    type DemoCoupon,
} from '../../demo/demoCoupons';

const CAPSULE_COLORS = ['#FF6F91', '#FFC93C', '#7B9BFF', '#5FD3B4', '#FF9A5A', '#B98CE0'];

type Prize = {
    coupon_name: string;
    description: string;
    image_url?: string;
    is_win?: boolean;
    entitlement_id?: number;
};

type PlayResult = {
    is_win: boolean;
    entitlement_id: number;
    coupon: { id: number; name: string; description: string | null; image_url: string | null } | null;
};

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

    async load(container?: HTMLElement | null): Promise<void> {
        const section = container
            ?? (document.getElementById('designlabSection') as HTMLElement | null);
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
     * デモ以外は未消化のガチャ権利があるときだけ表示する。
     * @returns 表示した場合 true
     */
    async openGachaPopup(): Promise<boolean> {
        this._lotteryLog('popup_open_start', { demo: isDemoDataMode() });
        this.closeGachaPopup();
        document.body.classList.add('liff-lottery-ui');

        if (!isDemoDataMode()) {
            await this._fetchState();
            const pending = this._pending('gacha');
            this._lotteryLog('popup_state', {
                pendingId: pending?.id ?? null,
                entitlementCount: this._state?.entitlements?.length ?? 0,
                gachaEnabled: this._state?.gacha_enabled ?? false,
            });
            if (pending === null) {
                this._lotteryLog('popup_abort_no_pending');
                ToastManager.info('プレイできる権利がありません');
                return false;
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
            return false;
        }
        this._fillDomeCapsules(panel);
        this._bind(panel);
        this._applyAvailability(panel);
        this._lotteryLog('popup_mounted', {
            hasDome: panel.querySelector('.pp-gacha__dome') !== null,
        });
        return true;
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
        const showGacha = isDemoDataMode() || this._state?.gacha_enabled === true;
        const showScratch = isDemoDataMode() || this._state?.scratch_enabled === true;
        const showRoulette = isDemoDataMode();
        const needsRemount = container.dataset.designLabMounted !== '1'
            || (showGacha && container.querySelector('.pp-gacha__dome') === null)
            || (showGacha && container.querySelector('.pp-gacha__outlet .pp-gacha__chute') === null)
            || (showScratch && container.querySelector('.pp-scratch__circle') === null)
            || (showScratch && container.querySelector('#designScratchImage') === null)
            || (showGacha && container.querySelectorAll('.pp-gacha__ball').length < 9)
            || (showRoulette && container.querySelector('.pp-roulette__lights') === null);
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
        this._paintRoulette(container);
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
        const gachaSection = container.querySelector('[data-lottery-section="gacha"]') as HTMLElement | null;
        const scratchSection = container.querySelector('[data-lottery-section="scratch"]') as HTMLElement | null;
        if (gachaSection !== null) {
            gachaSection.hidden = this._state?.gacha_enabled !== true;
        }
        if (scratchSection !== null) {
            scratchSection.hidden = this._state?.scratch_enabled !== true;
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
            : (focus === 'roulette'
                ? container.querySelector('.pp-roulette')
                : (focus === 'gacha' ? container.querySelector('.pp-gacha') : null));
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
                            <span class="pp-gacha__price">権利で1回</span>
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
        const showGacha = isDemo || this._state?.gacha_enabled === true;
        const showScratch = isDemo || this._state?.scratch_enabled === true;
        const gachaSection = showGacha
            ? `<section class="design-lab__section" data-lottery-section="gacha" aria-label="ガチャ">${this._gachaMachineHtml(chip)}</section>`
            : '';
        const scratchSection = showScratch
            ? `
                <section class="design-lab__section" data-lottery-section="scratch" aria-label="スクラッチ">
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
                                    <p class="pp-scratch__prize-mystery" id="designScratchMystery" aria-hidden="true" hidden>?</p>
                                    <img class="pp-scratch__prize-image" id="designScratchImage" alt="" hidden>
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
            `
            : '';
        const rouletteSection = isDemo
            ? `
                <section class="design-lab__section" data-lottery-section="roulette" aria-label="ルーレット">
                    <h2 class="design-lab__section-title">ルーレット</h2>
                    <p class="design-lab__section-lead">回して止まったマスのクーポンを獲得します</p>
                    <div class="pp-play-card">
                        <div class="pp-play-card__top">
                            <span class="pp-play-card__title">クーポンルーレット</span>
                            <span class="pp-play-card__chip">体験</span>
                        </div>
                        <p class="pp-play-card__rule">針の先に止まった景品が当たりです。</p>
                        <div class="pp-roulette" id="designRouletteStage" data-state="idle">
                            <div class="pp-roulette__stage">
                                <div class="pp-roulette__frame">
                                    <span class="pp-roulette__glow" aria-hidden="true"></span>
                                    <div class="pp-roulette__lights" aria-hidden="true" id="designRouletteLights"></div>
                                    <span class="pp-roulette__pointer" aria-hidden="true"></span>
                                    <span class="pp-roulette__rim" aria-hidden="true"></span>
                                    <div class="pp-roulette__wheel" id="designRouletteWheel" role="img" aria-label="ルーレット盤"></div>
                                    <div class="pp-roulette__hub" aria-hidden="true"><span></span></div>
                                </div>
                                <div class="pp-roulette__pedestal" aria-hidden="true"></div>
                            </div>
                            <button type="button" class="pp-cta" id="designRouletteBtn">ルーレットを回す</button>
                        </div>
                    </div>
                </section>
            `
            : '';
        const emptyLead = !showGacha && !showScratch && !isDemo
            ? '<p class="design-lab__section-lead">現在あそべるコンテンツはありません</p>'
            : '';
        return `
            <div class="design-lab">
                ${emptyLead}
                ${gachaSection}
                ${scratchSection}
                ${rouletteSection}
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
        dome.querySelectorAll('.pp-gacha__ball, .pp-gacha__pile-fill').forEach((el) => el.remove());

        const domeSize = 252;
        const border = 8;
        const collarPad = 28; // 襟・筐体に隠れる下端を床として扱わない
        const cx = domeSize / 2;
        const cy = domeSize / 2;
        const innerR = domeSize / 2 - border - 3;
        const floorY = domeSize - collarPad;
        const diameters = [46, 44, 48, 42, 44, 40, 42, 38, 40];
        type Ball = { x: number; y: number; r: number; rot: number };
        const clampInDome = (ball: Ball): void => {
            // 円形ドーム拘束
            const dx = ball.x - cx;
            const dy = ball.y - cy;
            const dist = Math.hypot(dx, dy) || 0.001;
            const maxDist = innerR - ball.r;
            if (dist > maxDist) {
                const scale = maxDist / dist;
                ball.x = cx + dx * scale;
                ball.y = cy + dy * scale;
            }
            // 見た目の床（襟の上）より下へは落とさない
            if (ball.y + ball.r > floorY) {
                ball.y = floorY - ball.r;
            }
        };
        // 底（襟の上）から積み上げ → 軽い重力で密着。上半球は空ける
        const balls: Ball[] = [];
        const sorted = diameters.slice().sort((a, b) => b - a);
        let row = 0;
        let col = 0;
        for (const d of sorted) {
            const r = d / 2;
            const rowY = floorY - r - 2 - (row * (r * 1.6));
            const colsInRow = Math.max(1, 4 - row);
            const rowWidth = (colsInRow - 1) * (r * 1.75);
            const x0 = cx - rowWidth / 2;
            balls.push({
                x: x0 + (col * (r * 1.75)),
                y: rowY,
                r,
                rot: ((balls.length * 17) % 40) - 20,
            });
            col += 1;
            if (col >= colsInRow) {
                col = 0;
                row += 1;
            }
        }

        for (let iter = 0; iter < 90; iter += 1) {
            for (const ball of balls) {
                ball.y += 2.6;
                clampInDome(ball);
            }
            for (let i = 0; i < balls.length; i += 1) {
                for (let j = i + 1; j < balls.length; j += 1) {
                    const a = balls[i];
                    const b = balls[j];
                    const dx = b.x - a.x;
                    const dy = b.y - a.y;
                    const dist = Math.hypot(dx, dy) || 0.001;
                    const minDist = a.r + b.r - 0.5;
                    if (dist >= minDist) {
                        continue;
                    }
                    const push = (minDist - dist) / 2;
                    const nx = dx / dist;
                    const ny = dy / dist;
                    a.x -= nx * push;
                    a.y -= ny * push;
                    b.x += nx * push;
                    b.y += ny * push;
                }
            }
            for (const ball of balls) {
                clampInDome(ball);
            }
        }

        balls
            .slice()
            .sort((a, b) => a.y - b.y)
            .forEach((ball, i) => {
                const el = document.createElement('span');
                el.className = 'pp-gacha__ball';
                el.style.width = `${ball.r * 2}px`;
                el.style.height = `${ball.r * 2}px`;
                el.style.left = `${ball.x - ball.r}px`;
                el.style.top = `${ball.y - ball.r}px`;
                el.style.zIndex = String(8 + Math.round(ball.y / 20));
                el.style.setProperty('--ball-color', CAPSULE_COLORS[i % CAPSULE_COLORS.length]);
                el.style.setProperty('--ball-rot', `${ball.rot}deg`);
                el.style.setProperty('--ball-scale', '1');
                el.style.setProperty('--ball-bright', String(0.9 + (ball.y / domeSize) * 0.12));
                el.style.setProperty('--ball-delay', `${(i % 5) * 0.04}s`);
                dome.appendChild(el);
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
            if (target.closest('#designRouletteBtn')) {
                void this._runRoulette(container);
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
        prizeEl.classList.remove('is-lose', 'is-revealed');
        const mystery = container.querySelector('#designScratchMystery') as HTMLElement | null;
        const imageEl = container.querySelector('#designScratchImage') as HTMLImageElement | null;
        const eyebrow = container.querySelector('#designScratchEyebrow') as HTMLElement | null;
        if (imageEl !== null) {
            imageEl.hidden = true;
            imageEl.removeAttribute('src');
            imageEl.alt = '';
        }
        if (isDemoDataMode()) {
            const prize = pickRandomPrize();
            prizeEl.dataset.prizeIndex = String(DEMO_COUPON_PRIZES.indexOf(prize));
            prizeEl.dataset.prizeName = prize.coupon_name;
            prizeEl.dataset.prizeDesc = prize.description;
            prizeEl.dataset.prizeImage = prize.image_url;
            // 銀面の下にあたり内容を先に置いて、こすると見えるようにする
            if (mystery !== null) {
                mystery.hidden = true;
            }
            if (eyebrow !== null) {
                eyebrow.hidden = false;
            }
            nameEl.textContent = prize.coupon_name;
            descEl.textContent = prize.description;
            if (imageEl !== null && prize.image_url !== '') {
                imageEl.src = prize.image_url;
                imageEl.alt = prize.coupon_name;
                imageEl.hidden = false;
            }
        } else {
            delete prizeEl.dataset.prizeIndex;
            delete prizeEl.dataset.prizeName;
            delete prizeEl.dataset.prizeDesc;
            delete prizeEl.dataset.prizeImage;
            if (mystery !== null) {
                mystery.hidden = false;
            }
            if (eyebrow !== null) {
                eyebrow.hidden = true;
            }
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
            const shine = container.querySelector('.pp-scratch__shine') as HTMLElement | null;
            if (shine !== null) {
                shine.hidden = true;
            }
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
            hint.textContent = '結果を確認しています…';
        }

        let prize: Prize | null = null;
        if (isDemoDataMode()) {
            const prizeIndex = Number(prizeEl.dataset.prizeIndex);
            prize = (Number.isInteger(prizeIndex) && prizeIndex >= 0 && prizeIndex < DEMO_COUPON_PRIZES.length)
                ? { ...DEMO_COUPON_PRIZES[prizeIndex], is_win: true }
                : { ...DEMO_COUPON_PRIZES[0], is_win: true };
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
            prize = result.is_win && result.coupon !== null
                ? {
                    coupon_name: result.coupon.name,
                    description: result.coupon.description || '',
                    image_url: result.coupon.image_url || undefined,
                    is_win: true,
                    entitlement_id: result.entitlement_id,
                }
                : {
                    coupon_name: 'ハズレ',
                    description: '残念！またチャレンジしてください',
                    is_win: false,
                    entitlement_id: result.entitlement_id,
                };
        }

        const isWin = prize?.is_win === true;
        if (hint !== null) {
            hint.textContent = isWin ? 'クーポンを獲得しました' : 'ハズレでした';
        }
        if (prize !== null) {
            if (isWin) {
                this._refreshWonList(container);
            }
            this._showPrizeModal(prize);
        }
        this._applyAvailability(container);
    }
    private _notifyCouponsUpdated(name: string, silent = false): void {
        const manager = (window.app as any)?.couponsManager;
        if (manager !== undefined && typeof manager.onCouponReceived === 'function') {
            manager.onCouponReceived({ message: `${name} を獲得しました`, silent });
        }
    }

    private async _notifyLotteryWinAndToast(prize: Prize): Promise<void> {
        if (prize.entitlement_id !== undefined && !isDemoDataMode()) {
            try {
                await this.apiClient.post(
                    AppConstants.API_ENDPOINTS.MEMBERS_LOTTERY_NOTIFY_WIN(prize.entitlement_id),
                    {},
                );
            } catch (error: any) {
                this._lotteryLog('notify_win_error', {
                    entitlementId: prize.entitlement_id,
                    message: error?.message || 'unknown',
                });
            }
        }
        this._notifyCouponsUpdated(prize.coupon_name, true);
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
        const prizeRoot = container.querySelector('#designScratchPrize') as HTMLElement | null;
        if (prizeRoot !== null) {
            prizeRoot.classList.remove('is-lose');
        }
        this._initScratch(container);
    }

    private _rouletteItems(): Array<Prize & { color: string; short: string }> {
        const palette = ['#E8C84A', '#3EC9A8', '#E8924A', '#8FA0B4', '#F0B14A', '#3AAFA3'];
        return [
            { ...DEMO_COUPON_PRIZES[0], short: 'ドリンク無料', is_win: true, color: palette[0] },
            { ...DEMO_COUPON_PRIZES[1], short: 'デザート割', is_win: true, color: palette[1] },
            {
                coupon_name: 'ハズレ',
                description: '残念！またチャレンジしてください',
                image_url: '',
                short: 'ハズレ',
                is_win: false,
                color: palette[3],
            },
            { ...DEMO_COUPON_PRIZES[2], short: '50pt', is_win: true, color: palette[2] },
            { ...DEMO_COUPON_PRIZES[0], short: 'ドリンク無料', is_win: true, color: palette[4] },
            {
                coupon_name: 'ハズレ',
                description: '残念！またチャレンジしてください',
                image_url: '',
                short: 'ハズレ',
                is_win: false,
                color: '#A8B2C0',
            },
            { ...DEMO_COUPON_PRIZES[1], short: 'デザート割', is_win: true, color: palette[5] },
            { ...DEMO_COUPON_PRIZES[2], short: '50pt', is_win: true, color: palette[0] },
        ];
    }

    private _paintRoulette(container: HTMLElement): void {
        const frame = container.querySelector('.pp-roulette__frame') as HTMLElement | null;
        const wheel = container.querySelector('#designRouletteWheel') as HTMLElement | null;
        const lights = container.querySelector('#designRouletteLights') as HTMLElement | null;
        if (wheel === null) {
            return;
        }
        const size = Math.min(280, Math.max(220, Math.floor(window.innerWidth * 0.78)));
        if (frame !== null) {
            frame.style.width = `${size}px`;
            frame.style.height = `${size}px`;
            frame.style.margin = '0 auto';
            frame.style.position = 'relative';
        }
        if (lights !== null && lights.childElementCount === 0) {
            lights.innerHTML = Array.from({ length: 18 }, (_, i) => (
                `<span class="pp-roulette__bulb" style="--bulb-i:${i}"></span>`
            )).join('');
        }
        wheel.style.position = 'absolute';
        wheel.style.inset = '14px';
        wheel.style.width = 'auto';
        wheel.style.height = 'auto';
        wheel.style.borderRadius = '50%';
        wheel.style.overflow = 'hidden';
        const items = this._rouletteItems();
        const slice = 360 / items.length;
        const stops = items.map((item, index) => {
            const start = index * slice;
            const end = (index + 1) * slice;
            return `${item.color} ${start}deg ${end}deg`;
        }).join(', ');
        // from 0deg（真上が起点）で色・ラベル・着地判定を揃える。from -90deg だとハズレ表示と結果がズレる
        wheel.style.background = `
            radial-gradient(circle at 50% 50%, transparent 28%, rgba(0,0,0,0.18) 29%, transparent 31%),
            conic-gradient(from 0deg, ${stops})
        `;
        wheel.innerHTML = items.map((item, index) => {
            const angle = (index * slice) + (slice / 2);
            return `
                <span class="pp-roulette__label" style="--roulette-angle:${angle}deg">
                    <span class="pp-roulette__label-text">${DomHelper.escapeHtml(item.short)}</span>
                </span>
            `;
        }).join('');
        wheel.style.transform = 'rotate(0deg)';
        wheel.style.transition = '';
        wheel.dataset.spinDeg = '0';
        wheel.dataset.segmentCount = String(items.length);
    }

    private async _runRoulette(container: HTMLElement): Promise<void> {
        if (!isDemoDataMode()) {
            return;
        }
        const stage = container.querySelector('#designRouletteStage') as HTMLElement | null;
        const wheel = container.querySelector('#designRouletteWheel') as HTMLElement | null;
        const btn = container.querySelector('#designRouletteBtn') as HTMLButtonElement | null;
        if (stage === null || wheel === null || btn === null || btn.disabled) {
            return;
        }
        if (stage.dataset.state === 'spinning') {
            return;
        }
        const items = this._rouletteItems();
        const pickIndex = Math.floor(Math.random() * items.length);
        const slice = 360 / items.length;
        const targetCenter = (pickIndex * slice) + (slice / 2);
        // 前回の角度から積み上げる（毎回同じ帯域だと2回目以降ほぼ回らない）
        const prevDeg = Number(wheel.dataset.spinDeg || '0');
        const landing = (360 - targetCenter) % 360;
        const nextBase = prevDeg + (360 * 7);
        const nextMod = ((nextBase % 360) + 360) % 360;
        const finalDeg = nextBase + ((landing - nextMod + 360) % 360);
        wheel.dataset.spinDeg = String(finalDeg);
        btn.disabled = true;
        stage.dataset.state = 'spinning';
        wheel.style.transition = 'transform 5.2s cubic-bezier(0.08, 0.82, 0.08, 1)';
        wheel.style.transform = `rotate(${finalDeg}deg)`;
        await new Promise<void>((resolve) => {
            window.setTimeout(resolve, 5300);
        });
        stage.dataset.state = 'idle';
        btn.disabled = false;
        // 見た目の着地位置から再計算（ラベルと結果を一致させる）
        const landedAngle = ((360 - (finalDeg % 360)) % 360);
        const landedIndex = Math.min(items.length - 1, Math.floor(landedAngle / slice));
        const prize = items[landedIndex] ?? items[pickIndex];
        const isWin = prize.is_win === true;
        if (isWin) {
            saveDemoWonCoupon(prize, 'roulette');
            this._refreshWonList(container);
        }
        this._showPrizeModal({
            coupon_name: prize.coupon_name,
            description: prize.description,
            is_win: isWin,
        });
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
            prize = { ...pickRandomPrize(), is_win: true };
        } else {
            const pending = this._pending('gacha');
            if (pending !== null) {
                const result = await this._playEntitlement(pending.id);
                if (result !== null) {
                    prize = result.is_win && result.coupon !== null
                        ? {
                            coupon_name: result.coupon.name,
                            description: result.coupon.description || '',
                            image_url: result.coupon.image_url || undefined,
                            is_win: true,
                            entitlement_id: result.entitlement_id,
                        }
                        : {
                            coupon_name: 'ハズレ',
                            description: '残念！またチャレンジしてください',
                            is_win: false,
                            entitlement_id: result.entitlement_id,
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
                const isWin = prize.is_win === true;
                if (isDemoDataMode() && isWin) {
                    saveDemoWonCoupon(prize as (typeof DEMO_COUPON_PRIZES)[number], 'gacha');
                }
                // 結果モーダル表示。通知・トーストは自動クローズ時に送る（当たる前にバレないように）
                this._showPrizeModal(prize);
                if (isWin) {
                    this._refreshWonList(container);
                }
            }
            this._applyAvailability(container);
            if (isDemoDataMode()) {
                btn.disabled = false;
                btn.textContent = 'もう一度回す';
            } else if (this._pending('gacha') === null) {
                btn.disabled = true;
                btn.textContent = '権利がありません';
            } else {
                btn.disabled = false;
                btn.textContent = 'レバーを回す';
            }
        }, 1850);
    }
    private async _playEntitlement(id: number): Promise<PlayResult | null> {
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
                    data?: {
                        entitlement_id?: number;
                        is_win?: boolean;
                        coupon?: { id: number; name: string; description: string | null; image_url: string | null } | null;
                    };
                    entitlement_id?: number;
                    is_win?: boolean;
                    coupon?: { id: number; name: string; description: string | null; image_url: string | null } | null;
                    message?: string;
                };
                message?: string;
            } : null;
            const nested = outer?.data;
            const payload = nested?.data ?? nested;
            const coupon = payload?.coupon ?? null;
            const isWin = payload?.is_win === true && coupon !== null;
            if (outer?.success !== true || payload === undefined) {
                const message = nested?.message || outer?.message || 'プレイに失敗しました';
                this._lotteryLog('play_failed', { id, message });
                ToastManager.error(message);
                return null;
            }
            if (isWin && (coupon === null || coupon === undefined)) {
                const message = nested?.message || outer?.message || 'プレイに失敗しました';
                this._lotteryLog('play_failed', { id, message: 'missing_coupon' });
                ToastManager.error(message);
                return null;
            }
            await this._fetchState();
            this._lotteryLog('play_ok', { id, isWin, couponName: coupon?.name ?? null });
            return {
                is_win: isWin,
                entitlement_id: payload?.entitlement_id ?? id,
                coupon: isWin ? coupon : null,
            };
        } catch (error: any) {
            this._lotteryLog('play_error', { id, message: error?.message || 'unknown' });
            ToastManager.error(error?.message || 'プレイに失敗しました');
            return null;
        } finally {
            this._playInFlight = false;
        }
    }

    private _navigateAfterWin(): void {
        this.closeGachaPopup();
        const app = window.app as { switchTab?: (tab: string) => void } | undefined;
        if (app !== undefined && typeof app.switchTab === 'function') {
            app.switchTab(AppConstants.TABS.COUPONS);
        }
    }

    private _showPrizeModal(prize: Prize): void {
        document.getElementById('designPrizeModal')?.remove();
        const isWin = prize.is_win === true;
        const overlay = document.createElement('div');
        overlay.id = 'designPrizeModal';
        overlay.className = isWin ? 'pp-prize-modal' : 'pp-prize-modal pp-prize-modal--lose';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', isWin ? '当選結果' : 'ハズレ');
        // 中央のリボン＋クーポン名（ガチャと同じ当選演出）
        overlay.innerHTML = `
            <div class="pp-prize-modal__backdrop" data-prize-close></div>
            <div class="pp-prize-modal__panel" data-prize-close>
                <span class="pp-prize-modal__ribbon${isWin ? '' : ' pp-prize-modal__ribbon--lose'}">${isWin ? 'あたり' : 'ハズレ'}</span>
                <p class="pp-prize-modal__name${isWin ? '' : ' pp-prize-modal__name--lose'}">${DomHelper.escapeHtml(isWin ? prize.coupon_name : 'ハズレ')}</p>
            </div>
        `;
        let finished = false;
        const finish = () => {
            if (finished) {
                return;
            }
            finished = true;
            overlay.classList.add('is-out');
            window.setTimeout(() => overlay.remove(), 220);
            if (isWin) {
                void this._notifyLotteryWinAndToast(prize).finally(() => {
                    this._navigateAfterWin();
                });
            } else {
                this.closeGachaPopup();
            }
        };
        overlay.addEventListener('click', (event) => {
            const t = event.target as HTMLElement | null;
            if (t !== null && t.closest('[data-prize-close]')) {
                finish();
            }
        });
        document.body.appendChild(overlay);
        void overlay.offsetWidth;
        overlay.classList.add('is-in');
        window.setTimeout(finish, 2800);
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
        list.innerHTML = won.map((c: DemoCoupon) => {
            const badge = c.source === 'scratch'
                ? 'スクラッチ'
                : (c.source === 'roulette' ? 'ルーレット' : 'ガチャ');
            const image = c.image_url
                ? `<img class="design-lab__won-image" src="${DomHelper.escapeHtml(c.image_url)}" alt="">`
                : '';
            return `
            <li class="design-lab__won-item">
                ${image}
                <div class="design-lab__won-copy">
                    <span class="design-lab__won-badge">${badge}</span>
                    <span class="design-lab__won-name">${DomHelper.escapeHtml(c.coupon_name)}</span>
                </div>
            </li>
        `;
        }).join('');
    }

}
