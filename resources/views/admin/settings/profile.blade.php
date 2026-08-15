@extends('admin.layout')
@section('title', 'プロフィール設定')
@section('page-title', 'プロフィール設定')

@section('content')
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>プロフィール設定</span>
</nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-user-cog"></i> プロフィール設定</h1>
</div>
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif

<form action="{{ admin_route('admin.settings.profile.update') }}" method="POST">
    @csrf
    @php
        $memberRegistrationMode = $view->settings['member_registration_mode'] ?? \App\Enums\MemberRegistrationMode::OPTIONAL;
        $modeOptions = $view->memberRegistrationModeOptions ?? \App\Enums\MemberRegistrationMode::getValues();
        $customFieldMax = \App\Domain\Settings\ShopSettingKeys::PROFILE_CUSTOM_FIELD_MAX;
        $inputTypeOptions = $view->customFieldInputTypeOptions ?? \App\Enums\ProfileCustomFieldInputType::getValues();
    @endphp
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">未会員時の扱い</h2>
        </div>
        <div class="card-body">
            <div class="form-group mb-0">
                <label for="member_registration_mode" class="form-label">未会員でアプリを開いたとき</label>
                <select name="member_registration_mode" id="member_registration_mode" class="form-control" required>
                    @foreach($modeOptions as $value => $label)
                        <option value="{{ $value }}" {{ $memberRegistrationMode === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">スマレジ連携用項目</h2>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>項目</th>
                        <th>表示する</th>
                        <th>必須</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($view->settings['smaregi_fields'] ?? [] as $key => $field)
                    @php
                        $isName = $key === 'name';
                        $isLinked = \App\Domain\Settings\ShopSettingKeys::isSmaregiLinkedProfileField($key);
                    @endphp
                    <tr>
                        <td>
                            {{ $field['label'] ?? $key }}
                            @if($isLinked)
                                <i class="fas fa-link text-muted" title="スマレジ会員情報と連携"></i>
                            @endif
                        </td>
                        <td>
                            <input type="hidden" name="smaregi_fields[{{ $key }}][enabled]" value="{{ $isName ? '1' : '0' }}">
                            <label class="profile-switch profile-switch--small">
                                <input type="checkbox" name="smaregi_fields[{{ $key }}][enabled]" value="1" {{ ($field['enabled'] ?? true) ? 'checked' : '' }} {{ $isName ? 'disabled' : '' }}>
                                <span aria-hidden="true"></span>
                            </label>
                        </td>
                        <td>
                            <input type="hidden" name="smaregi_fields[{{ $key }}][required]" value="{{ $isName ? '1' : '0' }}">
                            <label class="profile-switch profile-switch--small">
                                <input type="checkbox" name="smaregi_fields[{{ $key }}][required]" value="1" {{ !empty($field['required']) ? 'checked' : '' }} {{ $isName ? 'disabled' : '' }}>
                                <span aria-hidden="true"></span>
                            </label>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header card-header-with-action">
            <h2 class="card-title">任意の追加項目</h2>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addCustomField"><i class="fas fa-plus"></i> 項目を追加</button>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">保存するとスマレジの会員自由項目として登録・更新され、会員の入力値が連携されます（最大 {{ $customFieldMax }} 件）。</p>
            <div id="customFieldsContainer" class="custom-field-list">
                @foreach($view->settings['custom_fields'] ?? [] as $i => $cf)
                @php
                    $inputType = $cf['input_type'] ?? \App\Enums\ProfileCustomFieldInputType::TEXT;
                    $needsValues = \App\Enums\ProfileCustomFieldInputType::needsValues($inputType);
                    $values = array_values(array_filter(array_map('trim', $cf['values'] ?? []), static fn ($value) => $value !== ''));
                @endphp
                <div
                    class="custom-field-row card mb-3"
                    data-index="{{ $i }}"
                    data-key="{{ $cf['key'] ?? '' }}"
                    data-label="{{ $cf['label'] ?? '' }}"
                    data-input-type="{{ $inputType }}"
                    data-required="{{ !empty($cf['required']) ? '1' : '0' }}"
                    data-smaregi-no="{{ $cf['smaregi_no'] ?? '' }}"
                    data-values="{{ e(json_encode($values, JSON_UNESCAPED_UNICODE)) }}"
                >
                    <div class="card-body">
                        <input type="hidden" name="custom_fields[{{ $i }}][key]" value="{{ $cf['key'] ?? '' }}">
                        <input type="hidden" name="custom_fields[{{ $i }}][label]" value="{{ $cf['label'] ?? '' }}">
                        <input type="hidden" name="custom_fields[{{ $i }}][input_type]" value="{{ $inputType }}">
                        <input type="hidden" name="custom_fields[{{ $i }}][required]" value="{{ !empty($cf['required']) ? '1' : '0' }}">
                        <input type="hidden" name="custom_fields[{{ $i }}][description]" value="{{ $cf['description'] ?? '' }}">
                        <input type="hidden" name="custom_fields[{{ $i }}][smaregi_no]" value="{{ $cf['smaregi_no'] ?? '' }}">
                        <textarea name="custom_fields[{{ $i }}][values]" class="d-none" aria-hidden="true">{{ implode("\n", $values) }}</textarea>
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <strong class="custom-field-name">{{ $cf['label'] ?? ('項目'.($i + 1)) }}</strong>
                                    <span class="badge badge-light">{{ $inputTypeOptions[$inputType] ?? $inputType }}</span>
                                    <span class="badge {{ !empty($cf['required']) ? 'badge-danger' : 'badge-secondary' }}">{{ !empty($cf['required']) ? '必須' : '任意' }}</span>
                                </div>
                                <div class="custom-field-values">
                                    @if($needsValues && !empty($values))
                                        @foreach($values as $value)
                                            <span class="badge badge-light">{{ $value }}</span>
                                        @endforeach
                                    @elseif($needsValues)
                                        <span class="text-muted small">選択肢が未設定です</span>
                                    @else
                                        <span class="text-muted small">テキスト入力</span>
                                    @endif
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary edit-custom-field"><i class="fas fa-pen"></i> 編集</button>
                                <button type="button" class="btn btn-outline-danger remove-custom-field"><i class="fas fa-times"></i> 削除</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div id="customFieldModal" class="custom-field-modal" hidden>
        <div class="custom-field-modal__backdrop" data-action="close-modal"></div>
        <div class="custom-field-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="customFieldModalTitle">
            <div class="custom-field-modal__header">
                <h3 id="customFieldModalTitle">追加項目を編集</h3>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="close-modal" aria-label="閉じる"><i class="fas fa-times"></i></button>
            </div>
            <div class="custom-field-modal__body">
                <div class="form-group mb-3">
                    <label class="form-label">項目名</label>
                    <input type="text" class="form-control" id="customFieldModalLabel" maxlength="100" placeholder="例: 配信を受け取る店舗">
                    <small class="text-muted">プロフィール画面に表示される名前です。</small>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">入力タイプ</label>
                    <select class="form-control" id="customFieldModalInputType">
                        @foreach($inputTypeOptions as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="profile-switch profile-switch--required" for="customFieldModalRequired">
                        <input type="checkbox" id="customFieldModalRequired" value="1">
                        <span aria-hidden="true"></span>
                        <span class="profile-switch-label">必須にする</span>
                    </label>
                </div>
                <div class="form-group mb-0" id="customFieldModalValuesWrap">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label mb-0">選択肢</label>
                    </div>
                    <div id="customFieldModalOptions" class="custom-field-option-list"></div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary btn-block" id="addCustomFieldOption"><i class="fas fa-plus"></i> 選択肢を追加</button>
                    </div>
                    <small class="text-muted d-block mt-2">選択肢は1件ずつ追加してください。Enterでも次の行を追加できます。</small>
                </div>
            </div>
            <div class="custom-field-modal__footer">
                <button type="button" class="btn btn-secondary" data-action="close-modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveCustomFieldModal"><i class="fas fa-check"></i> 保存</button>
            </div>
        </div>
    </div>

    <style>
        .custom-field-modal {
            position: fixed;
            inset: 0;
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .custom-field-modal[hidden] {
            display: none !important;
        }
        .custom-field-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.58);
        }
        .custom-field-modal__dialog {
            position: relative;
            width: min(720px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.28);
        }
        .custom-field-modal__header,
        .custom-field-modal__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .custom-field-modal__footer {
            border-top: 1px solid #e5e7eb;
            border-bottom: 0;
        }
        .custom-field-modal__body {
            padding: 20px;
        }
        .custom-field-list .custom-field-row:last-child {
            margin-bottom: 0 !important;
        }
        .custom-field-option-list {
            display: grid;
            gap: 10px;
        }
        .custom-field-option-row {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }
        .custom-field-option-order {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }
        .custom-field-option-row .form-control {
            min-width: 0;
        }
        .profile-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        .profile-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .profile-switch span:first-of-type {
            display: inline-flex;
            position: relative;
            width: 48px;
            height: 28px;
            border-radius: 999px;
            background: #cbd5e1;
            border: 1px solid #cbd5e1;
            transition: background-color .15s ease, border-color .15s ease;
            flex-shrink: 0;
        }
        .profile-switch span:first-of-type::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            transition: transform .15s ease;
        }
        .profile-switch:hover span:first-of-type {
            border-color: #94a3b8;
        }
        .profile-switch input:checked + span:first-of-type {
            background: #16a34a;
            border-color: #16a34a;
        }
        .profile-switch input:checked + span:first-of-type::after {
            transform: translateX(20px);
        }
        .profile-switch input:disabled + span:first-of-type {
            opacity: .55;
            cursor: not-allowed;
        }
        .profile-switch--small span:first-of-type {
            width: 44px;
            height: 26px;
        }
        .profile-switch--small span:first-of-type::after {
            width: 18px;
            height: 18px;
            top: 3px;
            left: 3px;
        }
        .profile-switch--small input:checked + span:first-of-type::after {
            transform: translateX(18px);
        }
        .profile-switch--required {
            min-width: 44px;
            gap: 10px;
        }
        .profile-switch-label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }
    </style>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">基本設定へ</a>
    </div>
</form>

@php
    $needsValuesTypes = [
        \App\Enums\ProfileCustomFieldInputType::RADIO,
        \App\Enums\ProfileCustomFieldInputType::SELECTBOX,
        \App\Enums\ProfileCustomFieldInputType::CHECKBOX,
    ];
    $valuesMax = \App\Enums\ProfileCustomFieldInputType::VALUES_MAX;
@endphp
<script>
(function() {
    var container = document.getElementById('customFieldsContainer');
    var addBtn = document.getElementById('addCustomField');
    var modal = document.getElementById('customFieldModal');
    var modalTitle = document.getElementById('customFieldModalTitle');
    var modalLabel = document.getElementById('customFieldModalLabel');
    var modalInputType = document.getElementById('customFieldModalInputType');
    var modalRequired = document.getElementById('customFieldModalRequired');
    var modalValuesWrap = document.getElementById('customFieldModalValuesWrap');
    var modalOptions = document.getElementById('customFieldModalOptions');
    var modalAddOption = document.getElementById('addCustomFieldOption');
    var saveBtn = document.getElementById('saveCustomFieldModal');
    var maxFields = {{ (int) $customFieldMax }};
    var inputTypeOptions = @json($inputTypeOptions);
    var needsValuesTypes = @json($needsValuesTypes);
    var valuesMax = {{ (int) $valuesMax }};
    var editingRow = null;
    var nextIndex = 0;

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
        });
    }

    function slugifyLabel(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
    }

    function syncAddButton() {
        if (!addBtn || !container) return;
        var count = container.querySelectorAll('.custom-field-row').length;
        addBtn.disabled = count >= maxFields;
        addBtn.title = count >= maxFields ? ('追加項目は' + maxFields + '個までです') : '';
    }

    function syncValuesVisibility() {
        if (!modalInputType || !modalValuesWrap || !modalOptions) return;
        var shouldShow = needsValuesTypes.indexOf(modalInputType.value) !== -1;
        modalValuesWrap.style.display = shouldShow ? '' : 'none';
        if (!shouldShow) {
            modalOptions.innerHTML = '';
        } else if (modalOptions.querySelectorAll('.custom-field-option-row').length === 0) {
            appendOptionRow('');
        }
    }

    function getOptionRowCount() {
        return modalOptions ? modalOptions.querySelectorAll('.custom-field-option-row').length : 0;
    }

    function appendOptionRow(value) {
        if (!modalOptions) return;
        var index = getOptionRowCount() + 1;
        var row = document.createElement('div');
        row.className = 'custom-field-option-row';
        row.innerHTML = '' +
            '<span class="custom-field-option-order">' + index + '</span>' +
            '<input type="text" class="form-control" value="' + escapeHtml(value || '') + '" placeholder="選択肢' + index + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger custom-field-option-remove"><i class="fas fa-times"></i></button>';
        modalOptions.appendChild(row);
    }

    function syncOptionButtons() {
        if (!modalOptions) return;
        var rows = modalOptions.querySelectorAll('.custom-field-option-row');
        rows.forEach(function(row) {
            var order = row.querySelector('.custom-field-option-order');
            if (order) {
                order.textContent = String(Array.prototype.indexOf.call(rows, row) + 1);
            }
            var removeBtn = row.querySelector('.custom-field-option-remove');
            if (removeBtn) {
                removeBtn.disabled = rows.length === 1;
            }
        });
        if (modalAddOption) {
            modalAddOption.disabled = rows.length >= valuesMax;
        }
    }

    function readOptionValues() {
        if (!modalOptions) return [];
        return Array.prototype.slice.call(modalOptions.querySelectorAll('input[type="text"]'))
            .map(function(input) { return String(input.value || '').trim(); })
            .filter(function(value) { return value !== ''; })
            .slice(0, valuesMax);
    }

    function renderOptionRows(values) {
        if (!modalOptions) return;
        modalOptions.innerHTML = '';
        var list = Array.isArray(values) ? values : [];
        if (list.length === 0) {
            appendOptionRow('');
        } else {
            list.slice(0, valuesMax).forEach(function(value) {
                appendOptionRow(String(value));
            });
        }
        syncOptionButtons();
    }

    function openModal(row) {
        editingRow = row || null;
        if (modalTitle) {
            modalTitle.textContent = editingRow ? '追加項目を編集' : '追加項目を追加';
        }
        if (editingRow) {
            modalLabel.value = editingRow.dataset.label || '';
            modalInputType.value = editingRow.dataset.inputType || 'text';
            modalRequired.checked = editingRow.dataset.required === '1';
            try {
                var values = JSON.parse(editingRow.dataset.values || '[]');
                renderOptionRows(Array.isArray(values) ? values : []);
            } catch (error) {
                renderOptionRows([]);
            }
        } else {
            modalLabel.value = '';
            modalInputType.value = 'text';
            modalRequired.checked = false;
            renderOptionRows([]);
        }
        syncValuesVisibility();
        modal.hidden = false;
        modal.classList.add('is-open');
        window.setTimeout(function() {
            modalLabel.focus();
        }, 0);
    }

    function closeModal() {
        modal.hidden = true;
        modal.classList.remove('is-open');
        editingRow = null;
    }

    function buildValues(values) {
        return values.map(function(value) {
            return '<span class="badge badge-light">' + escapeHtml(value) + '</span>';
        }).join('');
    }

    function buildRow(index, data) {
        var inputTypeLabel = inputTypeOptions[data.input_type] || data.input_type || 'text';
        var values = Array.isArray(data.values) ? data.values : [];
        var valueText = values.join('\n');
        var valueChips = '';
        if (needsValuesTypes.indexOf(data.input_type) !== -1) {
            valueChips = values.length > 0 ? buildValues(values) : '<span class="text-muted small">選択肢が未設定です</span>';
        } else {
            valueChips = '<span class="text-muted small">テキスト入力</span>';
        }
        return '' +
            '<div class="custom-field-row card mb-3" data-index="' + index + '" data-key="' + escapeHtml(data.key) + '" data-label="' + escapeHtml(data.label) + '" data-input-type="' + escapeHtml(data.input_type) + '" data-required="' + (data.required ? '1' : '0') + '" data-smaregi-no="' + escapeHtml(data.smaregi_no) + '" data-description="' + escapeHtml(data.description) + '" data-values=\'' + escapeHtml(JSON.stringify(values)) + '\'>' +
                '<div class="card-body">' +
                    '<input type="hidden" name="custom_fields[' + index + '][key]" value="' + escapeHtml(data.key) + '">' +
                    '<input type="hidden" name="custom_fields[' + index + '][label]" value="' + escapeHtml(data.label) + '">' +
                    '<input type="hidden" name="custom_fields[' + index + '][input_type]" value="' + escapeHtml(data.input_type) + '">' +
                    '<input type="hidden" name="custom_fields[' + index + '][required]" value="' + (data.required ? '1' : '0') + '">' +
                    '<input type="hidden" name="custom_fields[' + index + '][description]" value="' + escapeHtml(data.description) + '">' +
                    '<input type="hidden" name="custom_fields[' + index + '][smaregi_no]" value="' + escapeHtml(data.smaregi_no) + '">' +
                    '<textarea name="custom_fields[' + index + '][values]" class="d-none" aria-hidden="true">' + escapeHtml(valueText) + '</textarea>' +
                    '<div class="d-flex align-items-start justify-content-between gap-3">' +
                        '<div class="flex-grow-1">' +
                            '<div class="d-flex flex-wrap align-items-center gap-2 mb-2">' +
                                '<strong class="custom-field-name">' + escapeHtml(data.label) + '</strong>' +
                                '<span class="badge badge-light">' + escapeHtml(inputTypeLabel) + '</span>' +
                            '</div>' +
                            '<div class="custom-field-values">' + valueChips + '</div>' +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-2">' +
                            '<label class="profile-switch profile-switch--small">' +
                                '<input type="checkbox" class="required-toggle" data-required-toggle' + (data.required ? ' checked' : '') + '>' +
                                '<span aria-hidden="true"></span>' +
                            '</label>' +
                            '<div class="btn-group btn-group-sm">' +
                                '<button type="button" class="btn btn-outline-secondary edit-custom-field"><i class="fas fa-pen"></i> 編集</button>' +
                                '<button type="button" class="btn btn-outline-danger remove-custom-field"><i class="fas fa-times"></i> 削除</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function collectRowData() {
        var label = String(modalLabel.value || '').trim();
        var inputType = String(modalInputType.value || 'text');
        var required = !!modalRequired.checked;
        var description = '';
        var values = needsValuesTypes.indexOf(inputType) !== -1 ? readOptionValues() : [];
        var key = editingRow ? String(editingRow.dataset.key || '') : '';
        if (key === '') {
            key = slugifyLabel(label);
        }
        if (key === '') {
            key = 'custom_' + (nextIndex + 1);
        }
        return {
            key: key,
            label: label,
            input_type: inputType,
            required: required,
            description: description,
            values: values,
            smaregi_no: editingRow ? String(editingRow.dataset.smaregiNo || '') : '',
        };
    }

    function saveModal() {
        if (!container || modalLabel.value.trim() === '') {
            modalLabel.focus();
            return;
        }
        var data = collectRowData();
        if (editingRow) {
            editingRow.dataset.key = data.key;
            editingRow.dataset.label = data.label;
            editingRow.dataset.inputType = data.input_type;
            editingRow.dataset.required = data.required ? '1' : '0';
            editingRow.dataset.description = data.description;
            editingRow.dataset.smaregiNo = data.smaregi_no;
            editingRow.dataset.values = JSON.stringify(data.values);
            var hiddenKey = editingRow.querySelector('input[name$="[key]"]');
            var hiddenLabel = editingRow.querySelector('input[name$="[label]"]');
            var hiddenInputType = editingRow.querySelector('input[name$="[input_type]"]');
            var hiddenRequired = editingRow.querySelector('input[name$="[required]"]');
            var hiddenDescription = editingRow.querySelector('input[name$="[description]"]');
            var hiddenSmaregiNo = editingRow.querySelector('input[name$="[smaregi_no]"]');
            var hiddenValues = editingRow.querySelector('textarea[name$="[values]"]');
            if (hiddenKey) hiddenKey.value = data.key;
            if (hiddenLabel) hiddenLabel.value = data.label;
            if (hiddenInputType) hiddenInputType.value = data.input_type;
            if (hiddenRequired) hiddenRequired.value = data.required ? '1' : '0';
            if (hiddenDescription) hiddenDescription.value = data.description;
            if (hiddenSmaregiNo) hiddenSmaregiNo.value = data.smaregi_no;
            if (hiddenValues) hiddenValues.value = data.values.join('\n');
            var nameEl = editingRow.querySelector('.custom-field-name');
            var valuesEl = editingRow.querySelector('.custom-field-values');
            var requiredToggle = editingRow.querySelector('.required-toggle');
            if (nameEl) nameEl.textContent = data.label;
            if (valuesEl) {
                valuesEl.innerHTML = needsValuesTypes.indexOf(data.input_type) !== -1
                    ? (data.values.length > 0 ? buildValues(data.values) : '<span class="text-muted small">選択肢が未設定です</span>')
                    : '<span class="text-muted small">テキスト入力</span>';
            }
            if (requiredToggle instanceof HTMLInputElement) {
                requiredToggle.checked = data.required;
            }
        } else {
            var nextIndexCandidate = nextIndex;
            var row = document.createElement('div');
            row.innerHTML = buildRow(nextIndexCandidate, data);
            container.appendChild(row.firstElementChild);
            nextIndex += 1;
        }
        syncAddButton();
        closeModal();
    }

    if (container) {
        container.querySelectorAll('.custom-field-row').forEach(function(row) {
            var index = Number(row.getAttribute('data-index') || '0');
            if (!Number.isNaN(index) && index >= nextIndex) {
                nextIndex = index + 1;
            }
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            if (container && container.querySelectorAll('.custom-field-row').length >= maxFields) {
                syncAddButton();
                return;
            }
            openModal(null);
        });
    }

    if (container) {
        container.addEventListener('click', function(event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            var row = target.closest('.custom-field-row');
            if (!(row instanceof HTMLElement)) {
                return;
            }
            if (target.closest('.remove-custom-field')) {
                row.remove();
                syncAddButton();
                return;
            }
            if (target.closest('.edit-custom-field')) {
                openModal(row);
            }
        });
        container.addEventListener('change', function(event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            if (!target.closest('.required-toggle')) {
                return;
            }
            var row = target.closest('.custom-field-row');
            if (!(row instanceof HTMLElement)) {
                return;
            }
            var requiredToggle = row.querySelector('.required-toggle');
            var hiddenRequired = row.querySelector('input[name$="[required]"]');
            if (requiredToggle instanceof HTMLInputElement && hiddenRequired instanceof HTMLInputElement) {
                hiddenRequired.value = requiredToggle.checked ? '1' : '0';
                row.dataset.required = requiredToggle.checked ? '1' : '0';
            }
        });
    }

    if (modal) {
        modal.addEventListener('click', function(event) {
            var target = event.target;
            if (target instanceof HTMLElement && target.dataset.action === 'close-modal') {
                closeModal();
            }
        });
    }

    if (modalInputType) {
        modalInputType.addEventListener('change', syncValuesVisibility);
    }

    if (modalAddOption) {
        modalAddOption.addEventListener('click', function() {
            if (!modalOptions) return;
            if (modalOptions.querySelectorAll('.custom-field-option-row').length >= valuesMax) {
                syncOptionButtons();
                return;
            }
            appendOptionRow('');
            syncOptionButtons();
            var inputs = modalOptions.querySelectorAll('input[type="text"]');
            if (inputs.length > 0) {
                inputs[inputs.length - 1].focus();
            }
        });
    }

    if (modalOptions) {
        modalOptions.addEventListener('click', function(event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            if (target.closest('.custom-field-option-remove')) {
                var row = target.closest('.custom-field-option-row');
                if (row) {
                    row.remove();
                    if (modalOptions.querySelectorAll('.custom-field-option-row').length === 0) {
                        appendOptionRow('');
                    }
                    syncOptionButtons();
                }
            }
        });
        modalOptions.addEventListener('keydown', function(event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            if (event.key !== 'Enter' || !target.matches('input[type="text"]')) {
                return;
            }
            event.preventDefault();
            if (modalOptions.querySelectorAll('.custom-field-option-row').length >= valuesMax) {
                syncOptionButtons();
                return;
            }
            appendOptionRow('');
            syncOptionButtons();
            var inputs = modalOptions.querySelectorAll('input[type="text"]');
            if (inputs.length > 0) {
                inputs[inputs.length - 1].focus();
            }
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', saveModal);
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });

    syncValuesVisibility();
    syncAddButton();
})();
</script>
@endsection
