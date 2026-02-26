@extends('admin.layout')

@section('title', 'ランク・ポイント設定')
@section('page-title', 'ランク・ポイント設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.basic') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>ランク・ポイント</span>
</nav>

<form action="{{ admin_route('admin.settings.ranks.update') }}" method="POST">
    @csrf

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-id-card"></i> 会員コード設定</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted">新規会員登録時に発行する会員番号の生成方法を設定します。スマレジ連携時は20文字以内で送信されます。</p>
            <div class="form-group">
                <label for="member_code_type" class="form-label">生成方式</label>
                <select name="member_code_type" id="member_code_type" class="form-control">
                    <option value="random" {{ ($memberCodeType ?? 'random') === 'random' ? 'selected' : '' }}>ランダム</option>
                    <option value="sequential" {{ ($memberCodeType ?? '') === 'sequential' ? 'selected' : '' }}>連番</option>
                </select>
            </div>
            <div class="form-group">
                <label for="member_code_format" class="form-label">形式</label>
                <select name="member_code_format" id="member_code_format" class="form-control">
                    <option value="numeric" {{ ($memberCodeFormat ?? 'numeric') === 'numeric' ? 'selected' : '' }}>数字のみ</option>
                    <option value="alphanumeric" {{ ($memberCodeFormat ?? '') === 'alphanumeric' ? 'selected' : '' }}>英数字混在</option>
                </select>
                <p class="form-text text-muted">英数字混在は大文字英字と数字（I,O,1,0 を除く）を使用します。</p>
            </div>
            <div class="form-group">
                <label for="member_code_length" class="form-label">桁数（プレフィックス除く）</label>
                <input type="number" name="member_code_length" id="member_code_length" class="form-control" value="{{ old('member_code_length', $memberCodeLength ?? 10) }}" min="1" max="20">
            </div>
            <div class="form-group">
                <label for="member_code_prefix" class="form-label">プレフィックス（任意・英数字のみ・10文字以内）</label>
                <input type="text" name="member_code_prefix" id="member_code_prefix" class="form-control" value="{{ old('member_code_prefix', $memberCodePrefix ?? '') }}" maxlength="10" pattern="[A-Za-z0-9]*" placeholder="例: M">
            </div>
        </div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-eye"></i> 会員カード表示設定</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="show_points_display" value="1" {{ ($showPointsDisplay ?? true) ? 'checked' : '' }}>
                    ポイント数を表示する
                </label>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="show_rank_progress" value="1" {{ ($showRankProgress ?? true) ? 'checked' : '' }}>
                    「次のランクまで」を表示する
                </label>
            </div>
        </div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group"></i> ランク基準</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="rank_basis" class="form-label">ランク判定基準</label>
                <select name="rank_basis" id="rank_basis" class="form-control" required>
                    <option value="points" {{ ($view->rankBasis ?? 'points') === 'points' ? 'selected' : '' }}>ポイント</option>
                    <option value="stamps" {{ ($view->rankBasis ?? '') === 'stamps' ? 'selected' : '' }}>スタンプ</option>
                    <option value="max" {{ ($view->rankBasis ?? '') === 'max' ? 'selected' : '' }}>ポイント/スタンプ（多い方）</option>
                </select>
                <p class="form-text text-muted">スタンプ基準は会員の現在スタンプ合計で判定します。</p>
            </div>
        </div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-coins"></i> ポイント設定</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="point_earn_rate" class="form-label">ポイント付与レート（円/1pt）</label>
                <input type="number" name="point_earn_rate" id="point_earn_rate" class="form-control" value="{{ old('point_earn_rate', $view->pointEarnRate ?? 100) }}" min="1" max="100000" required>
            </div>
            <div class="form-group">
                <label for="points_valid_days" class="form-label">ポイント有効期限（日）</label>
                <input type="number" name="points_valid_days" id="points_valid_days" class="form-control" value="{{ old('points_valid_days', $view->pointsValidDays ?? 365) }}" min="1" max="3650" required>
            </div>
        </div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header tabs-header-row">
            <h3 class="card-title"><i class="fas fa-medal"></i> ランク設定</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="addRankRow"><i class="fas fa-plus"></i> ランク追加</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="rankSettingsTable">
                    <thead>
                        <tr>
                            <th>キー</th>
                            <th>表示名</th>
                            <th>基準値</th>
                            <th>有効</th>
                            <th>カード色</th>
                            <th>アイコンURL</th>
                            <th>スタンプ背景URL</th>
                            <th>順序</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($view->rankSettings as $i => $rank)
                        <tr>
                            <td><input type="text" name="ranks[{{ $i }}][key]" class="form-control" value="{{ old('ranks.' . $i . '.key', $rank['key'] ?? '') }}" required></td>
                            <td><input type="text" name="ranks[{{ $i }}][label]" class="form-control" value="{{ old('ranks.' . $i . '.label', $rank['label'] ?? '') }}"></td>
                            <td><input type="number" name="ranks[{{ $i }}][min_points]" class="form-control" value="{{ old('ranks.' . $i . '.min_points', $rank['min_points'] ?? 0) }}" min="0"></td>
                            <td class="text-center">
                                <input type="checkbox" name="ranks[{{ $i }}][is_active]" value="1" {{ ((bool) old('ranks.' . $i . '.is_active', $rank['is_active'] ?? true)) ? 'checked' : '' }}>
                            </td>
                            <td><input type="text" name="ranks[{{ $i }}][card_color]" class="form-control" value="{{ old('ranks.' . $i . '.card_color', $rank['card_color'] ?? '') }}" placeholder="#cd7f32" maxlength="20" style="max-width:8em;"></td>
                            <td><input type="url" name="ranks[{{ $i }}][icon_url]" class="form-control" value="{{ old('ranks.' . $i . '.icon_url', $rank['icon_url'] ?? '') }}" placeholder="https://..."></td>
                            <td><input type="url" name="ranks[{{ $i }}][stamp_background_url]" class="form-control" value="{{ old('ranks.' . $i . '.stamp_background_url', $rank['stamp_background_url'] ?? '') }}" placeholder="https://..."></td>
                            <td><input type="number" name="ranks[{{ $i }}][order]" class="form-control" value="{{ old('ranks.' . $i . '.order', $rank['order'] ?? ($i + 1)) }}" min="0"></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="form-text text-muted">基準値は「ポイント基準」の場合は累計ポイント、「スタンプ基準」の場合はスタンプ数です。</p>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">戻る</a>
    </div>
</form>

<template id="rankRowTemplate">
    <tr>
        <td><input type="text" name="__NAME__[key]" class="form-control" required></td>
        <td><input type="text" name="__NAME__[label]" class="form-control"></td>
        <td><input type="number" name="__NAME__[min_points]" class="form-control" value="0" min="0"></td>
        <td class="text-center"><input type="checkbox" name="__NAME__[is_active]" value="1" checked></td>
        <td><input type="text" name="__NAME__[card_color]" class="form-control" placeholder="#cd7f32" maxlength="20" style="max-width:8em;"></td>
        <td><input type="url" name="__NAME__[icon_url]" class="form-control" placeholder="https://..."></td>
        <td><input type="url" name="__NAME__[stamp_background_url]" class="form-control" placeholder="https://..."></td>
        <td><input type="number" name="__NAME__[order]" class="form-control" value="0" min="0"></td>
    </tr>
</template>

<script>
document.getElementById('addRankRow')?.addEventListener('click', function() {
    const tableBody = document.querySelector('#rankSettingsTable tbody');
    const template = document.getElementById('rankRowTemplate');
    if (!tableBody || !template) return;
    const index = tableBody.children.length;
    const html = template.innerHTML.replaceAll('__NAME__', `ranks[${index}]`);
    const temp = document.createElement('tbody');
    temp.innerHTML = html.trim();
    tableBody.appendChild(temp.firstElementChild);
});
</script>
@endsection
