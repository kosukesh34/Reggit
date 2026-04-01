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
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">スマレジ連携用項目（表示ON/OFF・必須/任意）</h2>
            <p class="card-desc text-muted small mb-0">会員アプリのプロフィールで表示する項目と、必須か任意かを設定します。スマレジと同一内容を登録する場合に利用できます。</p>
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
                    <tr>
                        <td>{{ $field['label'] ?? $key }}</td>
                        <td>
                            <input type="hidden" name="smaregi_fields[{{ $key }}][enabled]" value="0">
                            <label class="checkbox-label mb-0">
                                <input type="checkbox" name="smaregi_fields[{{ $key }}][enabled]" value="1" {{ ($field['enabled'] ?? true) ? 'checked' : '' }}>
                                <span>表示する</span>
                            </label>
                        </td>
                        <td>
                            <label class="radio-label-inline">
                                <input type="radio" name="smaregi_fields[{{ $key }}][required]" value="1" {{ ($field['required'] ?? false) ? 'checked' : '' }}> 必須
                            </label>
                            <label class="radio-label-inline">
                                <input type="radio" name="smaregi_fields[{{ $key }}][required]" value="0" {{ !($field['required'] ?? false) ? 'checked' : '' }}> 任意
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
            <p class="text-muted small mb-3">会員プロフィールに独自の入力項目を追加できます。キーは半角英数字とアンダースコアのみ（例: company_name）。</p>
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
    let container = document.getElementById('customFieldsContainer');
    let addBtn = document.getElementById('addCustomField');
    let index = (container && container.querySelectorAll('.custom-field-row').length) || 0;

    function addRow() {
        let row = document.createElement('div');
        row.className = 'custom-field-row form-row align-items-center mb-2';
        row.innerHTML =
            '<div class="col-md-3"><input type="text" name="custom_fields[' + index + '][key]" class="form-control form-control-sm" placeholder="key_name" pattern="[a-z0-9_]+"></div>' +
            '<div class="col-md-4"><input type="text" name="custom_fields[' + index + '][label]" class="form-control form-control-sm" placeholder="表示名"></div>' +
            '<div class="col-md-2"><label class="checkbox-label mb-0"><input type="hidden" name="custom_fields[' + index + '][required]" value="0"><input type="checkbox" name="custom_fields[' + index + '][required]" value="1"> 必須</label></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-custom-field"><i class="fas fa-times"></i></button></div>';
        container.appendChild(row);
        row.querySelector('.remove-custom-field').addEventListener('click', function() { row.remove(); });
        index++;
    }
    addBtn.addEventListener('click', addRow);
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-custom-field')) e.target.closest('.custom-field-row').remove();
    });
})();
</script>
@endsection
