@extends('admin.layout')
@section('title', '会員登録・プロフィール')
@section('page-title', '会員登録・プロフィール')

@section('content')
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>会員登録・プロフィール</span>
</nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-user-cog"></i> 会員登録・プロフィール</h1>
</div>

@include('admin.settings._section-tabs', ['tabsLabel' => '会員登録・プロフィール', 'sectionTabs' => ['profile-fields' => '登録・プロフィール項目', 'profile-code' => '会員番号']])
<section id="profile-fields">
<form action="{{ admin_route('admin.settings.profile.update') }}" method="POST">
    @csrf
    @php
        $memberRegistrationMode = $view->settings['member_registration_mode'] ?? \App\Enums\MemberRegistrationMode::OPTIONAL;
        $modeOptions = $view->memberRegistrationModeOptions ?? \App\Enums\MemberRegistrationMode::getValues();
        $customFieldMax = \App\Domain\Settings\ShopSettingKeys::PROFILE_CUSTOM_FIELD_MAX;
        $customFieldLabelMax = \App\Services\Smaregi\SmaregiCustomerCustomFieldLabelSyncService::LABEL_MAX;
        $inputTypeOptions = $view->customFieldInputTypeOptions ?? \App\Enums\ProfileCustomFieldInputType::getValues();
        $inputTypeDescriptions = \App\Enums\ProfileCustomFieldInputType::descriptions();
        $customFields = array_values($view->settings['custom_fields'] ?? []);
        $customFieldCount = count($customFields);
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
            <h2 class="card-title">プロフィール項目</h2>
        </div>
        <div class="card-body">
            <p class="profile-switch-legend">緑の<strong>オン</strong>＝使う、グレーの<strong>オフ</strong>＝使わない、です。「アプリに表示」をオンにすると会員のプロフィール画面に出ます。「入力必須」をオンにすると空欄のまま保存できません。</p>
            <table class="table">
                <thead>
                    <tr>
                        <th>項目</th>
                        <th class="profile-switch-cell">アプリに表示</th>
                        <th class="profile-switch-cell">入力必須</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($view->settings['smaregi_fields'] ?? [] as $key => $field)
                    @php
                        $isName = $key === 'name';
                        $isProfileImage = $key === 'profile_image';
                        $fieldLabel = $field['label'] ?? $key;
                    @endphp
                    <tr>
                        <td>
                            {{ $fieldLabel }}
                            @if($isName)
                                <div class="text-muted small">お名前は常に表示・必須です</div>
                            @elseif($isProfileImage)
                                <div class="text-muted small">オンにすると会員本人が画像を登録・変更・削除できます</div>
                            @endif
                        </td>
                        <td class="profile-switch-cell">
                            <input type="hidden" name="smaregi_fields[{{ $key }}][enabled]" value="{{ $isName ? '1' : '0' }}">
                            <label class="profile-switch profile-switch--small">
                                <input type="checkbox" name="smaregi_fields[{{ $key }}][enabled]" value="1" {{ ($field['enabled'] ?? true) ? 'checked' : '' }} {{ $isName ? 'disabled' : '' }} aria-label="{{ $fieldLabel }}をアプリに表示">
                                <span class="profile-switch-ui" aria-hidden="true">
                                    <span class="profile-switch-on">オン</span>
                                    <span class="profile-switch-off">オフ</span>
                                </span>
                            </label>
                        </td>
                        <td class="profile-switch-cell">
                            <input type="hidden" name="smaregi_fields[{{ $key }}][required]" value="{{ $isName ? '1' : '0' }}">
                            @if($isProfileImage)
                                <span class="text-muted small">—</span>
                            @else
                            <label class="profile-switch profile-switch--small">
                                <input type="checkbox" name="smaregi_fields[{{ $key }}][required]" value="1" {{ !empty($field['required']) ? 'checked' : '' }} {{ $isName ? 'disabled' : '' }} aria-label="{{ $fieldLabel }}を入力必須にする">
                                <span class="profile-switch-ui" aria-hidden="true">
                                    <span class="profile-switch-on">オン</span>
                                    <span class="profile-switch-off">オフ</span>
                                </span>
                            </label>
                            @endif
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
            <div class="custom-field-header-actions">
                <span id="customFieldCount" class="custom-field-count">{{ $customFieldCount }} / {{ $customFieldMax }} 件</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addCustomField"><i class="fas fa-plus"></i> 項目を追加</button>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">会員プロフィールに、お店独自の質問を追加できます。最大 {{ $customFieldMax }} 件まで追加できます。</p>
            <div id="customFieldsEmpty" class="empty-state custom-field-empty" @if($customFieldCount > 0) hidden @endif>
                <i class="fas fa-list"></i>
                <p>まだ追加項目はありません</p>
                <p class="empty-state-hint">「項目を追加」から作成してください。例: 配信を受け取る店舗、興味のあるジャンル</p>
            </div>
            <div id="customFieldsContainer" class="custom-field-list">
                @foreach($customFields as $i => $cf)
                @php
                    $inputType = $cf['input_type'] ?? \App\Enums\ProfileCustomFieldInputType::TEXT;
                    $needsValues = \App\Enums\ProfileCustomFieldInputType::needsValues($inputType);
                    $values = array_values(array_filter(array_map('trim', $cf['values'] ?? []), static fn ($value) => $value !== ''));
                    $isRequired = !empty($cf['required']);
                @endphp
                <div
                    class="custom-field-row"
                    data-index="{{ $i }}"
                    data-key="{{ $cf['key'] ?? '' }}"
                    data-label="{{ $cf['label'] ?? '' }}"
                    data-input-type="{{ $inputType }}"
                    data-required="{{ $isRequired ? '1' : '0' }}"
                    data-smaregi-no="{{ $cf['smaregi_no'] ?? '' }}"
                    data-values="{{ e(json_encode($values, JSON_UNESCAPED_UNICODE)) }}"
                >
                    <input type="hidden" name="custom_fields[{{ $i }}][key]" value="{{ $cf['key'] ?? '' }}">
                    <input type="hidden" name="custom_fields[{{ $i }}][label]" value="{{ $cf['label'] ?? '' }}">
                    <input type="hidden" name="custom_fields[{{ $i }}][input_type]" value="{{ $inputType }}">
                    <input type="hidden" name="custom_fields[{{ $i }}][required]" value="{{ $isRequired ? '1' : '0' }}">
                    <input type="hidden" name="custom_fields[{{ $i }}][description]" value="{{ $cf['description'] ?? '' }}">
                    <input type="hidden" name="custom_fields[{{ $i }}][smaregi_no]" value="{{ $cf['smaregi_no'] ?? '' }}">
                    <textarea name="custom_fields[{{ $i }}][values]" hidden aria-hidden="true">{{ implode("\n", $values) }}</textarea>
                    <div class="custom-field-body">
                        <div class="custom-field-main">
                            <div class="custom-field-title">
                                <strong class="custom-field-name">{{ $cf['label'] ?? ('項目'.($i + 1)) }}</strong>
                                <span class="custom-field-chip {{ $isRequired ? 'is-required' : 'is-optional' }}">{{ $isRequired ? '必須' : '任意' }}</span>
                                <span class="custom-field-chip custom-field-type">{{ $inputTypeOptions[$inputType] ?? $inputType }}</span>
                            </div>
                            @if($needsValues)
                                <div class="custom-field-values">
                                    @if($values !== [])
                                        @foreach($values as $value)
                                            <span class="custom-field-option">{{ $value }}</span>
                                        @endforeach
                                    @else
                                        <span class="custom-field-values-empty">選択肢がまだありません</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="custom-field-actions">
                            <button type="button" class="btn btn-sm btn-outline-secondary edit-custom-field">編集</button>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-custom-field">削除</button>
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
                    <label class="form-label" for="customFieldModalLabel">項目名</label>
                    <input type="text" class="form-control" id="customFieldModalLabel" maxlength="{{ $customFieldLabelMax }}" placeholder="例: 配信を受け取る店舗">
                    <small class="text-muted">プロフィール画面に表示される名前です（{{ $customFieldLabelMax }}文字まで）。</small>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label" for="customFieldModalInputType">入力タイプ</label>
                    <select class="form-control" id="customFieldModalInputType">
                        @foreach($inputTypeOptions as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1" id="customFieldModalInputTypeHint"></small>
                </div>
                <div class="form-group mb-3">
                    <label class="profile-switch profile-switch--required" for="customFieldModalRequired">
                        <input type="checkbox" id="customFieldModalRequired" value="1">
                        <span class="profile-switch-ui" aria-hidden="true">
                            <span class="profile-switch-on">オン</span>
                            <span class="profile-switch-off">オフ</span>
                        </span>
                        <span class="profile-switch-label">入力必須にする</span>
                    </label>
                    <small class="text-muted d-block mt-1">オンにすると、会員はこの項目を空欄のまま保存できません。</small>
                </div>
                <div class="form-group mb-0" id="customFieldModalValuesWrap">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label mb-0">選択肢</label>
                    </div>
                    <p class="text-muted small mb-2">会員が選ぶ候補です。1件ずつ追加してください。</p>
                    <div id="customFieldModalOptions" class="custom-field-option-list"></div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary btn-block" id="addCustomFieldOption"><i class="fas fa-plus"></i> 選択肢を追加</button>
                    </div>
                    <small class="text-muted d-block mt-2">Enterでも次の行を追加できます。</small>
                </div>
            </div>
            <div class="custom-field-modal__footer">
                <button type="button" class="btn btn-secondary" data-action="close-modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveCustomFieldModal"><i class="fas fa-check"></i> この内容で追加</button>
            </div>
        </div>
    </div>

    <style>
        .profile-switch-legend {
            margin: 0 0 16px;
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
        }
        .profile-switch-cell {
            width: 132px;
            text-align: center;
            vertical-align: middle;
        }
        .custom-field-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .custom-field-count {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
        }
        .custom-field-empty {
            padding: 32px 16px;
            border: 1px dashed var(--border);
            border-radius: 12px;
            background: var(--bg-secondary);
        }
        .custom-field-empty[hidden] {
            display: none !important;
        }
        .custom-field-list {
            display: grid;
            gap: 8px;
        }
        .custom-field-row {
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--bg-primary);
        }
        .custom-field-body {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .custom-field-main {
            min-width: 0;
            flex: 1;
        }
        .custom-field-title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        .custom-field-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .custom-field-chip {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }
        .custom-field-chip.is-required {
            background: var(--danger-bg);
            color: var(--danger-hover);
        }
        .custom-field-values {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        .custom-field-option {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 8px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            font-size: 12px;
            font-weight: 500;
        }
        .custom-field-values-empty {
            font-size: 12px;
            color: var(--text-tertiary);
        }
        .custom-field-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }
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
        .custom-field-row textarea {
            display: none !important;
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
        .profile-switch-ui {
            display: inline-flex;
            position: relative;
            width: 68px;
            height: 28px;
            border-radius: 999px;
            background: #64748b;
            border: 1px solid #64748b;
            transition: background-color .15s ease, border-color .15s ease;
            flex-shrink: 0;
        }
        .profile-switch-ui::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2);
            transition: transform .15s ease;
        }
        .profile-switch-on,
        .profile-switch-off {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            pointer-events: none;
        }
        .profile-switch-off {
            right: 8px;
            color: #fff;
        }
        .profile-switch-on {
            display: none;
            left: 8px;
            color: #fff;
        }
        .profile-switch:hover .profile-switch-ui {
            border-color: #475569;
        }
        .profile-switch input:checked + .profile-switch-ui {
            background: #16a34a;
            border-color: #16a34a;
        }
        .profile-switch input:checked + .profile-switch-ui::after {
            transform: translateX(40px);
        }
        .profile-switch input:checked + .profile-switch-ui .profile-switch-on {
            display: block;
        }
        .profile-switch input:checked + .profile-switch-ui .profile-switch-off {
            display: none;
        }
        .profile-switch input:disabled + .profile-switch-ui {
            opacity: .55;
            cursor: not-allowed;
        }
        .profile-switch--small .profile-switch-ui {
            width: 64px;
            height: 26px;
        }
        .profile-switch--small .profile-switch-ui::after {
            width: 18px;
            height: 18px;
            top: 3px;
            left: 3px;
        }
        .profile-switch--small input:checked + .profile-switch-ui::after {
            transform: translateX(38px);
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
        .profile-saving {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 24px 32px;
            border-radius: 12px;
            background: #fff;
            color: #172033;
            font-size: 14px;
            font-weight: 700;
        }
    </style>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
    </div>
</form>
</section>
<section id="profile-code"><form action="{{ admin_route('admin.settings.member-code.update') }}" method="POST">
    @csrf
    <div class="card"><div class="card-header"><h2 class="card-title">会員番号の発行ルール</h2></div><div class="card-body">
                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle"><i class="fas fa-id-card"></i> 会員番号</h4>
                        <p class="form-text text-muted">新規会員登録時に発行する会員番号の生成方法。スマレジ連携時は20文字以内で送信されます。</p>
                        <div class="ranks-settings-fields">
                            <div class="form-group">
                                <label for="member_code_type" class="form-label">生成方式</label>
                                <select name="member_code_type" id="member_code_type" class="form-control">
                                    <option value="random" {{ old('member_code_type', $memberCode->type ?? 'random') === 'random' ? 'selected' : '' }}>ランダム</option>
                                    <option value="sequential" {{ old('member_code_type', $memberCode->type ?? '') === 'sequential' ? 'selected' : '' }}>連番</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="member_code_format" class="form-label">形式</label>
                                <select name="member_code_format" id="member_code_format" class="form-control">
                                    <option value="numeric" {{ old('member_code_format', $memberCode->format ?? 'numeric') === 'numeric' ? 'selected' : '' }}>数字のみ</option>
                                    <option value="alphanumeric" {{ old('member_code_format', $memberCode->format ?? '') === 'alphanumeric' ? 'selected' : '' }}>英数字混在</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="member_code_length" class="form-label">桁数（プレフィックス除く）</label>
                                <input type="number" name="member_code_length" id="member_code_length" class="form-control" value="{{ old('member_code_length', $memberCode->length ?? 10) }}" min="1" max="20">
                            </div>
                            <div class="form-group">
                                <label for="member_code_prefix" class="form-label">プレフィックス（任意）</label>
                                <input type="text" name="member_code_prefix" id="member_code_prefix" class="form-control" value="{{ old('member_code_prefix', $memberCode->prefix ?? '') }}" maxlength="10" pattern="[A-Za-z0-9]*" placeholder="例: M">
                            </div>
                        </div>
                    </div>

    </div></div>
    @foreach(['member_code_type', 'member_code_format', 'member_code_length', 'member_code_prefix'] as $field)
        @error($field)<p class="text-danger" data-setting-error>{{ $message }}</p>@enderror
    @endforeach
    <div class="form-actions"><button class="btn btn-primary" type="submit">会員番号の発行ルールを保存</button></div>
</form></section>


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
    var emptyEl = document.getElementById('customFieldsEmpty');
    var countEl = document.getElementById('customFieldCount');
    var addBtn = document.getElementById('addCustomField');
    var modal = document.getElementById('customFieldModal');
    var modalTitle = document.getElementById('customFieldModalTitle');
    var modalLabel = document.getElementById('customFieldModalLabel');
    var modalInputType = document.getElementById('customFieldModalInputType');
    var modalInputTypeHint = document.getElementById('customFieldModalInputTypeHint');
    var modalRequired = document.getElementById('customFieldModalRequired');
    var modalValuesWrap = document.getElementById('customFieldModalValuesWrap');
    var modalOptions = document.getElementById('customFieldModalOptions');
    var modalAddOption = document.getElementById('addCustomFieldOption');
    var saveBtn = document.getElementById('saveCustomFieldModal');
    var maxFields = {{ (int) $customFieldMax }};
    var inputTypeOptions = @json($inputTypeOptions);
    var inputTypeDescriptions = @json($inputTypeDescriptions);
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

    function fieldCount() {
        return container ? container.querySelectorAll('.custom-field-row').length : 0;
    }

    function syncAddButton() {
        if (!addBtn || !container) return;
        var count = fieldCount();
        addBtn.disabled = count >= maxFields;
        addBtn.title = count >= maxFields ? ('追加項目は' + maxFields + '個までです') : '';
        if (countEl) {
            countEl.textContent = count + ' / ' + maxFields + ' 件';
        }
        if (emptyEl) {
            emptyEl.hidden = count > 0;
        }
    }

    function syncInputTypeHint() {
        if (!modalInputType || !modalInputTypeHint) return;
        modalInputTypeHint.textContent = inputTypeDescriptions[modalInputType.value] || '';
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
        syncInputTypeHint();
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
        if (saveBtn) {
            saveBtn.innerHTML = editingRow
                ? '<i class="fas fa-check"></i> この内容で更新'
                : '<i class="fas fa-check"></i> この内容で追加';
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
            return '<span class="custom-field-option">' + escapeHtml(value) + '</span>';
        }).join('');
    }

    function valuesHtml(inputType, values) {
        if (needsValuesTypes.indexOf(inputType) === -1) {
            return '';
        }
        var inner = values.length > 0
            ? buildValues(values)
            : '<span class="custom-field-values-empty">選択肢がまだありません</span>';
        return '<div class="custom-field-values">' + inner + '</div>';
    }

    function buildRow(index, data) {
        var inputTypeLabel = inputTypeOptions[data.input_type] || data.input_type || 'text';
        var values = Array.isArray(data.values) ? data.values : [];
        var valueText = values.join('\n');
        var requiredLabel = data.required ? '必須' : '任意';
        var requiredClass = data.required ? 'is-required' : 'is-optional';
        return '' +
            '<div class="custom-field-row" data-index="' + index + '" data-key="' + escapeHtml(data.key) + '" data-label="' + escapeHtml(data.label) + '" data-input-type="' + escapeHtml(data.input_type) + '" data-required="' + (data.required ? '1' : '0') + '" data-smaregi-no="' + escapeHtml(data.smaregi_no) + '" data-description="' + escapeHtml(data.description) + '" data-values=\'' + escapeHtml(JSON.stringify(values)) + '\'>' +
                '<input type="hidden" name="custom_fields[' + index + '][key]" value="' + escapeHtml(data.key) + '">' +
                '<input type="hidden" name="custom_fields[' + index + '][label]" value="' + escapeHtml(data.label) + '">' +
                '<input type="hidden" name="custom_fields[' + index + '][input_type]" value="' + escapeHtml(data.input_type) + '">' +
                '<input type="hidden" name="custom_fields[' + index + '][required]" value="' + (data.required ? '1' : '0') + '">' +
                '<input type="hidden" name="custom_fields[' + index + '][description]" value="' + escapeHtml(data.description) + '">' +
                '<input type="hidden" name="custom_fields[' + index + '][smaregi_no]" value="' + escapeHtml(data.smaregi_no) + '">' +
                '<textarea name="custom_fields[' + index + '][values]" hidden aria-hidden="true">' + escapeHtml(valueText) + '</textarea>' +
                '<div class="custom-field-body">' +
                    '<div class="custom-field-main">' +
                        '<div class="custom-field-title">' +
                            '<strong class="custom-field-name">' + escapeHtml(data.label) + '</strong>' +
                            '<span class="custom-field-chip ' + requiredClass + '">' + requiredLabel + '</span>' +
                            '<span class="custom-field-chip custom-field-type">' + escapeHtml(inputTypeLabel) + '</span>' +
                        '</div>' +
                        valuesHtml(data.input_type, values) +
                    '</div>' +
                    '<div class="custom-field-actions">' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary edit-custom-field">編集</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger remove-custom-field">削除</button>' +
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
        var wrap = document.createElement('div');
        if (editingRow) {
            wrap.innerHTML = buildRow(Number(editingRow.getAttribute('data-index') || '0'), data);
            editingRow.replaceWith(wrap.firstElementChild);
        } else {
            wrap.innerHTML = buildRow(nextIndex, data);
            container.appendChild(wrap.firstElementChild);
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
            if (container && fieldCount() >= maxFields) {
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
