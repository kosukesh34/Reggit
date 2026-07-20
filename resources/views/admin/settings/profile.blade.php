@extends('admin.layout')
@section('title', 'プロフィール設定')
@section('page-title', 'プロフィール設定')

@section('content')
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>設定</span>
    <span class="breadcrumb-sep">/</span>
    <span>プロフィール設定</span>
</nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-user-cog"></i> プロフィール設定</h1>
    <p class="page-header-desc">会員が登録・編集できる項目を設定します。スマレジと同じ内容を登録できる項目の表示ON/OFFと必須・任意を指定し、任意の追加項目も登録できます。</p>
</div>
@include('admin.settings._context', ['pageKey' => 'profile'])
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif

<form action="{{ admin_route('admin.settings.profile.update') }}" method="POST">
    @csrf
    @php
        $memberRegistrationMode = $settings['member_registration_mode'] ?? \App\Enums\MemberRegistrationMode::OPTIONAL;
        $modeOptions = $memberRegistrationModeOptions ?? \App\Enums\MemberRegistrationMode::getValues();
        $customFieldMax = \App\Models\ShopSetting::PROFILE_CUSTOM_FIELD_MAX;
    @endphp
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">未会員時の扱い</h2>
            <p class="card-desc text-muted small mb-0">LIFF アプリで、会員登録していないユーザーへの案内方法を選びます。</p>
        </div>
        <div class="card-body">
            @foreach($modeOptions as $value => $label)
            <label class="radio-label d-block mb-2">
                <input type="radio" name="member_registration_mode" value="{{ $value }}" {{ $memberRegistrationMode === $value ? 'checked' : '' }} required>
                <span>{{ $label }}</span>
            </label>
            @endforeach
            <ul class="text-muted small mb-0 mt-2 pl-3">
                <li>そのまま使える … 未会員のままアプリを利用できます</li>
                <li>登録を促す … 起動時に会員登録フォームを表示します（キャンセル可）</li>
                <li>会員登録を必須にする … 登録画面を表示し、登録するまでアプリを使えません</li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">スマレジ連携用項目（表示ON/OFF・必須/任意）</h2>
            <p class="card-desc text-muted small mb-0">
                会員アプリのプロフィールで表示する項目と、必須か任意かを設定します。
                <i class="fas fa-link"></i> マークが付いている項目はスマレジ会員情報と連携します。
                都道府県・市区町村・番地は住所入力の補助で、スマレジへは「住所」にまとめて送ります。
            </p>
            <p class="card-desc text-muted small mb-0 mt-1">※ スマレジ POS 側の「会員必須項目」設定とは独立です。POS 側は基本的に任意入力にしてください。</p>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>項目</th>
                        <th>表示する</th>
                        <th>必須 / 任意</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($settings['smaregi_fields'] ?? [] as $key => $field)
                    @php
                        $isName = $key === 'name';
                        $isLinked = \App\Models\ShopSetting::isSmaregiLinkedProfileField($key);
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
                            <label class="checkbox-label mb-0">
                                <input type="checkbox" name="smaregi_fields[{{ $key }}][enabled]" value="1" {{ ($field['enabled'] ?? true) ? 'checked' : '' }} {{ $isName ? 'disabled' : '' }}>
                                <span>表示する</span>
                            </label>
                            @if($isName)
                                <div class="text-muted small">氏名はスマレジ登録に必要なため常に表示</div>
                            @endif
                        </td>
                        <td>
                            <label class="radio-label-inline">
                                <input type="radio" name="smaregi_fields[{{ $key }}][required]" value="1" {{ ($field['required'] ?? false) ? 'checked' : '' }} {{ $isName ? 'disabled' : '' }}> 必須
                            </label>
                            <label class="radio-label-inline">
                                <input type="radio" name="smaregi_fields[{{ $key }}][required]" value="0" {{ !($field['required'] ?? false) ? 'checked' : '' }} {{ $isName ? 'disabled' : '' }}> 任意
                            </label>
                            @if($isName)
                                <input type="hidden" name="smaregi_fields[{{ $key }}][required]" value="1">
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
            <button type="button" class="btn btn-sm btn-outline-primary" id="addCustomField"><i class="fas fa-plus"></i> 項目を追加</button>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">会員プロフィールに独自の入力項目を追加できます（最大 {{ $customFieldMax }} 件）。キーは半角英数字とアンダースコアのみ（例: company_name）。追加項目はアプリ内のみで、スマレジには送信されません。</p>
            <div id="customFieldsContainer">
                @foreach($settings['custom_fields'] ?? [] as $i => $cf)
                <div class="custom-field-row form-row align-items-center mb-2">
                    <div class="col-md-3">
                        <input type="text" name="custom_fields[{{ $i }}][key]" class="form-control form-control-sm" value="{{ $cf['key'] ?? '' }}" placeholder="key_name" pattern="[a-z0-9_]+" title="半角英小文字・数字・アンダースコア">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="custom_fields[{{ $i }}][label]" class="form-control form-control-sm" value="{{ $cf['label'] ?? '' }}" placeholder="表示名（例: 会社名）">
                    </div>
                    <div class="col-md-2">
                        <label class="checkbox-label mb-0">
                            <input type="hidden" name="custom_fields[{{ $i }}][required]" value="0">
                            <input type="checkbox" name="custom_fields[{{ $i }}][required]" value="1" {{ ($cf['required'] ?? false) ? 'checked' : '' }}> 必須
                        </label>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-custom-field"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">基本設定へ</a>
    </div>
</form>

<script>
(function() {
    var container = document.getElementById('customFieldsContainer');
    var addBtn = document.getElementById('addCustomField');
    var index = (container && container.querySelectorAll('.custom-field-row').length) || 0;
    var maxFields = {{ (int) $customFieldMax }};

    function syncAddButton() {
        if (!addBtn || !container) return;
        var count = container.querySelectorAll('.custom-field-row').length;
        addBtn.disabled = count >= maxFields;
        addBtn.title = count >= maxFields ? ('追加項目は' + maxFields + '個までです') : '';
    }

    function addRow() {
        if (container.querySelectorAll('.custom-field-row').length >= maxFields) {
            syncAddButton();
            return;
        }
        var row = document.createElement('div');
        row.className = 'custom-field-row form-row align-items-center mb-2';
        row.innerHTML =
            '<div class="col-md-3"><input type="text" name="custom_fields[' + index + '][key]" class="form-control form-control-sm" placeholder="key_name" pattern="[a-z0-9_]+"></div>' +
            '<div class="col-md-4"><input type="text" name="custom_fields[' + index + '][label]" class="form-control form-control-sm" placeholder="表示名"></div>' +
            '<div class="col-md-2"><label class="checkbox-label mb-0"><input type="hidden" name="custom_fields[' + index + '][required]" value="0"><input type="checkbox" name="custom_fields[' + index + '][required]" value="1"> 必須</label></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-custom-field"><i class="fas fa-times"></i></button></div>';
        container.appendChild(row);
        row.querySelector('.remove-custom-field').addEventListener('click', function() { row.remove(); syncAddButton(); });
        index++;
        syncAddButton();
    }
    addBtn.addEventListener('click', addRow);
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-custom-field')) {
            e.target.closest('.custom-field-row').remove();
            syncAddButton();
        }
    });
    syncAddButton();
})();
</script>
@endsection
