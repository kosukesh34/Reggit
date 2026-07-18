import { AppConstants } from '../../shared/constants';
import { DomHelper } from '../../shared/utils/domHelper';

export type LiffPageKey =
    | 'announcement_detail'
    | 'product_detail'
    | 'coupon_detail'
    | 'gallery_detail'
    | 'stamp_detail'
    | 'order_cart'
    | 'order_confirm'
    | 'order_complete'
    | 'reservation_new'
    | 'reservation_confirm'
    | 'reservation_complete';

export type OpenPageOptions = {
    key: LiffPageKey;
    hash: string;
    returnHash?: string;
    title?: string;
    bodyClass?: string;
};

const TAB_KEYS = new Set([
    'home', 'products', 'orders', 'stamps', 'coupons', 'queue',
    'member', 'reservations', 'gallery', 'announcements',
]);

const PAGE_TITLES: Partial<Record<LiffPageKey, string>> = {
    announcement_detail: 'お知らせ',
    product_detail: '商品詳細',
    coupon_detail: 'クーポン詳細',
    gallery_detail: 'ギャラリー',
    stamp_detail: 'スタンプ',
    order_cart: 'カート',
    order_confirm: '注文確認',
    order_complete: '注文完了',
    reservation_new: '予約',
    reservation_confirm: '予約確認',
    reservation_complete: '予約完了',
};

/**
 * タブ外の詳細・フローページを hash で開閉する共通ルーター。
 */
export class LiffPageRouter {
    private currentKey: LiffPageKey | null = null;
    private returnHash = 'home';
    private activeBodyClass: string | null = null;
    private suppressHashUntil: string | null = null;

    getPage(): HTMLElement | null {
        return document.getElementById('liffDetailPage') as HTMLElement | null
            ?? document.getElementById('announcementDetailPage') as HTMLElement | null;
    }

    getSection(): HTMLElement | null {
        return document.getElementById('liffDetailSection') as HTMLElement | null
            ?? document.getElementById('announcementDetailSection') as HTMLElement | null;
    }

    getCurrentKey(): LiffPageKey | null {
        return this.currentKey;
    }

    getReturnHash(): string {
        return this.returnHash;
    }

    isOpen(): boolean {
        return this.currentKey !== null;
    }

    resolveReturnHash(fallback: string, explicit?: string): string {
        if (typeof explicit === 'string' && explicit !== '') {
            return explicit.replace(/^#/, '');
        }
        if (typeof window !== 'undefined' && window.app && typeof window.app.currentTab === 'string') {
            const tab = window.app.currentTab;
            if (tab !== '' && TAB_KEYS.has(tab)) {
                return tab;
            }
        }
        return fallback;
    }

    openPage(options: OpenPageOptions): HTMLElement | null {
        const page = this.getPage();
        const section = this.getSection();
        if (page === null || section === null) {
            return null;
        }

        this.returnHash = this.resolveReturnHash('home', options.returnHash);
        this.currentKey = options.key;
        section.dataset.page = options.key;
        delete section.dataset.layoutRendered;

        const titleEl = document.getElementById('liffDetailPageTitle');
        if (titleEl !== null) {
            titleEl.textContent = options.title ?? PAGE_TITLES[options.key] ?? '';
        }

        const back = page.querySelector('[data-liff-page-back], [data-announcement-back]') as HTMLAnchorElement | null;
        if (back !== null) {
            back.setAttribute('href', '#' + this.returnHash);
        }

        this.showPage(page, options.bodyClass ?? ('liff-page-' + options.key.replace(/_/g, '-')));

        const nextHash = options.hash.startsWith('#') ? options.hash : ('#' + options.hash);
        if (typeof window !== 'undefined' && window.location.hash !== nextHash) {
            this.suppressHashUntil = nextHash;
            window.location.hash = nextHash;
        }

        return section;
    }

    closePage(updateHash = true): void {
        if (this.currentKey === null) {
            return;
        }
        const page = this.getPage();
        this.hidePage(page);
        this.currentKey = null;
        if (updateHash && typeof window !== 'undefined') {
            const back = this.returnHash || 'home';
            const nextHash = '#' + back;
            if (window.location.hash !== nextHash) {
                this.suppressHashUntil = nextHash;
                window.location.hash = nextHash;
            } else if (window.app && typeof window.app.switchTab === 'function') {
                window.app.switchTab(back);
            }
        }
    }

    /** hashchange 由来で閉じるとき（既に hash がタブ側） */
    closePageQuiet(): void {
        if (this.currentKey === null) {
            return;
        }
        const page = this.getPage();
        this.hidePage(page);
        this.currentKey = null;
    }

    shouldIgnoreHashChange(): boolean {
        if (this.suppressHashUntil === null || typeof window === 'undefined') {
            return false;
        }
        if (window.location.hash === this.suppressHashUntil) {
            this.suppressHashUntil = null;
            return true;
        }
        return false;
    }

    private showPage(page: HTMLElement, bodyClass: string): void {
        const apply = () => {
            DomHelper.querySelectorAll('.tab-content').forEach((content) => {
                DomHelper.removeClass(content, AppConstants.CSS_CLASSES.ACTIVE);
                (content as HTMLElement).style.display = 'none';
            });
            page.hidden = false;
            DomHelper.addClass(page, AppConstants.CSS_CLASSES.ACTIVE);
            page.style.display = '';
            document.body.classList.add('liff-page-detail', 'liff-detail-animating');
            if (this.activeBodyClass !== null) {
                document.body.classList.remove(this.activeBodyClass);
            }
            this.activeBodyClass = bodyClass;
            document.body.classList.add(bodyClass);
            const main = document.querySelector('.main-content') as HTMLElement | null;
            if (main !== null) {
                main.scrollTop = 0;
            }
            window.scrollTo(0, 0);
            window.setTimeout(() => document.body.classList.remove('liff-detail-animating'), 320);
        };
        this.runWithDetailTransition('open', apply);
    }

    private hidePage(page: HTMLElement | null): void {
        const apply = () => {
            if (page !== null) {
                page.hidden = true;
                DomHelper.removeClass(page, AppConstants.CSS_CLASSES.ACTIVE);
                page.style.display = 'none';
            }
            document.body.classList.remove('liff-page-detail', 'liff-detail-animating');
            if (this.activeBodyClass !== null) {
                document.body.classList.remove(this.activeBodyClass);
                this.activeBodyClass = null;
            }
            DomHelper.querySelectorAll('.tab-content').forEach((content) => {
                (content as HTMLElement).style.display = '';
            });
        };
        this.runWithDetailTransition('close', apply);
    }

    private runWithDetailTransition(kind: 'open' | 'close', apply: () => void): void {
        const reduced =
            typeof window !== 'undefined'
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const startVt = (document as Document & {
            startViewTransition?: (cb: () => void) => { finished?: Promise<unknown> };
        }).startViewTransition;

        if (!reduced && typeof startVt === 'function') {
            const flag = kind === 'open' ? 'liff-vt-detail-open' : 'liff-vt-detail-close';
            document.documentElement.classList.add(flag);
            try {
                const transition = startVt(apply);
                const finished = transition && transition.finished;
                if (finished !== undefined && finished !== null && typeof finished.finally === 'function') {
                    finished.finally(() => document.documentElement.classList.remove(flag));
                } else {
                    window.setTimeout(() => document.documentElement.classList.remove(flag), 320);
                }
                return;
            } catch (_err) {
                document.documentElement.classList.remove(flag);
            }
        }
        apply();
    }
}

export type ParsedLiffHash =
    | { kind: 'tab'; tab: string }
    | { kind: 'announcement'; id: string }
    | { kind: 'product'; id: string }
    | { kind: 'coupon'; id: string }
    | { kind: 'gallery'; id: string }
    | { kind: 'stamp'; id: string }
    | { kind: 'order'; step: 'cart' | 'confirm' | 'complete' }
    | { kind: 'reservation'; step: 'new' | 'confirm' | 'complete' }
    | { kind: 'unknown'; raw: string };

export function parseLiffHash(rawHash: string): ParsedLiffHash {
    const hash = (rawHash || '').replace(/^#/, '');
    if (hash === '') {
        return { kind: 'unknown', raw: hash };
    }
    if (TAB_KEYS.has(hash)) {
        return { kind: 'tab', tab: hash };
    }
    const announcement = hash.match(/^announcement\/([^/]+)$/);
    if (announcement !== null) {
        return { kind: 'announcement', id: announcement[1] };
    }
    const product = hash.match(/^product\/([^/]+)$/);
    if (product !== null) {
        return { kind: 'product', id: product[1] };
    }
    const coupon = hash.match(/^coupon\/([^/]+)$/);
    if (coupon !== null) {
        return { kind: 'coupon', id: coupon[1] };
    }
    const gallery = hash.match(/^gallery\/([^/]+)$/);
    if (gallery !== null) {
        return { kind: 'gallery', id: gallery[1] };
    }
    const stamp = hash.match(/^stamp\/([^/]+)$/);
    if (stamp !== null) {
        return { kind: 'stamp', id: stamp[1] };
    }
    const order = hash.match(/^order\/(cart|confirm|complete)$/);
    if (order !== null) {
        return { kind: 'order', step: order[1] as 'cart' | 'confirm' | 'complete' };
    }
    const reservation = hash.match(/^reservation\/(new|confirm|complete)$/);
    if (reservation !== null) {
        return { kind: 'reservation', step: reservation[1] as 'new' | 'confirm' | 'complete' };
    }
    return { kind: 'unknown', raw: hash };
}

export function isDeepLiffHash(rawHash: string): boolean {
    const parsed = parseLiffHash(rawHash);
    return parsed.kind !== 'tab' && parsed.kind !== 'unknown';
}
