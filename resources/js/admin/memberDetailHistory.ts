type HistoryResponse = {
    html: string;
    total: number;
    section: string;
    keyword: string;
    from: string;
    to: string;
};

function initMemberDetailHistory(): void {
    if (document.body.dataset.pageKind !== 'member-detail') {
        return;
    }

    document.querySelectorAll<HTMLElement>('.member-history-panel').forEach((panel) => {
        const form = panel.querySelector<HTMLFormElement>('[data-history-form]');
        const toggle = panel.querySelector<HTMLButtonElement>('[data-history-toggle]');
        const input = panel.querySelector<HTMLInputElement>('[data-history-input]');
        const fromInput = panel.querySelector<HTMLInputElement>('[data-history-from]');
        const toInput = panel.querySelector<HTMLInputElement>('[data-history-to]');
        const body = panel.querySelector<HTMLElement>('[data-history-body]');
        const url = panel.dataset.historyUrl;
        const queryKey = panel.dataset.queryKey;
        const fromKey = panel.dataset.fromKey;
        const toKey = panel.dataset.toKey;
        if (
            form === null
            || toggle === null
            || input === null
            || fromInput === null
            || toInput === null
            || body === null
            || url === undefined
            || queryKey === undefined
            || fromKey === undefined
            || toKey === undefined
        ) {
            return;
        }

        let requestId = 0;

        const setSearchOpen = (isOpen: boolean): void => {
            panel.classList.toggle('is-search-open', isOpen);
            if (isOpen) {
                form.removeAttribute('hidden');
            } else {
                form.setAttribute('hidden', '');
            }
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? '検索を閉じる' : '検索を開く');
            toggle.title = isOpen ? '検索を閉じる' : '検索';
            const icon = toggle.querySelector('i');
            if (icon !== null) {
                icon.className = isOpen ? 'fas fa-times' : 'fas fa-search';
            }
            if (isOpen) {
                input.focus();
            }
        };

        const readFilters = (): { keyword: string; from: string; to: string } => ({
            keyword: input.value.trim(),
            from: fromInput.value.trim(),
            to: toInput.value.trim(),
        });

        const updateBrowserQuery = (keyword: string, from: string, to: string): void => {
            const params = new URLSearchParams(window.location.search);
            const setOrDelete = (key: string, value: string): void => {
                if (value === '') {
                    params.delete(key);
                } else {
                    params.set(key, value);
                }
            };
            setOrDelete(queryKey, keyword);
            setOrDelete(fromKey, from);
            setOrDelete(toKey, to);
            [
                'visits_page', 'points_page', 'coupons_page',
                'stamps_page', 'rank_page', 'redemptions_page', 'page',
            ].forEach((key) => params.delete(key));
            const qs = params.toString();
            const next = qs === '' ? window.location.pathname : `${window.location.pathname}?${qs}`;
            window.history.replaceState({}, '', next + window.location.hash);
        };

        const load = async (keyword: string, from: string, to: string, page: number): Promise<void> => {
            const currentId = ++requestId;
            panel.classList.add('is-loading');
            const params = new URLSearchParams();
            if (keyword !== '') {
                params.set('q', keyword);
            }
            if (from !== '') {
                params.set('from', from);
            }
            if (to !== '') {
                params.set('to', to);
            }
            if (page > 1) {
                params.set('page', String(page));
            }
            const requestUrl = params.toString() === '' ? url : `${url}?${params.toString()}`;
            try {
                const res = await fetch(requestUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    throw new Error(`history fetch failed: ${res.status}`);
                }
                const data = (await res.json()) as HistoryResponse;
                if (currentId !== requestId) {
                    return;
                }
                body.innerHTML = data.html;
                updateBrowserQuery(keyword, from, to);
            } catch (error) {

                console.error('[member-detail-history]', error);
            } finally {
                if (currentId === requestId) {
                    panel.classList.remove('is-loading');
                }
            }
        };

        toggle.addEventListener('click', () => {
            setSearchOpen(!panel.classList.contains('is-search-open'));
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const filters = readFilters();
            void load(filters.keyword, filters.from, filters.to, 1);
        });

        body.addEventListener('click', (event) => {
            const target = event.target as HTMLElement | null;
            const link = target?.closest('a.pagination-item') as HTMLAnchorElement | null;
            if (link === null || !body.contains(link)) {
                return;
            }
            event.preventDefault();
            const href = link.getAttribute('href');
            if (href === null || href === '') {
                return;
            }
            let page = 1;
            try {
                const parsed = new URL(href, window.location.origin);
                const sectionPageName = queryKey.replace(/_q$/, '_page');
                const rawPage = parsed.searchParams.get('page')
                    ?? parsed.searchParams.get(sectionPageName);
                page = Math.max(1, parseInt(rawPage || '1', 10) || 1);
            } catch {
                page = 1;
            }
            const filters = readFilters();
            void load(filters.keyword, filters.from, filters.to, page);
        });
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMemberDetailHistory);
} else {
    initMemberDetailHistory();
}
