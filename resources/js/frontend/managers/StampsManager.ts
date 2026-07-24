import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { mountDetailLayoutChrome } from '../utils/detailLayoutMount';
import { isDemoDataMode } from '../demo/demoMode';
import { ensureLiffIdentityForLoad } from '../demo/ensureIdentity';
import { buildBuiltinSampleStampCards } from '../demo/sampleData';

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

export class StampsManager {
    apiClient: ApiClient;
    private cache: StampCard[] = [];
    private lastDefaultImage: string | null = null;

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

    async load(): Promise<void> {
        const section = this.getSection();
        if (!section) {
            return;
        }

        if (isDemoDataMode()) {
            this.renderSampleStampCards();
            return;
        }

        const canCallApi = await ensureLiffIdentityForLoad();
        if (!canCallApi) {
            this.renderSampleStampCards();
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
                this.renderSampleStampCards();
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
        if (this.cache.length === 0) {
            await this.load();
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
