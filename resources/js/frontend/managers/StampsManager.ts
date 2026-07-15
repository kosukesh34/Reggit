import type { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';
import { isMembershipRequired } from '../utils/membershipPolicy';
import { mountDetailLayoutChrome } from '../utils/detailLayoutMount';

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
    member_stamp_valid_until?: string | null;
    valid_until?: string | null;
    reward_milestones?: StampRewardMilestone[];
    name?: string;
    reward_description?: string | null;
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
                        <button type="button" class="btn btn-secondary mt-2" onclick="app.switchTab('${AppConstants.TABS.MEMBER}')">
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
                section.innerHTML = isMembershipRequired()
                    ? this.getMemberRequiredTemplate(message)
                    : this.getGuestTemplate();
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
        const layoutStyle = card.layout_style || 'grid';
        const imageUrl = card.image_url || this.lastDefaultImage;
        const validUntilDate = this.formatValidUntil(
            card.member_stamp_valid_until || card.valid_until,
        );
        const bodyHtml = `
            <div class="liff-entity-detail stamp-detail-page">
                ${imageUrl ? `<div class="liff-entity-detail__image"><img src="${DomHelper.escapeHtml(imageUrl)}" alt=""></div>` : ''}
                <h1 class="liff-entity-detail__title">${DomHelper.escapeHtml(card.name || 'スタンプカード')}</h1>
                <p class="liff-entity-detail__meta">${current} / ${required} スタンプ${validUntilDate ? ` · 有効期限: ${DomHelper.escapeHtml(validUntilDate)}` : ''}</p>
                ${this.renderSlots(current, required, layoutStyle)}
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

    renderSlot(index: number, filled: boolean, isNext: boolean): string {
        const number = index + 1;
        const classes = ['stamp-slot'];
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
        const content = filled
            ? '<i class="fas fa-stamp" aria-hidden="true"></i>'
            : `<span class="stamp-slot-number">${number}</span>`;

        return `<div class="${classes.join(' ')}" aria-label="${DomHelper.escapeHtml(label)}">${content}</div>`;
    }

    renderSlots(current: number, required: number, layoutStyle?: string): string {
        const slots = [];
        for (let i = 0; i < required; i++) {
            const filled = i < current;
            const isNext = !filled && i === current && current < required;
            slots.push(this.renderSlot(i, filled, isNext));
        }
        return `<div class="stamp-slots stamp-slots--${layoutStyle || 'grid'}" role="img" aria-label="${current}/${required} スタンプ">${slots.join('')}</div>`;
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

    render(cards: StampCard[], defaultImage?: string | null): void {
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
                    <button type="button" class="stamp-card-item stamp-card-item--layout-${layoutStyle} ${completed ? 'stamp-card-item--completed' : ''}" data-stamp-id="${DomHelper.escapeHtml(String(card.id ?? ''))}">
                        ${imageUrl ? `<div class="stamp-card-image"><img src="${DomHelper.escapeHtml(imageUrl)}" alt="スタンプカード" loading="lazy" onerror="this.closest('.stamp-card-image').style.display='none'"></div>` : '<div class="stamp-card-image stamp-card-image--icon"><i class="fas fa-stamp" aria-hidden="true"></i></div>'}
                        <div class="stamp-card-header">
                            <h3 class="stamp-card-name"><i class="fas fa-stamp stamp-card-name-icon" aria-hidden="true"></i>${DomHelper.escapeHtml(card.name || 'スタンプカード')}</h3>
                            ${completed ? '<span class="stamp-card-badge"><i class="fas fa-check-circle"></i> 達成</span>' : ''}
                        </div>
                        ${this.renderSlots(current, required, layoutStyle)}
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
            <div class="stamps-list">${html}</div>
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
