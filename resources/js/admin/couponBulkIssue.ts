type CouponBulkIssueConfig = {
    initialSelectedMembers?: Array<number | string>;
    initialDeliveryType?: string;
    previewUrl?: string;
    previewMembersUrl?: string;
    membersUrl?: string;
    bulkIssueUrl?: string;
    memberCount?: number;
};

type MemberSearchResult = {
    id: number;
    member_number?: string;
    name?: string;
};

type PreviewMember = {
    member_id: number;
    member_name: string;
    member_number: string;
};

type DeliveryMode = 'all' | 'personal' | 'segment' | 'scheduled';
type ScheduledAudience = 'all' | 'personal' | 'segment';
type SegmentPreset = '' | 'birthday' | 'new_members' | 'inactive' | 'rank' | 'rank_up_arrival' | 'stamp' | 'days_after_visit';

function initCouponBulkIssue(): void {
    const cfg = (window.CouponBulkIssueConfig || {}) as CouponBulkIssueConfig;
    let selectedMembers = (cfg.initialSelectedMembers || []).map((id) => parseInt(String(id), 10)).filter((id) => id > 0);
    const memberCountTotal = typeof cfg.memberCount === 'number' ? cfg.memberCount : 0;

    function $(sel: string): HTMLElement | null {
        return document.querySelector(sel);
    }

    function $all(sel: string): NodeListOf<HTMLElement> {
        return document.querySelectorAll(sel);
    }

    function toggleBlock(id: string, hidden: boolean): void {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('is-hidden', hidden);
    }

    function getDeliveryMode(): DeliveryMode {
        const el = document.querySelector('input[name="delivery_mode"]:checked') as HTMLInputElement | null;
        return (el?.value as DeliveryMode) || 'all';
    }

    function getScheduledAudience(): ScheduledAudience {
        const el = document.querySelector('input[name="scheduled_audience"]:checked') as HTMLInputElement | null;
        return (el?.value as ScheduledAudience) || 'all';
    }

    function getSegmentPreset(): SegmentPreset {
        const el = $('#segment_preset') as HTMLInputElement | null;
        return ((el?.value || '') as SegmentPreset);
    }

    function setSegmentPreset(preset: SegmentPreset): void {
        const el = $('#segment_preset') as HTMLInputElement | null;
        if (el) el.value = preset;
        $all('.segment-preset-chip').forEach((chip) => {
            chip.classList.toggle('is-active', chip.getAttribute('data-preset') === preset);
        });
        togglePresetBlocks();
        syncDeliveryType();
        updateTargetPreview();
    }

    function hasAttributeFilters(): boolean {
        const ids = [
            'segment_channel', 'segment_birthday_month', 'segment_sex', 'segment_rank', 'segment_product_id',
            'segment_visit_count_min', 'segment_visit_count_max',
            'segment_lifetime_points_min', 'segment_lifetime_points_max', 'segment_last_visit_mode',
            'segment_stamp_card_id', 'segment_stamp_count_min',
        ];
        const hasField = ids.some((id) => {
            const el = document.getElementById(id) as HTMLInputElement | HTMLSelectElement | null;
            return el !== null && String(el.value).trim() !== '';
        });
        const stampCompleted = $('#segment_stamp_completed') as HTMLInputElement | null;
        return hasField || !!(stampCompleted && stampCompleted.checked);
    }

    function getSalesCondition(): string {
        const el = document.querySelector('input[name="segment_condition"]:checked') as HTMLInputElement | null;
        return el ? String(el.value) : '';
    }

    function resolveSegmentDeliveryType(isScheduled: boolean): string {
        const preset = getSegmentPreset();
        if (preset === 'rank_up_arrival') return 'event_rank_up_arrival';
        if (preset === 'birthday') return isScheduled ? 'scheduled_birthday' : 'immediate_birthday';
        if (preset === 'new_members') return isScheduled ? 'scheduled_new_members' : 'immediate_new_members';
        if (preset === 'inactive') return isScheduled ? 'scheduled_inactive' : 'immediate_inactive';
        if (preset === 'rank') return isScheduled ? 'scheduled_rank' : 'immediate_rank';
        if (preset === 'stamp') return isScheduled ? 'scheduled_stamp' : 'immediate_stamp';
        if (preset === 'days_after_visit') return isScheduled ? 'scheduled_days_after_visit' : 'immediate_days_after_visit';

        if (hasAttributeFilters()) {
            return isScheduled ? 'scheduled_segment_attributes' : 'immediate_segment_attributes';
        }
        if (getSalesCondition() !== '') {
            return isScheduled ? 'scheduled_segment_sales' : 'immediate_segment_sales';
        }
        return isScheduled ? 'scheduled_segment_attributes' : 'immediate_segment_attributes';
    }

    function syncDeliveryType(): string {
        const mode = getDeliveryMode();
        let deliveryType = 'immediate_all';

        if (mode === 'all') {
            deliveryType = 'immediate_all';
        } else if (mode === 'personal') {
            deliveryType = 'immediate_personal';
        } else if (mode === 'segment') {
            deliveryType = resolveSegmentDeliveryType(false);
        } else if (mode === 'scheduled') {
            const audience = getScheduledAudience();
            if (audience === 'personal') {
                deliveryType = 'scheduled_personal';
            } else if (audience === 'segment') {
                deliveryType = resolveSegmentDeliveryType(true);
            } else {
                deliveryType = 'scheduled_all';
            }
        }

        const hidden = $('#delivery_type') as HTMLInputElement | null;
        if (hidden) hidden.value = deliveryType;
        return deliveryType;
    }

    function applyInitialDeliveryType(deliveryType: string): void {
        let mode: DeliveryMode = 'all';
        let audience: ScheduledAudience = 'all';
        let preset: SegmentPreset = '';

        if (deliveryType.indexOf('scheduled_') === 0) {
            mode = 'scheduled';
            if (deliveryType === 'scheduled_personal') audience = 'personal';
            else if (deliveryType === 'scheduled_all') audience = 'all';
            else audience = 'segment';
        } else if (deliveryType === 'event_rank_up_arrival') {
            mode = 'segment';
            preset = 'rank_up_arrival';
        } else if (deliveryType === 'immediate_personal') {
            mode = 'personal';
        } else if (deliveryType !== 'immediate_all') {
            mode = 'segment';
        }

        if (deliveryType.indexOf('birthday') !== -1) preset = 'birthday';
        else if (deliveryType.indexOf('new_members') !== -1) preset = 'new_members';
        else if (deliveryType.indexOf('inactive') !== -1) preset = 'inactive';
        else if (deliveryType === 'event_rank_up_arrival') preset = 'rank_up_arrival';
        else if (deliveryType.indexOf('rank') !== -1) preset = 'rank';
        else if (deliveryType.indexOf('stamp') !== -1) preset = 'stamp';
        else if (deliveryType.indexOf('days_after_visit') !== -1) preset = 'days_after_visit';

        const modeRadio = document.querySelector(`input[name="delivery_mode"][value="${mode}"]`) as HTMLInputElement | null;
        if (modeRadio) modeRadio.checked = true;

        const audienceRadio = document.querySelector(`input[name="scheduled_audience"][value="${audience}"]`) as HTMLInputElement | null;
        if (audienceRadio) audienceRadio.checked = true;

        const presetEl = $('#segment_preset') as HTMLInputElement | null;
        if (presetEl) presetEl.value = preset;
        $all('.segment-preset-chip').forEach((chip) => {
            chip.classList.toggle('is-active', chip.getAttribute('data-preset') === preset);
        });
    }

    function updateSelectedCoupon(): void {
        const checked = document.querySelector('.coupon-picker-input:checked') as HTMLInputElement | null;
        const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
        const preview = $('#selectedCouponPreview');
        const label = $('#selectedCouponLabel');
        const submitBtn = $('#submitDeliveryBtn') as HTMLButtonElement | null;

        $all('.coupon-picker-card').forEach((card) => {
            card.classList.toggle('is-selected', checked ? card.contains(checked) : false);
        });

        if (!checked) {
            if (couponIdEl && couponIdEl.value) {
                if (preview) preview.classList.remove('is-empty');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.title = '';
                }
                updateStepIndicator();
                updateTargetPreview();
                return;
            }
            if (preview) preview.classList.add('is-empty');
            if (label) label.textContent = '未選択 — 上でクーポンを選んでください';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.title = '配布するクーポンを選択してください';
            }
            if (couponIdEl) couponIdEl.value = '';
            updateStepIndicator();
            updateTargetPreview();
            return;
        }

        if (couponIdEl) couponIdEl.value = checked.value;
        if (preview) preview.classList.remove('is-empty');
        if (label) {
            const discount = checked.getAttribute('data-discount') || '';
            label.textContent = `${checked.getAttribute('data-name') || ''}（${checked.getAttribute('data-code') || ''}）${discount ? ` / ${discount}` : ''}`;
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.title = '';
        }
        $all('.pagination-admin a.pagination-item').forEach((link) => {
            try {
                const url = new URL((link as HTMLAnchorElement).href, window.location.origin);
                url.searchParams.set('coupon_id', checked.value);
                (link as HTMLAnchorElement).href = url.toString();
            } catch (_e) {
                // ignore invalid href
            }
        });
        updateStepIndicator();
        updateTargetPreview();
    }

    function updateStepIndicator(): void {
        const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
        const hasCoupon = !!(couponIdEl && couponIdEl.value);
        const step1 = document.querySelector('.bulk-step[data-step="1"]');
        const step2 = document.querySelector('.bulk-step[data-step="2"]');
        const step3 = document.querySelector('.bulk-step[data-step="3"]');
        if (step1) {
            step1.classList.toggle('is-done', hasCoupon);
            step1.classList.toggle('is-current', !hasCoupon);
        }
        if (step2) {
            step2.classList.toggle('is-current', hasCoupon);
            step2.classList.toggle('is-done', false);
        }
        if (step3) {
            step3.classList.toggle('is-current', false);
            step3.classList.toggle('is-done', false);
        }
        const section = $('#deliverySection');
        if (section) section.classList.toggle('is-disabled', !hasCoupon);
    }

    function togglePresetBlocks(): void {
        const preset = getSegmentPreset();
        const showRank = preset === 'rank' || preset === 'rank_up_arrival';
        toggleBlock('rankBlock', !showRank);
        toggleBlock('stampBlock', preset !== 'stamp');
        toggleBlock('daysAfterVisitBlock', preset !== 'days_after_visit');
        toggleBlock('birthdayBlock', preset !== 'birthday');
        toggleBlock('newMemberBlock', preset !== 'new_members');
        toggleBlock('inactiveBlock', preset !== 'inactive');
        toggleBlock('customSegmentPanel', preset !== '');

        const rankLabel = $('#targetRankLabel');
        const rankHelp = $('#targetRankHelp');
        if (rankLabel) {
            rankLabel.innerHTML = preset === 'rank_up_arrival'
                ? '到達ランク <span class="text-danger">*</span>'
                : '対象ランク <span class="text-danger">*</span>';
        }
        if (rankHelp) {
            rankHelp.textContent = preset === 'rank_up_arrival'
                ? '指定ランクへ昇格したタイミングで自動発行します（日時指定は不要です）'
                : 'いまそのランクの会員へ配布します';
        }
    }

    function toggleSegmentMinAmount(): void {
        const cond = getSalesCondition();
        const block = $('#segmentMinAmountBlock');
        if (block) block.style.display = cond === 'min_amount' ? 'block' : 'none';
    }

    function toggleDeliveryBlocks(): void {
        const mode = getDeliveryMode();
        const audience = getScheduledAudience();
        const preset = getSegmentPreset();
        const isRankUpEvent = preset === 'rank_up_arrival' && (mode === 'segment' || (mode === 'scheduled' && audience === 'segment'));
        const showScheduled = mode === 'scheduled' && !isRankUpEvent;
        const showPersonal = mode === 'personal' || (showScheduled && audience === 'personal');
        const showSegment = mode === 'segment' || (mode === 'scheduled' && audience === 'segment');

        toggleBlock('scheduledBlock', !showScheduled);
        toggleBlock('personalBlock', !showPersonal);
        toggleBlock('segmentBlock', !showSegment);
        togglePresetBlocks();
        toggleSegmentMinAmount();

        const submitLabel = $('#submitDeliveryLabel');
        if (submitLabel) {
            if (isRankUpEvent || syncDeliveryType() === 'event_rank_up_arrival') {
                submitLabel.textContent = '配信を登録';
            } else {
                submitLabel.textContent = showScheduled ? '予約する' : '配布する';
            }
        }

        syncDeliveryType();
        updateTargetPreview();
    }

    $all('.coupon-picker-input').forEach((input) => {
        input.addEventListener('change', updateSelectedCoupon);
    });

    $all('input[name="delivery_mode"]').forEach((radio) => {
        radio.addEventListener('change', toggleDeliveryBlocks);
    });
    $all('input[name="scheduled_audience"]').forEach((radio) => {
        radio.addEventListener('change', toggleDeliveryBlocks);
    });
    $all('input[name="segment_condition"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            toggleSegmentMinAmount();
            syncDeliveryType();
            updateTargetPreview();
        });
    });

    $all('.segment-preset-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            setSegmentPreset((chip.getAttribute('data-preset') || '') as SegmentPreset);
        });
    });

    [
        'segment_period_days', 'segment_min_amount', 'birthday_month', 'new_member_days', 'inactive_days', 'scheduled_at',
        'segment_channel', 'segment_birthday_month', 'segment_visit_count_min', 'segment_visit_count_max',
        'segment_lifetime_points_min', 'segment_lifetime_points_max',
        'segment_last_visit_mode', 'segment_last_visit_days', 'segment_sex', 'segment_rank', 'segment_product_id',
        'target_rank', 'stamp_card_id', 'stamp_count_min', 'stamp_completed', 'days_after_last_visit',
        'segment_stamp_card_id', 'segment_stamp_count_min', 'segment_stamp_completed',
    ].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                syncDeliveryType();
                updateTargetPreview();
            });
            el.addEventListener('input', () => {
                syncDeliveryType();
                updateTargetPreview();
            });
        }
    });

    let previewTimer: number | undefined;
    function buildPreviewParams(deliveryType: string): URLSearchParams {
        const params = new URLSearchParams();
        params.set('delivery_type', deliveryType);
        selectedMembers.forEach((id) => {
            params.append('target_member_ids[]', String(id));
        });

        [
            'segment_period_days', 'segment_condition', 'segment_min_amount', 'birthday_month', 'new_member_days', 'inactive_days',
            'segment_channel', 'segment_birthday_month', 'segment_visit_count_min', 'segment_visit_count_max',
            'segment_lifetime_points_min', 'segment_lifetime_points_max',
            'segment_last_visit_mode', 'segment_last_visit_days', 'segment_sex', 'segment_rank', 'segment_product_id',
            'target_rank', 'stamp_card_id', 'stamp_count_min', 'days_after_last_visit',
            'segment_stamp_card_id', 'segment_stamp_count_min',
        ].forEach((key) => {
            const el = document.querySelector(`[name="${key}"]`) as HTMLInputElement | null;
            if (el && el.value) params.set(key, el.value);
        });
        const stampCompleted = $('#stamp_completed') as HTMLInputElement | null;
        if (stampCompleted && stampCompleted.checked) params.set('stamp_completed', '1');
        const segmentStampCompleted = $('#segment_stamp_completed') as HTMLInputElement | null;
        if (segmentStampCompleted && segmentStampCompleted.checked) params.set('segment_stamp_completed', '1');
        return params;
    }

    function updateTargetPreview(): void {
        window.clearTimeout(previewTimer);
        previewTimer = window.setTimeout(() => {
            const previewText = $('#targetPreviewText');
            const previewBtn = $('#previewMembersBtn') as HTMLButtonElement | null;
            if (!previewText) return;

            const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
            const couponId = couponIdEl ? couponIdEl.value : '';
            if (!couponId) {
                previewText.textContent = 'クーポンを選択すると発行予定人数が表示されます';
                if (previewBtn) previewBtn.disabled = true;
                const preview = $('#targetPreview');
                if (preview) preview.classList.remove('is-loading');
                return;
            }
            if (!cfg.previewUrl) {
                previewText.textContent = '発行予定人数: —';
                if (previewBtn) previewBtn.disabled = true;
                return;
            }

            const deliveryType = syncDeliveryType();
            const previewBox = $('#targetPreview');
            if (previewBox) previewBox.classList.add('is-loading');
            previewText.textContent = '発行予定人数: 計算中...';
            if (previewBtn) previewBtn.disabled = true;

            fetch(`${cfg.previewUrl}?${buildPreviewParams(deliveryType).toString()}`)
                .then((r) => r.json())
                .then((data: { count?: number; total?: number; is_event?: boolean }) => {
                    const total = typeof data.total === 'number' ? data.total : memberCountTotal;
                    if (data.is_event || deliveryType === 'event_rank_up_arrival') {
                        previewText.textContent = `発行予定人数: 昇格時に自動 / ${total}`;
                        if (previewBtn) previewBtn.disabled = true;
                    } else {
                        const count = typeof data.count === 'number' ? data.count : 0;
                        previewText.textContent = `発行予定人数: ${count} / ${total}`;
                        if (previewBtn) previewBtn.disabled = count < 1;
                    }
                    if (previewBox) previewBox.classList.remove('is-loading');
                })
                .catch(() => {
                    previewText.textContent = '発行予定人数: —';
                    if (previewBtn) previewBtn.disabled = true;
                    if (previewBox) previewBox.classList.remove('is-loading');
                });
        }, 350);
    }

    function openPreviewMembersModal(): void {
        const modal = $('#previewMembersModal');
        const body = $('#previewMembersModalBody');
        const note = $('#previewMembersModalNote');
        if (!modal || !body || !cfg.previewMembersUrl) return;

        const deliveryType = syncDeliveryType();
        if (deliveryType === 'event_rank_up_arrival') return;

        body.innerHTML = '<p class="text-muted small">読み込み中...</p>';
        if (note) note.textContent = '';
        modal.classList.remove('is-hidden');

        fetch(`${cfg.previewMembersUrl}?${buildPreviewParams(deliveryType).toString()}`)
            .then((r) => r.json())
            .then((data: { members?: PreviewMember[]; total?: number; error?: string | null }) => {
                body.replaceChildren();
                if (data.error) {
                    const err = document.createElement('p');
                    err.className = 'text-danger';
                    err.textContent = data.error;
                    body.appendChild(err);
                    return;
                }
                const members = data.members || [];
                const total = typeof data.total === 'number' ? data.total : members.length;
                if (note) {
                    note.textContent = total > members.length
                        ? `全${total}人中、先頭${members.length}人を表示しています`
                        : `対象 ${total}人`;
                }
                if (members.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'text-muted';
                    empty.textContent = '対象会員がいません';
                    body.appendChild(empty);
                    return;
                }
                const table = document.createElement('table');
                table.className = 'table';
                const thead = document.createElement('thead');
                const headRow = document.createElement('tr');
                ['会員名', '会員番号'].forEach((label) => {
                    const th = document.createElement('th');
                    th.textContent = label;
                    headRow.appendChild(th);
                });
                thead.appendChild(headRow);
                const tbody = document.createElement('tbody');
                members.forEach((m) => {
                    const tr = document.createElement('tr');
                    const nameTd = document.createElement('td');
                    nameTd.textContent = m.member_name;
                    const numberTd = document.createElement('td');
                    numberTd.textContent = m.member_number;
                    tr.appendChild(nameTd);
                    tr.appendChild(numberTd);
                    tbody.appendChild(tr);
                });
                table.appendChild(thead);
                table.appendChild(tbody);
                body.appendChild(table);
            })
            .catch(() => {
                body.replaceChildren();
                const err = document.createElement('p');
                err.className = 'text-danger';
                err.textContent = '対象会員の取得に失敗しました';
                body.appendChild(err);
            });
    }

    function closePreviewMembersModal(): void {
        const modal = $('#previewMembersModal');
        if (modal) modal.classList.add('is-hidden');
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

    const previewMembersBtn = $('#previewMembersBtn');
    if (previewMembersBtn) {
        previewMembersBtn.addEventListener('click', openPreviewMembersModal);
    }
    $all('[data-close-preview-modal]').forEach((el) => {
        el.addEventListener('click', closePreviewMembersModal);
    });

    const form = $('#bulkIssueForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const deliveryType = syncDeliveryType();
            const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
            if (!couponIdEl || !couponIdEl.value) {
                e.preventDefault();
                alert('配布するクーポンを選択してください');
                return;
            }
            if (deliveryType === 'event_rank_up_arrival') {
                const targetRank = $('#target_rank') as HTMLSelectElement | null;
                if (!targetRank || String(targetRank.value).trim() === '') {
                    e.preventDefault();
                    alert('到達ランクを選択してください');
                }
                return;
            }
            if ((deliveryType === 'immediate_personal' || deliveryType === 'scheduled_personal') && selectedMembers.length === 0) {
                e.preventDefault();
                alert('個人指定では会員を1人以上選択してください');
                return;
            }
            if (deliveryType.indexOf('segment_attributes') !== -1 && getSegmentPreset() === '' && !hasAttributeFilters() && getSalesCondition() === '') {
                e.preventDefault();
                alert('セグメント配信では1つ以上の絞り込み条件を指定してください');
                return;
            }
            if (deliveryType.indexOf('segment_sales') !== -1 && getSalesCondition() === '') {
                e.preventDefault();
                alert('売上条件を選択してください');
                return;
            }
            if (deliveryType.indexOf('_rank') !== -1) {
                const targetRank = $('#target_rank') as HTMLSelectElement | null;
                if (!targetRank || String(targetRank.value).trim() === '') {
                    e.preventDefault();
                    alert('対象ランクを選択してください');
                    return;
                }
            }
            if (deliveryType.indexOf('_stamp') !== -1) {
                const stampCard = $('#stamp_card_id') as HTMLSelectElement | null;
                const stampMin = $('#stamp_count_min') as HTMLInputElement | null;
                const stampCompleted = $('#stamp_completed') as HTMLInputElement | null;
                const hasCard = !!(stampCard && String(stampCard.value).trim() !== '');
                const hasMin = !!(stampMin && String(stampMin.value).trim() !== '');
                const hasCompleted = !!(stampCompleted && stampCompleted.checked);
                if (!hasCard && !hasMin && !hasCompleted) {
                    e.preventDefault();
                    alert('スタンプカード・累計枚数・達成のいずれかを指定してください');
                    return;
                }
            }
            if (deliveryType.indexOf('days_after_visit') !== -1) {
                const days = $('#days_after_last_visit') as HTMLInputElement | null;
                if (!days || String(days.value).trim() === '' || parseInt(days.value, 10) < 1) {
                    e.preventDefault();
                    alert('最終来店から何日後かを入力してください');
                    return;
                }
            }
            if (deliveryType.indexOf('scheduled_') === 0) {
                const scheduledAt = $('#scheduled_at') as HTMLInputElement | null;
                if (!scheduledAt || !scheduledAt.value) {
                    e.preventDefault();
                    alert('予約配信では配布日時を指定してください');
                }
            }
        });
    }

    const couponSearchBtn = $('#couponPickerSearchBtn') as HTMLButtonElement | null;
    const couponSearchInput = $('#couponPickerSearch') as HTMLInputElement | null;

    function navigateCouponPickerSearch(): void {
        const baseUrl = cfg.bulkIssueUrl || window.location.pathname;
        const params = new URLSearchParams();
        const q = couponSearchInput ? couponSearchInput.value.trim() : '';
        if (q !== '') {
            params.set('q', q);
        }
        const couponIdEl = $('#coupon_id') as HTMLInputElement | null;
        if (couponIdEl && couponIdEl.value) {
            params.set('coupon_id', couponIdEl.value);
        }
        const query = params.toString();
        window.location.href = query !== '' ? `${baseUrl}?${query}` : baseUrl;
    }

    if (couponSearchBtn) {
        couponSearchBtn.addEventListener('click', navigateCouponPickerSearch);
    }
    if (couponSearchInput) {
        couponSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                navigateCouponPickerSearch();
            }
        });
    }

    applyInitialDeliveryType(cfg.initialDeliveryType || 'immediate_all');
    renderSelectedMembers();
    toggleDeliveryBlocks();
    updateSelectedCoupon();
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
