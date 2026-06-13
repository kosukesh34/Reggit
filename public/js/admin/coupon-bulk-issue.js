(function () {
    'use strict';

    function init() {
        const cfg = window.CouponBulkIssueConfig || {};
        let selectedMembers = (cfg.initialSelectedMembers || []).map(function (id) {
            return parseInt(id, 10);
        }).filter(function (id) {
            return id > 0;
        });

        function $(sel) { return document.querySelector(sel); }
        function $all(sel) { return document.querySelectorAll(sel); }

        function getCurrentSource() {
            var field = $('#coupon_source_field');
            return field ? field.value : (cfg.initialCouponSource || 'existing');
        }

        function syncPickerFromHidden() {
            var couponIdEl = $('#coupon_id');
            if (!couponIdEl || !couponIdEl.value) {
                return;
            }
            var radios = $all('.coupon-picker-input');
            if (!radios.length) {
                return;
            }
            radios.forEach(function (radio) {
                radio.checked = radio.value === couponIdEl.value;
            });
        }

        function updateSelectedCoupon() {
            syncPickerFromHidden();

            var checked = document.querySelector('.coupon-picker-input:checked');
            var couponIdEl = $('#coupon_id');
            var preview = $('#selectedCouponPreview');
            var label = $('#selectedCouponLabel');
            var submitBtn = $('#submitDeliveryBtn');
            var onNewTab = getCurrentSource() === 'new';

            if (!checked) {
                if (couponIdEl && couponIdEl.value) {
                    if (preview) preview.classList.remove('is-empty');
                    if (submitBtn) submitBtn.disabled = onNewTab;
                    updateTargetPreview();
                    return;
                }
                if (preview) preview.classList.add('is-empty');
                if (label) label.textContent = '未選択 — 上でクーポンを選ぶか作成してください';
                if (submitBtn) submitBtn.disabled = true;
                if (couponIdEl) couponIdEl.value = '';
                updateTargetPreview();
                return;
            }

            if (couponIdEl) couponIdEl.value = checked.value;
            if (preview) preview.classList.remove('is-empty');
            if (label) {
                label.textContent = (checked.getAttribute('data-name') || '') + '（' + (checked.getAttribute('data-code') || '') + '）';
            }
            if (submitBtn) submitBtn.disabled = onNewTab;

            $all('.coupon-picker-card').forEach(function (card) {
                card.classList.toggle('is-selected', card.contains(checked));
            });
            updateTargetPreview();
        }

        function updateDeliverySectionState() {
            var section = $('#deliverySection');
            var onNewTab = getCurrentSource() === 'new';
            if (section) section.classList.toggle('is-disabled', onNewTab);
            updateSelectedCoupon();
        }

        function setCouponSource(source) {
            $all('.coupon-source-tab').forEach(function (tab) {
                tab.classList.toggle('is-active', tab.getAttribute('data-source') === source);
            });
            var panelExisting = $('#panelExisting');
            var panelNew = $('#panelNew');
            if (panelExisting) panelExisting.classList.toggle('is-hidden', source !== 'existing');
            if (panelNew) panelNew.classList.toggle('is-hidden', source !== 'new');
            var field = $('#coupon_source_field');
            if (field) field.value = source;
            updateDeliverySectionState();
        }

        $all('.coupon-source-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                setCouponSource(tab.getAttribute('data-source'));
            });
        });

        $all('.coupon-picker-input').forEach(function (input) {
            input.addEventListener('change', updateSelectedCoupon);
        });

        function currentDeliveryType() {
            var el = document.querySelector('input[name="delivery_type"]:checked');
            return el ? el.value : 'immediate_all';
        }

        function includesType(list, type) {
            return (list || []).indexOf(type) !== -1;
        }

        function toggleBlock(id, hidden) {
            var el = document.getElementById(id);
            if (el) el.classList.toggle('is-hidden', hidden);
        }

        function toggleDeliveryBlocks() {
            var type = currentDeliveryType();
            var personal = includesType(cfg.personalTypes, type);
            var segment = includesType(cfg.segmentTypes, type);
            var birthday = includesType(cfg.birthdayTypes, type);
            var newMember = includesType(cfg.newMemberTypes, type);
            var inactive = includesType(cfg.inactiveTypes, type);
            var scheduled = includesType(cfg.scheduledTypes, type);

            toggleBlock('personalBlock', !personal);
            toggleBlock('segmentBlock', !segment);
            toggleBlock('birthdayBlock', !birthday);
            toggleBlock('newMemberBlock', !newMember);
            toggleBlock('inactiveBlock', !inactive);
            toggleBlock('scheduledBlock', !scheduled);

            var submitLabel = $('#submitDeliveryLabel');
            if (submitLabel) {
                submitLabel.textContent = scheduled ? '予約する' : '配布する';
            }

            toggleSegmentMinAmount();
            updateTargetPreview();
        }

        function toggleSegmentMinAmount() {
            var cond = document.querySelector('input[name="segment_condition"]:checked');
            var block = $('#segmentMinAmountBlock');
            if (block) block.style.display = cond && cond.value === 'min_amount' ? 'block' : 'none';
        }

        $all('input[name="delivery_type"]').forEach(function (radio) {
            radio.addEventListener('change', toggleDeliveryBlocks);
        });
        $all('input[name="segment_condition"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                toggleSegmentMinAmount();
                updateTargetPreview();
            });
        });

        ['segment_period_days', 'segment_min_amount', 'birthday_month', 'new_member_days', 'inactive_days', 'scheduled_at'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', updateTargetPreview);
            if (el) el.addEventListener('input', updateTargetPreview);
        });

        var previewTimer;
        function updateTargetPreview() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(function () {
                var previewText = $('#targetPreviewText');
                if (!previewText) return;

                var type = currentDeliveryType();
                var couponIdEl = $('#coupon_id');
                var couponId = couponIdEl ? couponIdEl.value : '';
                if (!couponId || getCurrentSource() === 'new') {
                    previewText.textContent = 'クーポンを選択すると対象人数が表示されます';
                    return;
                }
                if (!cfg.previewUrl) {
                    previewText.textContent = '対象会員: —';
                    return;
                }

                var params = new URLSearchParams();
                params.set('delivery_type', type);
                selectedMembers.forEach(function (id) { params.append('target_member_ids[]', String(id)); });

                ['segment_period_days', 'segment_condition', 'segment_min_amount', 'birthday_month', 'new_member_days', 'inactive_days'].forEach(function (key) {
                    var el = document.querySelector('[name="' + key + '"]');
                    if (el && el.value) params.set(key, el.value);
                });

                fetch(cfg.previewUrl + '?' + params.toString())
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        previewText.textContent = '対象会員: 約 ' + (data.count || 0) + ' 人';
                    })
                    .catch(function () {
                        previewText.textContent = '対象会員: —';
                    });
            }, 350);
        }

        function renderSelectedMembers() {
            var container = $('#selectedMembers');
            var hidden = $('#targetMemberIdsHidden');
            if (!container || !hidden) return;
            container.innerHTML = '';
            hidden.innerHTML = '';
            selectedMembers.forEach(function (id) {
                var tag = document.createElement('span');
                tag.className = 'tag';
                tag.innerHTML = '#' + id + ' <button type="button" data-id="' + id + '" aria-label="削除">&times;</button>';
                container.appendChild(tag);
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'target_member_ids[]';
                input.value = String(id);
                hidden.appendChild(input);
            });
            container.querySelectorAll('button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = parseInt(btn.getAttribute('data-id'), 10);
                    selectedMembers = selectedMembers.filter(function (x) { return x !== id; });
                    renderSelectedMembers();
                    updateTargetPreview();
                });
            });
        }

        var searchInput = $('#memberSearch');
        var resultsDiv = $('#memberSearchResults');
        var searchTimer;

        if (searchInput && resultsDiv && cfg.membersUrl) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                var q = searchInput.value.trim();
                if (q.length < 1) {
                    resultsDiv.classList.add('is-hidden');
                    return;
                }
                searchTimer = setTimeout(function () {
                    fetch(cfg.membersUrl + '?q=' + encodeURIComponent(q))
                        .then(function (r) { return r.json(); })
                        .then(function (list) {
                            resultsDiv.innerHTML = '';
                            if (!list || !list.length) {
                                resultsDiv.innerHTML = '<div class="p-2 text-muted small">該当なし</div>';
                            } else {
                                list.forEach(function (m) {
                                    var btn = document.createElement('button');
                                    btn.type = 'button';
                                    btn.className = 'member-search-item';
                                    btn.textContent = (m.member_number || '') + ' ' + (m.name || '') + ' (ID:' + m.id + ')';
                                    btn.addEventListener('click', function () {
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

            document.addEventListener('click', function (e) {
                if (!resultsDiv.contains(e.target) && e.target !== searchInput) {
                    resultsDiv.classList.add('is-hidden');
                }
            });
        }

        var form = $('#bulkIssueForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (getCurrentSource() === 'new') {
                    e.preventDefault();
                    alert('先にクーポンを作成するか、「既存クーポンを使う」タブで選択してください');
                    return;
                }
                var couponIdEl = $('#coupon_id');
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
