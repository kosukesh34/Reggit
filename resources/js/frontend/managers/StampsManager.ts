import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { mountDetailLayoutChrome } from '../utils/detailLayoutMount';
import { isDemoDataMode, isFixedUiShell } from '../demo/demoMode';
import { ensureLiffIdentityForLoad } from '../demo/ensureIdentity';
import { buildBuiltinSampleStampCards } from '../demo/sampleData';
import { isReactOwnedMount } from '../utils/reactOwnedMount';

type StampRewardMilestone = {
    stamp_count?: number | string;
    description?: string;
};

type StampCard = {
    id?: number | string;
    current_stamps?: number;
    required_stamps?: number;
    completed_at?: string | null;
    layout_style?: string;
    image_url?: string | null;
    stamp_images?: Record<string, string> | null;
    member_stamp_valid_until?: string | null;
    valid_until?: string | null;
    reward_milestones?: StampRewardMilestone[];
    name?: string;
    reward_description?: string | null;
    is_sample_preview?: boolean;
};

type StampsResponse = {
    stamp_cards?: StampCard[];
    default_image_url?: string | null;
    message?: string | null;
};

type StampDesignSettings = {
    screenDesign: string;
    slotStyle: string;
    filledStyle: string;
    accentColor: string;
};

const STAMP_SCREEN_DESIGNS = ['grid_card', 'wallet', 'circle_board', 'minimal'];
const STAMP_SLOT_STYLES = ['circle', 'rounded', 'square'];
const STAMP_FILLED_STYLES = ['image', 'solid', 'icon'];

export class StampsManager {
    apiClient: ApiClient;
    private cache: StampCard[] = [];
    private lastDefaultImage: string | null = null;

    static getStampDesignSettings(): StampDesignSettings {
        const block = document.querySelector('.builder-data-block[data-source="stamps"]') as HTMLElement | null;
        const readBlock = (attr: string): string => (block ? String(block.getAttribute(attr) || '') : '');
        const readGlobal = (key: string, fallback: string): string =>
            DomHelper.getLiffGlobalSetting(key, fallback);

        const screenDesign = readBlock('data-stamp-design') || readGlobal('stamp_screen_design', 'grid_card');
        const slotStyle = readBlock('data-stamp-slot-style') || readGlobal('stamp_slot_style', 'circle');
        const filledStyle = readBlock('data-stamp-filled-style') || readGlobal('stamp_filled_style', 'image');
        const accentColor = readBlock('data-stamp-accent-color') || readGlobal('stamp_accent_color', '');

        return {
            screenDesign: STAMP_SCREEN_DESIGNS.includes(screenDesign) ? screenDesign : 'grid_card',
            slotStyle: STAMP_SLOT_STYLES.includes(slotStyle) ? slotStyle : 'circle',
            filledStyle: STAMP_FILLED_STYLES.includes(filledStyle) ? filledStyle : 'image',
            accentColor,
        };
    }

    constructor(apiClient: ApiClient) {
        this.apiClient = apiClient;
    }

    getSection(): Element | null {
        return DomHelper.getTabMount(
            AppConstants.TAB_MOUNTS.STAMPS,
            AppConstants.ELEMENT_IDS.STAMPS_SECTION,
            { silent: true },
        );
    }

    getGuestTemplate(): string {
        return `
            <div class="member-card-container">
                <div class="member-card member-card--line-only">
                    <div class="member-card-header">
                        <h2 class="member-card-title"><i class="fas fa-stamp"></i> スタンプカード</h2>
                    </div>
                    <div class="member-card-line-only-body">
                        <p class="member-card-line-only-message">この端末はゲスト状態です。</p>
                        <p class="member-card-line-only-sub">会員登録するとスタンプ履歴を引き継げます。</p>
                        <button type="button" class="btn btn-primary mt-2" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                            <i class="fas fa-user-plus"></i> 会員登録へ
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getMemberRequiredTemplate(message = '会員登録が必要です'): string {
        return `
            <div class="stamps-empty">
                <i class="fas fa-id-card"></i>
                <p>${DomHelper.escapeHtml(message)}</p>
                <p class="stamps-empty-sub">会員登録後にスタンプカードを利用できます</p>
                <button type="button" class="btn btn-primary mt-2" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
                    <i class="fas fa-user-plus"></i> 会員登録する
                </button>
            </div>
        `;
    }

    getDesignerSampleStampCards(): StampCard[] {
        if (typeof window !== 'undefined'
            && window.app
            && typeof (window.app as { getDesignerSampleStampCards?: () => StampCard[] }).getDesignerSampleStampCards === 'function') {
            return (window.app as { getDesignerSampleStampCards: () => StampCard[] }).getDesignerSampleStampCards();
        }
        return buildBuiltinSampleStampCards();
    }

    renderSampleStampCards(): void {
        const cards = this.getDesignerSampleStampCards().map((card) => ({
            ...card,
            is_sample_preview: true,
        }));
        this.cache = cards;
        this.render(cards, null, true);
    }

    getErrorTemplate(error?: { message?: string } | Error | null): string {
        const message =
            error && 'message' in error && error.message
                ? error.message
                : 'スタンプの読み込みに失敗しました';
        return `
            <div class="stamps-error">
                <i class="fas fa-exclamation-circle"></i>
                <p>${DomHelper.escapeHtml(message)}</p>
                <button type="button" class="btn btn-secondary" onclick="window.app.stampsManager.load()">
                    <i class="fas fa-redo"></i> 再読み込み
                </button>
            </div>
        `;
    }

    primeCache(cards: StampCard[], defaultImage?: string | null): void {
        this.cache = cards.map((card, index) => ({
            ...card,
            id: card.id ?? index,
        }));
        this.lastDefaultImage = defaultImage ?? null;
    }

    private async ensureCacheLoaded(): Promise<void> {
        if (this.cache.length > 0) {
            return;
        }
        if (isDemoDataMode()) {
            this.cache = this.getDesignerSampleStampCards().map((card) => ({
                ...card,
                is_sample_preview: true,
            }));
            return;
        }
        const canCallApi = await ensureLiffIdentityForLoad();
        if (!canCallApi) {
            return;
        }
        const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.STAMPS);
        if (result.success !== true) {
            return;
        }
        const data = result.data as StampsResponse;
        const cards = data && data.stamp_cards ? data.stamp_cards : [];
        this.cache = cards.map((card, index) => ({
            ...card,
            id: card.id ?? index,
        }));
        this.lastDefaultImage = data && data.default_image_url ? data.default_image_url : null;
    }

    async load(): Promise<void> {
        const section = this.getSection();
        if (!section) {
            return;
        }
        if (isReactOwnedMount(section)) {
            return;
        }

        if (isDemoDataMode()) {
            this.renderSampleStampCards();
            return;
        }

        const canCallApi = await ensureLiffIdentityForLoad();
        if (!canCallApi) {
            if (isDemoDataMode()) {
                this.renderSampleStampCards();
                return;
            }
            section.innerHTML = this.getGuestTemplate();
            return;
        }

        section.innerHTML =
            '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>';
        try {
            const result = await this.apiClient.get(AppConstants.API_ENDPOINTS.STAMPS);
            if (result.success !== true) {
                section.innerHTML = this.getErrorTemplate({ message: result.error });
                return;
            }

            const data = result.data as StampsResponse;
            const cards = data && data.stamp_cards ? data.stamp_cards : [];
            const defaultImage = data && data.default_image_url ? data.default_image_url : null;
            const message = data && data.message ? data.message : null;
            if ((!cards || cards.length === 0) && message) {
                if (isDemoDataMode()) {
                    this.renderSampleStampCards();
                    return;
                }
                section.innerHTML = this.getMemberRequiredTemplate(message);
                return;
            }

            this.cache = cards.map((card, index) => ({
                ...card,
                id: card.id ?? index,
            }));
            this.lastDefaultImage = defaultImage;
            this.render(this.cache, defaultImage);
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('Failed to load stamps:', error);
            section.innerHTML = this.getErrorTemplate(error as Error);
        }
    }

    async openDetail(id: number | string, returnHash?: string): Promise<void> {
        if (!isFixedUiShell()) {
            await this._openClassicStampDetail(id, returnHash);
            return;
        }
        await this._openFixedUiStampDetail(id, returnHash);
    }

    async _openClassicStampDetail(id: number | string, returnHash?: string): Promise<void> {
        if (this.cache.length === 0) {
            await this.ensureCacheLoaded();
        }
        const card = this.cache.find((c) => String(c.id) === String(id));
        if (!card) {
            return;
        }
        const router = typeof window !== 'undefined' && window.app && window.app.pageRouter
            ? window.app.pageRouter
            : null;
        if (router === null) {
            return;
        }
        const section = router.openPage({
            key: 'stamp_detail',
            hash: 'stamp/' + String(id),
            returnHash: router.resolveReturnHash('stamps', returnHash),
            title: card.name || 'スタンプ',
            bodyClass: 'liff-page-stamp-detail',
        });
        if (section === null) {
            return;
        }
        const current = card.current_stamps || 0;
        const required = card.required_stamps || 10;
        const layoutStyle = card.layout_style || 'grid';
        const imageUrl = card.image_url || this.lastDefaultImage;
        const validUntilDate = this.formatValidUntil(
            card.member_stamp_valid_until || card.valid_until,
        );
        const design = StampsManager.getStampDesignSettings();
        const completed = !!card.completed_at;
        const bodyHtml = `
            <div class="liff-entity-detail stamp-detail-page stamp-screen--${DomHelper.escapeHtml(design.screenDesign)}">
                ${imageUrl ? `<div class="liff-entity-detail__image"><img src="${DomHelper.escapeHtml(imageUrl)}" alt=""></div>` : ''}
                <h1 class="liff-entity-detail__title">${DomHelper.escapeHtml(card.name || 'スタンプカード')}</h1>
                <p class="liff-entity-detail__meta">${current} / ${required} スタンプ${validUntilDate ? ` · 有効期限: ${DomHelper.escapeHtml(validUntilDate)}` : ''}</p>
                ${this.renderProgressSummary(current, required, completed)}
                ${this.renderSlots(current, required, layoutStyle, card.stamp_images || null, design)}
                ${this.renderRewardMilestones(Array.isArray(card.reward_milestones) ? card.reward_milestones : [], current)}
                ${card.reward_description ? `<div class="liff-entity-detail__body">${DomHelper.escapeHtml(card.reward_description)}</div>` : ''}
            </div>
        `;
        mountDetailLayoutChrome({
            section,
            layoutKey: 'stamp_detail',
            dataSource: 'stamp_detail',
            bodyHtml,
            slotClassName: 'liff-detail-slot stamp-detail-slot',
        });
    }

    async _openFixedUiStampDetail(id: number | string, returnHash?: string): Promise<void> {
        if (this.cache.length === 0) {
            await this.ensureCacheLoaded();
        }
        const card = this.cache.find((c) => String(c.id) === String(id));
        if (!card) {
            return;
        }
        const router = typeof window !== 'undefined' && window.app && window.app.pageRouter
            ? window.app.pageRouter
            : null;
        if (router === null) {
            return;
        }
        const section = router.openPage({
            key: 'stamp_detail',
            hash: 'stamp/' + String(id),
            returnHash: router.resolveReturnHash('stamps', returnHash),
            title: card.name || 'スタンプ',
            bodyClass: 'liff-page-stamp-detail',
        });
        if (section === null) {
            return;
        }
        const current = card.current_stamps || 0;
        const required = card.required_stamps || 10;
        const remaining = Math.max(0, required - current);
        const progress = Math.min(100, required > 0 ? (current / required) * 100 : 0);
        const imageUrl = card.image_url || this.lastDefaultImage || '/images/liff/stamp-card-bg.jpg';
        const validUntilDate = this.formatValidUntil(
            card.member_stamp_valid_until || card.valid_until,
        );
        const completed = !!card.completed_at;
        const bodyHtml = `
            <article class="ios-detail ios-detail--stamp">
                <div class="ios-detail__hero" style="background-image:url('${DomHelper.escapeHtml(imageUrl)}');background-size:cover;background-position:center">
                    <div class="ios-detail__hero-shade"></div>
                    <div class="ios-detail__chips">
                        <span class="ios-detail__chip ios-detail__chip--status">スタンプ</span>
                        ${completed ? '<span class="ios-detail__chip">達成</span>' : `<span class="ios-detail__chip">あと ${remaining}</span>`}
                    </div>
                </div>
                <div class="ios-detail__panel">
                    <h1 class="ios-detail__title">${DomHelper.escapeHtml(card.name || 'スタンプカード')}</h1>
                    ${validUntilDate ? `<p class="ios-detail__meta"><i class="far fa-calendar-alt" aria-hidden="true"></i>有効期限 ${DomHelper.escapeHtml(validUntilDate)}</p>` : ''}
                    <div class="ios-stamp-card__meter" style="margin-bottom:14px">
                        <div class="ios-stamp-card__count"><strong>${current}</strong><span>/${required}</span></div>
                        <div class="ios-stamp-card__bar"><i style="width:${progress}%"></i></div>
                    </div>
                    ${this.renderIosStampDots(current, required)}
                    ${card.reward_description ? `<p class="ios-detail__body" style="margin-top:16px;margin-bottom:0">${DomHelper.escapeHtml(card.reward_description)}</p>` : ''}
                </div>
            </article>
        `;
        mountDetailLayoutChrome({
            section,
            layoutKey: 'stamp_detail',
            dataSource: 'stamp_detail',
            bodyHtml,
            slotClassName: 'liff-detail-slot stamp-detail-slot',
        });
    }

    renderIosStampDots(current: number, required: number): string {
        const slots = [];
        for (let i = 0; i < required; i++) {
            const filled = i < current;
            slots.push(`<span class="ios-stamp-dot${filled ? ' is-filled' : ''}${!filled && i === current ? ' is-next' : ''}" aria-hidden="true">${filled ? '<i class="fas fa-check"></i>' : (i + 1)}</span>`);
        }
        return `<div class="ios-stamp-dots" role="img" aria-label="${current}/${required} スタンプ">${slots.join('')}</div>`;
    }

    formatValidUntil(dateLike?: string | null): string | null {
        if (!dateLike) {
            return null;
        }
        const d = new Date(dateLike);
        if (Number.isNaN(d.getTime())) {
            return null;
        }
        return d.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        });
    }

    render(cards: StampCard[], defaultImage?: string | null, isSample = false): void {
        if (isFixedUiShell()) {
            this._renderFixedUiStamps(cards, defaultImage, isSample);
            return;
        }
        this._renderClassicStamps(cards, defaultImage);
    }

    resolveStampImage(stampImages: Record<string, string> | null, index: number): string | null {
        if (!stampImages || typeof stampImages !== 'object') {
            return null;
        }
        const key = String(index);
        const url = stampImages[key] || stampImages[String(index + 1)] || null;
        return typeof url === 'string' && url !== '' ? url : null;
    }

    renderSlotContent(
        index: number,
        filled: boolean,
        stampImages: Record<string, string> | null,
        design: StampDesignSettings,
    ): string {
        if (!filled) {
            return `<span class="stamp-slot-number">${index + 1}</span>`;
        }

        const imageUrl = design.filledStyle === 'image' ? this.resolveStampImage(stampImages, index) : null;
        if (imageUrl) {
            return `<img src="${DomHelper.escapeHtml(imageUrl)}" alt="" class="stamp-slot-image" loading="lazy">`;
        }
        if (design.filledStyle === 'icon') {
            return '<i class="fas fa-stamp" aria-hidden="true"></i>';
        }
        return '<i class="fas fa-check" aria-hidden="true"></i>';
    }

    renderSlot(
        index: number,
        filled: boolean,
        isNext: boolean,
        stampImages: Record<string, string> | null,
        design: StampDesignSettings,
    ): string {
        const number = index + 1;
        const classes = ['stamp-slot', `stamp-slot--shape-${design.slotStyle}`];
        if (filled) {
            classes.push('stamp-slot--filled');
        } else {
            classes.push('stamp-slot--empty');
        }
        if (isNext) {
            classes.push('stamp-slot--next');
        }
        const label = filled
            ? `${number}番目（押印済み）`
            : isNext
                ? `${number}番目（次はここ）`
                : `${number}番目`;

        return `<div class="${classes.join(' ')}" aria-label="${DomHelper.escapeHtml(label)}">${this.renderSlotContent(index, filled, stampImages, design)}</div>`;
    }

    renderSlots(
        current: number,
        required: number,
        layoutStyle?: string,
        stampImages?: Record<string, string> | null,
        design?: StampDesignSettings,
    ): string {
        const settings = design || StampsManager.getStampDesignSettings();
        const slots = [];
        for (let i = 0; i < required; i++) {
            const filled = i < current;
            const isNext = !filled && i === current && current < required;
            slots.push(this.renderSlot(i, filled, isNext, stampImages || null, settings));
        }
        const layout = layoutStyle || 'grid';
        const accentStyle = settings.accentColor !== ''
            ? ` style="--stamp-accent-color:${DomHelper.escapeHtml(settings.accentColor)}"`
            : '';
        return `<div class="stamp-slots stamp-slots--${layout} stamp-slots--filled-${settings.filledStyle}" role="img" aria-label="${current}/${required} スタンプ"${accentStyle}>${slots.join('')}</div>`;
    }

    renderProgressSummary(current: number, required: number, completed: boolean): string {
        const progress = Math.min(100, required > 0 ? (current / required) * 100 : 0);
        return `
            <div class="stamp-progress-summary${completed ? ' stamp-progress-summary--completed' : ''}">
                <div class="stamp-progress-summary__ring" style="--stamp-progress:${progress}">
                    <span class="stamp-progress-summary__value">${current}<small>/${required}</small></span>
                </div>
                <div class="stamp-progress-summary__copy">
                    <strong>${completed ? '達成しました' : 'あと ' + Math.max(0, required - current) + ' 個'}</strong>
                    <span>スタンプを集めて特典をゲット</span>
                </div>
            </div>
        `;
    }

    renderRewardMilestones(rewardMilestones: StampRewardMilestone[], current: number): string {
        if (!Array.isArray(rewardMilestones) || rewardMilestones.length === 0) {
            return '';
        }
        const items = rewardMilestones
            .filter(
                (m) =>
                    m
                    && Number.parseInt(String(m.stamp_count), 10) > 0
                    && String(m.description || '').trim() !== '',
            )
            .sort((a, b) => Number(a.stamp_count) - Number(b.stamp_count))
            .map((m) => {
                const stampCount = Number.parseInt(String(m.stamp_count), 10);
                const achieved = current >= stampCount;
                return `
                    <li class="stamp-card-milestone ${achieved ? 'stamp-card-milestone--achieved' : ''}">
                        <span class="stamp-card-milestone-count">${stampCount}個</span>
                        <span class="stamp-card-milestone-text">${DomHelper.escapeHtml(String(m.description))}</span>
                    </li>
                `;
            })
            .join('');
        if (!items) {
            return '';
        }
        return `<ul class="stamp-card-milestones">${items}</ul>`;
    }

    _renderClassicStamps(cards: StampCard[], defaultImage?: string | null): void {
        const section = this.getSection();
        if (!section) {
            return;
        }
        if (!cards || cards.length === 0) {
            section.innerHTML = `
                <div class="stamps-empty">
                    <i class="fas fa-stamp"></i>
                    <p>スタンプカードはありません</p>
                    <p class="stamps-empty-sub">お店でスタンプを集めましょう</p>
                </div>
            `;
            return;
        }

        const design = StampsManager.getStampDesignSettings();
        const accentStyle = design.accentColor !== ''
            ? ` style="--stamp-accent-color:${DomHelper.escapeHtml(design.accentColor)}"`
            : '';

        const html = cards
            .map((card) => {
                const current = card.current_stamps || 0;
                const required = card.required_stamps || 10;
                const progress = Math.min(100, (current / required) * 100);
                const completed = !!card.completed_at;
                const layoutStyle = card.layout_style || 'grid';
                const imageUrl = card.image_url || defaultImage;
                const validUntilDate = this.formatValidUntil(
                    card.member_stamp_valid_until || card.valid_until,
                );
                const rewardMilestones = Array.isArray(card.reward_milestones)
                    ? card.reward_milestones
                    : [];
                return `
                    <button type="button" class="stamp-card-item stamp-card-item--layout-${layoutStyle} stamp-card-item--screen-${design.screenDesign} ${completed ? 'stamp-card-item--completed' : ''}" data-stamp-id="${DomHelper.escapeHtml(String(card.id ?? ''))}"${accentStyle}>
                        <div class="stamp-card-item__hero">
                            ${imageUrl
                                ? `<div class="stamp-card-image"><img src="${DomHelper.escapeHtml(imageUrl)}" alt="スタンプカード" loading="lazy" onerror="this.closest('.stamp-card-image').style.display='none'"></div>`
                                : '<div class="stamp-card-image stamp-card-image--badge"><span class="stamp-card-image__mark"><i class="fas fa-stamp" aria-hidden="true"></i></span></div>'}
                            <div class="stamp-card-item__headline">
                                <h3 class="stamp-card-name">${DomHelper.escapeHtml(card.name || 'スタンプカード')}</h3>
                                ${completed ? '<span class="stamp-card-badge"><i class="fas fa-check-circle"></i> 達成</span>' : ''}
                            </div>
                        </div>
                        ${this.renderProgressSummary(current, required, completed)}
                        ${this.renderSlots(current, required, layoutStyle, card.stamp_images || null, design)}
                        <div class="stamp-card-meta">
                            <p class="stamp-card-count">${current} / ${required} スタンプ</p>
                            ${validUntilDate ? `<p class="stamp-card-valid">有効期限: ${DomHelper.escapeHtml(validUntilDate)}</p>` : ''}
                        </div>
                        ${layoutStyle !== 'minimal' ? `<div class="stamp-card-progress"><div class="stamp-card-progress-bar" style="width: ${progress}%"></div></div>` : ''}
                        ${this.renderRewardMilestones(rewardMilestones, current)}
                        ${card.reward_description ? `<p class="stamp-card-reward">${DomHelper.escapeHtml(card.reward_description)}</p>` : ''}
                    </button>
                `;
            })
            .join('');

        section.innerHTML = `
            <div class="stamps-header">
                <h2 class="section-title"><i class="fas fa-stamp"></i> スタンプカード</h2>
            </div>
            <div class="stamps-list stamp-screen--${DomHelper.escapeHtml(design.screenDesign)}"${accentStyle}>${html}</div>
        `;
        section.querySelectorAll('[data-stamp-id]').forEach((el) => {
            el.addEventListener('click', () => {
                const id = (el as HTMLElement).dataset.stampId;
                if (id === undefined || id === '') {
                    return;
                }
                void this.openDetail(id);
            });
        });
    }

    _renderFixedUiStamps(cards: StampCard[], defaultImage?: string | null, isSample = false): void {
        const section = this.getSection();
        if (!section) {
            return;
        }
        if (!cards || cards.length === 0) {
            section.innerHTML = `
                <div class="ios-empty">
                    <div class="ios-empty__icon"><i class="fas fa-stamp"></i></div>
                    <h3 class="ios-empty__title">スタンプカードはありません</h3>
                    <p class="ios-empty__text">お店でスタンプを集めましょう</p>
                </div>
            `;
            return;
        }

        const html = cards
            .map((card) => {
                const current = card.current_stamps || 0;
                const required = card.required_stamps || 10;
                const remaining = Math.max(0, required - current);
                const progress = Math.min(100, required > 0 ? (current / required) * 100 : 0);
                const completed = !!card.completed_at;
                const imageUrl = card.image_url || defaultImage || '/images/liff/stamp-card-bg.jpg';
                const validUntilDate = this.formatValidUntil(
                    card.member_stamp_valid_until || card.valid_until,
                );
                const reward = card.reward_description
                    ? DomHelper.escapeHtml(card.reward_description)
                    : '特典をゲット';
                return `
                    <button type="button" class="ios-stamp-card${completed ? ' is-completed' : ''}" data-stamp-id="${DomHelper.escapeHtml(String(card.id ?? ''))}">
                        <div class="ios-stamp-card__hero" style="background-image:url('${DomHelper.escapeHtml(imageUrl)}')">
                            <div class="ios-stamp-card__hero-shade"></div>
                            <div class="ios-stamp-card__hero-top">
                                <span class="ios-stamp-card__eyebrow">スタンプカード</span>
                                ${completed ? '<span class="ios-stamp-card__done">達成</span>' : `<span class="ios-stamp-card__remain">あと ${remaining}</span>`}
                            </div>
                            <h3 class="ios-stamp-card__title">${DomHelper.escapeHtml(card.name || 'スタンプカード')}</h3>
                            <p class="ios-stamp-card__reward">${reward}</p>
                        </div>
                        <div class="ios-stamp-card__body">
                            <div class="ios-stamp-card__meter">
                                <div class="ios-stamp-card__count"><strong>${current}</strong><span>/${required}</span></div>
                                <div class="ios-stamp-card__bar"><i style="width:${progress}%"></i></div>
                                ${validUntilDate ? `<p class="ios-stamp-card__expiry">有効期限 ${DomHelper.escapeHtml(validUntilDate)}</p>` : ''}
                            </div>
                            ${this.renderIosStampDots(current, required)}
                        </div>
                    </button>
                `;
            })
            .join('');

        section.innerHTML = `
            <div class="ios-panel-head">
                <h2 class="ios-panel-head__title">スタンプ</h2>
                <p class="ios-panel-head__sub">来店するたび貯まる</p>
            </div>
            <div class="ios-stamp-stack">${html}</div>
        `;
        section.querySelectorAll('[data-stamp-id]').forEach((el) => {
            el.addEventListener('click', () => {
                const id = (el as HTMLElement).dataset.stampId;
                if (id === undefined || id === '') {
                    return;
                }
                void this.openDetail(id);
            });
        });
    }
}
