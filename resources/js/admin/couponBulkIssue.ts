type CouponBulkIssueConfig = {
    initialSelectedMembers?: Array<number | string>;
    initialCouponSource?: string;
    personalTypes?: string[];
    segmentTypes?: string[];
    birthdayTypes?: string[];
    newMemberTypes?: string[];
    inactiveTypes?: string[];
    scheduledTypes?: string[];
    previewUrl?: string;
    membersUrl?: string;
};

type MemberSearchResult = {
    id: number;
    member_number?: string;
    name?: string;
};

function initCouponBulkIssue(): void {
    const cfg = (window.CouponBulkIssueConfig || {}) as CouponBulkIssueConfig;
    let selectedMembers = (cfg.initialSelectedMembers || []).map((id) => parseInt(String(id), 10)).filter((id) => id > 0);

    function $(sel: string): HTMLElement | null {
        return document.querySelector(sel);
    }

    function $all(sel: string): NodeListOf<HTMLElement> {
        return document.querySelectorAll(sel);
    }

    function getCurrentSource(): string {
        const field = $('#coupon_source_field') as HTMLInputElement | null;
        return field ? field.value : (cfg.initialCouponSource || 'existing');
    }

    function syncPickerFromHidden(): void {
        const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
        if (!couponIdEl || !couponIdEl.value) {
            return;
        }
        const radios = $all('.coupon-picker-input') as NodeListOf<HTMLInputElement>;
        if (!radios.length) {
            return;
        }
        radios.forEach((radio) => {
            radio.checked = radio.value === couponIdEl.value;
        });
    }

    function updateSelectedCoupon(): void {
        syncPickerFromHidden();

        const checked = document.querySelector('.coupon-picker-input:checked') as HTMLInputElement | null;
        const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
        const preview = $('#selectedCouponPreview');
        const label = $('#selectedCouponLabel');
        const submitBtn = $('#submitDeliveryBtn') as HTMLButtonElement | null;
        const onNewTab = getCurrentSource() === 'new';

        $all('.coupon-picker-card').forEach((card) => {
            card.classList.toggle('is-selected', checked ? card.contains(checked) : false);
        });

        if (!checked) {
            if (couponIdEl && couponIdEl.value) {
                if (preview) preview.classList.remove('is-empty');
                if (submitBtn) submitBtn.disabled = onNewTab;
                if (submitBtn) submitBtn.title = onNewTab ? '先にクーポンを作成してください' : '';
                updateTargetPreview();
                return;
            }
            if (preview) preview.classList.add('is-empty');
            if (label) label.textContent = '未選択 — 上でクーポンを選ぶか作成してください';
            if (submitBtn) submitBtn.disabled = true;
            if (submitBtn) submitBtn.title = '配布するクーポンを選択してください';
            if (couponIdEl) couponIdEl.value = '';
            updateTargetPreview();
            return;
        }

        if (couponIdEl) couponIdEl.value = checked.value;
        if (preview) preview.classList.remove('is-empty');
        if (label) {
            const discount = checked.getAttribute('data-discount') || '';
            label.textContent = `${checked.getAttribute('data-name') || ''}（${checked.getAttribute('data-code') || ''}）${discount ? ` / ${discount}` : ''}`;
        }
        if (submitBtn) submitBtn.disabled = onNewTab;
        if (submitBtn) submitBtn.title = onNewTab ? '先にクーポンを作成してください' : '';
        updateTargetPreview();
    }

    function updateDeliverySectionState(): void {
        const section = $('#deliverySection');
        const onNewTab = getCurrentSource() === 'new';
        if (section) section.classList.toggle('is-disabled', onNewTab);
        updateSelectedCoupon();
    }

    function setCouponSource(source: string | null): void {
        const nextSource = source || 'existing';
        $all('.coupon-source-tab').forEach((tab) => {
            const active = tab.getAttribute('data-source') === nextSource;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        const panelExisting = $('#panelExisting');
        const panelNew = $('#panelNew');
        if (panelExisting) panelExisting.classList.toggle('is-hidden', nextSource !== 'existing');
        if (panelNew) panelNew.classList.toggle('is-hidden', nextSource !== 'new');
        const field = $('#coupon_source_field') as HTMLInputElement | null;
        if (field) field.value = nextSource;
        updateDeliverySectionState();
    }

    $all('.coupon-source-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            setCouponSource(tab.getAttribute('data-source'));
        });
    });

    $all('.coupon-picker-input').forEach((input) => {
        input.addEventListener('change', updateSelectedCoupon);
    });

    function currentDeliveryType(): string {
        const el = document.querySelector('input[name="delivery_type"]:checked') as HTMLInputElement | null;
        return el ? el.value : 'immediate_all';
    }

    function includesType(list: string[] | undefined, type: string): boolean {
        return (list || []).indexOf(type) !== -1;
    }

    function toggleBlock(id: string, hidden: boolean): void {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('is-hidden', hidden);
    }

    function toggleDeliveryBlocks(): void {
        const type = currentDeliveryType();
        const personal = includesType(cfg.personalTypes, type);
        const segment = includesType(cfg.segmentTypes, type);
        const birthday = includesType(cfg.birthdayTypes, type);
        const newMember = includesType(cfg.newMemberTypes, type);
        const inactive = includesType(cfg.inactiveTypes, type);
        const scheduled = includesType(cfg.scheduledTypes, type);

        toggleBlock('personalBlock', !personal);
        toggleBlock('segmentBlock', !segment);
        toggleBlock('birthdayBlock', !birthday);
        toggleBlock('newMemberBlock', !newMember);
        toggleBlock('inactiveBlock', !inactive);
        toggleBlock('scheduledBlock', !scheduled);

        const submitLabel = $('#submitDeliveryLabel');
        if (submitLabel) {
            submitLabel.textContent = scheduled ? '予約する' : '配布する';
        }

        toggleSegmentMinAmount();
        updateTargetPreview();
    }

    function toggleSegmentMinAmount(): void {
        const cond = document.querySelector('input[name="segment_condition"]:checked') as HTMLInputElement | null;
        const block = $('#segmentMinAmountBlock');
        if (block) block.style.display = cond && cond.value === 'min_amount' ? 'block' : 'none';
    }

    $all('input[name="delivery_type"]').forEach((radio) => {
        radio.addEventListener('change', toggleDeliveryBlocks);
    });
    $all('input[name="segment_condition"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            toggleSegmentMinAmount();
            updateTargetPreview();
        });
    });

    ['segment_period_days', 'segment_min_amount', 'birthday_month', 'new_member_days', 'inactive_days', 'scheduled_at'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', updateTargetPreview);
        if (el) el.addEventListener('input', updateTargetPreview);
    });

    let previewTimer: number | undefined;
    function updateTargetPreview(): void {
        window.clearTimeout(previewTimer);
        previewTimer = window.setTimeout(() => {
            const previewText = $('#targetPreviewText');
            if (!previewText) return;

            const type = currentDeliveryType();
            const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
            const couponId = couponIdEl ? couponIdEl.value : '';
            if (!couponId || getCurrentSource() === 'new') {
                previewText.textContent = 'クーポンを選択すると対象人数が表示されます';
                const preview = $('#targetPreview');
                if (preview) preview.classList.remove('is-loading');
                return;
            }
            if (!cfg.previewUrl) {
                previewText.textContent = '対象会員: —';
                const previewUnavailable = $('#targetPreview');
                if (previewUnavailable) previewUnavailable.classList.remove('is-loading');
                return;
            }

            const previewBox = $('#targetPreview');
            if (previewBox) previewBox.classList.add('is-loading');
            previewText.textContent = '対象会員: 計算中...';

            const params = new URLSearchParams();
            params.set('delivery_type', type);
            selectedMembers.forEach((id) => {
                params.append('target_member_ids[]', String(id));
            });

            ['segment_period_days', 'segment_condition', 'segment_min_amount', 'birthday_month', 'new_member_days', 'inactive_days'].forEach((key) => {
                const el = document.querySelector(`[name="${key}"]`) as HTMLInputElement | null;
                if (el && el.value) params.set(key, el.value);
            });

            fetch(`${cfg.previewUrl}?${params.toString()}`)
                .then((r) => r.json())
                .then((data: { count?: number }) => {
                    previewText.textContent = `対象会員: 約 ${data.count || 0} 人`;
                    if (previewBox) previewBox.classList.remove('is-loading');
                })
                .catch(() => {
                    previewText.textContent = '対象会員: —';
                    if (previewBox) previewBox.classList.remove('is-loading');
                });
        }, 350);
    }

    function renderSelectedMembers(): void {
        const container = $('#selectedMembers');
        const hidden = $('#targetMemberIdsHidden');
        if (!container || !hidden) return;
        container.innerHTML = '';
        hidden.innerHTML = '';
        selectedMembers.forEach((id) => {
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.innerHTML = `#${id} <button type="button" data-id="${id}" aria-label="削除">&times;</button>`;
            container.appendChild(tag);
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'target_member_ids[]';
            input.value = String(id);
            hidden.appendChild(input);
        });
        container.querySelectorAll('button').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id') || '', 10);
                selectedMembers = selectedMembers.filter((x) => x !== id);
                renderSelectedMembers();
                updateTargetPreview();
            });
        });
    }

    const searchInput = $('#memberSearch') as HTMLInputElement | null;
    const resultsDiv = $('#memberSearchResults');
    let searchTimer: number | undefined;

    if (searchInput && resultsDiv && cfg.membersUrl) {
        searchInput.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            const q = searchInput.value.trim();
            if (q.length < 1) {
                resultsDiv.classList.add('is-hidden');
                return;
            }
            searchTimer = window.setTimeout(() => {
                resultsDiv.innerHTML = '<div class="p-2 text-muted small">検索中...</div>';
                resultsDiv.classList.remove('is-hidden');
                fetch(`${cfg.membersUrl}?q=${encodeURIComponent(q)}`)
                    .then((r) => r.json())
                    .then((list: MemberSearchResult[]) => {
                        resultsDiv.innerHTML = '';
                        if (!list || !list.length) {
                            resultsDiv.innerHTML = '<div class="p-2 text-muted small">該当なし</div>';
                        } else {
                            list.forEach((m) => {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'member-search-item';
                                btn.textContent = `${m.member_number || ''} ${m.name || ''} (ID:${m.id})`;
                                btn.addEventListener('click', () => {
                                    if (selectedMembers.indexOf(m.id) === -1) selectedMembers.push(m.id);
                                    renderSelectedMembers();
                                    updateTargetPreview();
                                    resultsDiv.classList.add('is-hidden');
                                    searchInput.value = '';
                                });
                                resultsDiv.appendChild(btn);
                            });
                        }
                        resultsDiv.classList.remove('is-hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', (e) => {
            const target = e.target as Node | null;
            if (!resultsDiv.contains(target as Node) && target !== searchInput) {
                resultsDiv.classList.add('is-hidden');
            }
        });
    }

    const form = $('#bulkIssueForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (getCurrentSource() === 'new') {
                e.preventDefault();
                alert('先にクーポンを作成するか、「既存クーポンを使う」タブで選択してください');
                return;
            }
            const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
            if (!couponIdEl || !couponIdEl.value) {
                e.preventDefault();
                alert('配布するクーポンを選択してください');
                return;
            }
            if (includesType(cfg.personalTypes, currentDeliveryType()) && selectedMembers.length === 0) {
                e.preventDefault();
                alert('個人指定では会員を1人以上選択してください');
            }
        });
    }

    setCouponSource(cfg.initialCouponSource || 'existing');
    renderSelectedMembers();
    toggleDeliveryBlocks();
}

export function bootstrapCouponBulkIssue(): void {
    if (document.body.dataset.pageKind !== 'coupon-bulk-issue') {
        return;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCouponBulkIssue);
    } else {
        initCouponBulkIssue();
    }
}

bootstrapCouponBulkIssue();
