@extends('admin.layout')

@section('title', 'ランク・ポイント・スタンプ')
@section('page-title', 'ランク・ポイント・スタンプ')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>ランク・ポイント・スタンプ</span>
</nav>

<form action="{{ admin_route('admin.settings.ranks.update') }}" method="POST" id="ranksSettingsForm">
    @csrf

    @include('admin.settings._section-tabs', ['tabsLabel' => 'ランク・ポイント・スタンプ', 'sectionTabs' => ['loyalty-ranks' => 'ランク', 'loyalty-points' => 'ポイント', 'loyalty-stamps' => 'スタンプ']])
    <div class="ranks-settings-panels">
        <section id="loyalty-ranks">
            <div class="card card-mb-24"><div class="card-header"><h3 class="card-title">ランクの判定条件</h3></div><div class="card-body">
                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle"><i class="fas fa-layer-group"></i> ランク判定基準</h4>
                        <div class="ranks-settings-fields">
                            <div class="form-group">
                                <label for="rank_basis" class="form-label">判定に使う指標</label>
                                <select name="rank_basis" id="rank_basis" class="form-control" required>
                                    <option value="points" {{ old('rank_basis', $view->rankBasis ?? 'points') === 'points' ? 'selected' : '' }}>ポイント</option>
                                    <option value="stamps" {{ old('rank_basis', $view->rankBasis ?? '') === 'stamps' ? 'selected' : '' }}>スタンプ</option>
                                    <option value="max" {{ old('rank_basis', $view->rankBasis ?? '') === 'max' ? 'selected' : '' }}>ポイント/スタンプ（多い方）</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">集計方法</label>
                                <div class="ranks-settings-radios">
                                    <label class="radio_text">
                                        <input type="radio" name="rank_aggregation_type" value="total" {{ old('rank_aggregation_type', $view->rankAggregationType ?? 'total') === 'total' ? 'checked' : '' }}>
                                        累計
                                    </label>
                                    <label class="radio_text">
                                        <input type="radio" name="rank_aggregation_type" value="period" {{ old('rank_aggregation_type', $view->rankAggregationType ?? '') === 'period' ? 'checked' : '' }}>
                                        期間
                                    </label>
                                </div>
                                <p class="form-text text-muted">累計は現在の保有数、期間は直近N日間の獲得数で判定します。</p>
                            </div>
                            <div class="form-group" id="rank_period_fields">
                                <label for="rank_period_days" class="form-label">集計期間（日）</label>
                                <input type="number" name="rank_period_days" id="rank_period_days" class="form-control" value="{{ old('rank_period_days', $view->rankPeriodDays ?? 365) }}" min="1" max="3650">
                                <label class="checkbox-label checkbox-block ranks-rank-down-toggle">
                                    <input type="hidden" name="is_rank_down_enabled" value="0">
                                    <input type="checkbox" name="is_rank_down_enabled" id="is_rank_down_enabled" value="1" {{ old('is_rank_down_enabled', $view->isRankDownEnabled ?? false) ? 'checked' : '' }}>
                                    <span>期間の条件を満たさない場合はランクダウンする</span>
                                </label>
                                <p class="form-text text-muted">オフのときは期間中の獲得が減ってもランクは下がりません。日次の再判定（cron）で反映します。</p>
                            </div>
                        </div>
                    </div>

            </div></div>
            <div class="card card-mb-24">
                @php
                    $defaultPointEarnRate = (int) old('point_earn_rate', $view->pointEarnRate ?? 100);
                    $defaultStampEarnPerAmount = (int) old('stamp_earn_per_amount', $view->stampEarnPerAmount ?? 0);
                @endphp
                <div class="card-header ranks-card-header-with-action">
                    <h3 class="card-title"><i class="fas fa-medal"></i> ランク一覧</h3>
                    <button type="button" class="btn btn-primary btn-sm" id="addRankRow"><i class="fas fa-plus"></i> ランクを追加</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table ranks-settings-table" id="rankSettingsTable">
                            <thead>
                                <tr>
                                    <th scope="col" class="ranks-th-basic">キー</th>
                                    <th scope="col" class="ranks-th-basic">表示名</th>
                                    <th scope="col" class="ranks-th-basic">基準値</th>
                                    <th scope="col" class="ranks-th-basic">付与レート<br><span class="ranks-th-sub">(円/1pt)</span></th>
                                    <th scope="col" class="ranks-th-basic">スタンプ<br><span class="ranks-th-sub">(円/1個)</span></th>
                                    <th scope="col" class="ranks-th-basic ranks-th--center">有効</th>
                                    <th scope="col" class="ranks-th-design">カード色</th>
                                    <th scope="col" class="ranks-th-basic">順序</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($view->rankSettings as $i => $rank)
                                <tr>
                                    <td><input type="text" name="ranks[{{ $i }}][key]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.key', $rank['key'] ?? '') }}" required placeholder="例: gold"></td>
                                    <td><input type="text" name="ranks[{{ $i }}][label]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.label', $rank['label'] ?? '') }}" placeholder="例: ゴールド"></td>
                                    <td><input type="number" name="ranks[{{ $i }}][min_points]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.min_points', $rank['min_points'] ?? 0) }}" min="0"></td>
                                    <td><input type="number" name="ranks[{{ $i }}][point_earn_rate]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.point_earn_rate', $rank['point_earn_rate'] ?? $defaultPointEarnRate) }}" min="1" max="99999"></td>
                                    <td><input type="number" name="ranks[{{ $i }}][stamp_earn_per_amount]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.stamp_earn_per_amount', $rank['stamp_earn_per_amount'] ?? $defaultStampEarnPerAmount) }}" min="0" max="10000000"></td>
                                    <td class="text-center"><input type="checkbox" name="ranks[{{ $i }}][is_active]" value="1" {{ ((bool) old('ranks.' . $i . '.is_active', $rank['is_active'] ?? true)) ? 'checked' : '' }}></td>
                                    <td>
                                        @php
                                            $rawCardColor = (string) old('ranks.' . $i . '.card_color', $rank['card_color'] ?? '');
                                            $pickerCardColor = '#cd7f32';
                                            if (preg_match('/^#([0-9A-Fa-f]{6})$/', $rawCardColor, $m)) {
                                                $pickerCardColor = '#' . $m[1];
                                            } elseif (preg_match('/^#([0-9A-Fa-f])([0-9A-Fa-f])([0-9A-Fa-f])$/', $rawCardColor, $m)) {
                                                $pickerCardColor = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
                                            } elseif ($rawCardColor !== '') {
                                                $pickerCardColor = '#cd7f32';
                                            }
                                            $hexCardColor = $rawCardColor !== '' ? $rawCardColor : $pickerCardColor;
                                        @endphp
                                        <div class="color-input-row ranks-color-row">
                                            <input type="color" class="color-picker js-rank-color-picker" value="{{ $pickerCardColor }}" aria-label="カード色">
                                            <input type="text" name="ranks[{{ $i }}][card_color]" class="form-control form-control-sm color-hex" value="{{ $hexCardColor }}" maxlength="7" placeholder="#cd7f32">
                                        </div>
                                    </td>
                                    <td><input type="number" name="ranks[{{ $i }}][order]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.order', $rank['order'] ?? ($i + 1)) }}" min="0"></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
        <section id="loyalty-points"><div class="card"><div class="card-header"><h3 class="card-title">ポイントの付与・有効期限</h3></div><div class="card-body">
                    <div class="ranks-settings-subsection" id="points_settings_section">
                        <h4 class="ranks-settings-subtitle"><i class="fas fa-coins"></i> ポイント</h4>
                        <p class="form-text text-muted">{{ config('native_app.order_type_mobile_label', 'Reggit') }}で注文完了時に付与するポイントを設定します。</p>
                        <div class="ranks-settings-fields">
                            <div class="form-group">
                                <label for="points_per_order" class="form-label">注文ごとの固定ポイント（pt）</label>
                                <input type="number" name="points_per_order" id="points_per_order" class="form-control" value="{{ old('points_per_order', $view->pointsPerOrder ?? 0) }}" min="0" max="99999" placeholder="0で無効">
                                <p class="form-text text-muted">例: 100 で毎回の注文に100pt付与。0の場合は付与しません。</p>
                            </div>
                            <div class="form-group">
                                <label for="point_earn_rate" class="form-label">デフォルト付与レート（円/1pt）</label>
                                <input type="number" name="point_earn_rate" id="point_earn_rate" class="form-control" value="{{ old('point_earn_rate', $view->pointEarnRate ?? 100) }}" min="1" max="99999" required>
                                <p class="form-text text-muted">例: 100 で100円ごとに1pt付与。ランク別レートが未設定のときのデフォルトです。保存時にスマレジ会員ランクへも同期します。</p>
                            </div>
                            <div class="form-group">
                                <label for="points_valid_days" class="form-label">有効期限（日）</label>
                                <input type="number" name="points_valid_days" id="points_valid_days" class="form-control" value="{{ old('points_valid_days', $view->pointsValidDays ?? 365) }}" min="1" max="3650" required>
                            </div>
                        </div>
                    </div>

        </div></div></section>
        <section id="loyalty-stamps"><div class="card"><div class="card-header"><h3 class="card-title">スタンプの付与</h3></div><div class="card-body">
                    <div class="ranks-settings-subsection" id="stamps_settings_section">
                        <h4 class="ranks-settings-subtitle"><i class="fas fa-stamp"></i> スタンプ（注文・取引連動）</h4>
                        <p class="form-text text-muted">{{ config('native_app.order_type_mobile_label', 'Reggit') }}の注文完了時、および取引連動スタンプON時のスマレジ会計で、会員が現在持っているスタンプラリーに付与します。未開始の場合は表示中のカードから付与を開始します。ランク別レートが未入力のときはこのデフォルトを使います。</p>
                        <div class="ranks-settings-fields">
                            <div class="form-group">
                                <label for="stamp_earn_per_amount" class="form-label">付与レート（円/1スタンプ）</label>
                                <input type="number" name="stamp_earn_per_amount" id="stamp_earn_per_amount" class="form-control" value="{{ old('stamp_earn_per_amount', $view->stampEarnPerAmount ?? 0) }}" min="0" max="10000000" placeholder="0で無効">
                                <p class="form-text text-muted">例: 1000 で1000円ごとに1スタンプ付与。0の場合は付与しません。スタンプカードが無い場合は <a href="{{ admin_route('admin.stamps.create') }}">スタンプ</a> から作成してください。</p>
                            </div>
                        </div>
                    </div>
        </div></div></section>
    </div>

    <div class="form-actions form-actions-ranks">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
    </div>
</form>

<template id="rankRowTemplate">
    <tr>
        <td><input type="text" name="__NAME__[key]" class="form-control form-control-sm" required placeholder="例: gold"></td>
        <td><input type="text" name="__NAME__[label]" class="form-control form-control-sm" placeholder="例: ゴールド"></td>
        <td><input type="number" name="__NAME__[min_points]" class="form-control form-control-sm" value="0" min="0"></td>
        <td><input type="number" name="__NAME__[point_earn_rate]" class="form-control form-control-sm js-rank-point-earn-rate" min="1" max="99999" value="{{ $defaultPointEarnRate }}"></td>
        <td><input type="number" name="__NAME__[stamp_earn_per_amount]" class="form-control form-control-sm js-rank-stamp-earn" min="0" max="10000000" value="{{ $defaultStampEarnPerAmount }}"></td>
        <td class="text-center"><input type="checkbox" name="__NAME__[is_active]" value="1" checked></td>
        <td>
            <div class="color-input-row ranks-color-row">
                <input type="color" class="color-picker js-rank-color-picker" value="#cd7f32" aria-label="カード色">
                <input type="text" name="__NAME__[card_color]" class="form-control form-control-sm color-hex" value="#cd7f32" maxlength="7" placeholder="#cd7f32">
            </div>
        </td>
        <td><input type="number" name="__NAME__[order]" class="form-control form-control-sm" value="0" min="0"></td>
    </tr>
</template>

<script>
document.getElementById('addRankRow')?.addEventListener('click', function() {
    var tableBody = document.querySelector('#rankSettingsTable tbody');
    var template = document.getElementById('rankRowTemplate');
    if (!tableBody || !template) return;
    var index = tableBody.children.length;
    var html = template.innerHTML.replaceAll('__NAME__', 'ranks[' + index + ']');
    var temp = document.createElement('tbody');
    temp.innerHTML = html.trim();
    var row = temp.firstElementChild;
    var pointDefault = document.getElementById('point_earn_rate');
    var stampDefault = document.getElementById('stamp_earn_per_amount');
    var pointInput = row.querySelector('.js-rank-point-earn-rate');
    var stampInput = row.querySelector('.js-rank-stamp-earn');
    if (pointInput !== null && pointDefault !== null && pointDefault.value !== '') {
        pointInput.value = pointDefault.value;
    }
    if (stampInput !== null && stampDefault !== null && stampDefault.value !== '') {
        stampInput.value = stampDefault.value;
    }
    tableBody.appendChild(row);
});

(function() {
    function expandHex(value) {
        var v = (value || '').trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(v)) return v;
        var m = v.match(/^#([0-9A-Fa-f])([0-9A-Fa-f])([0-9A-Fa-f])$/);
        if (m) return '#' + m[1] + m[1] + m[2] + m[2] + m[3] + m[3];
        return null;
    }

    function bindRankColorRow(row) {
        if (!row) return;
        var picker = row.querySelector('.js-rank-color-picker');
        var hex = row.querySelector('.color-hex');
        if (!picker || !hex) return;
        picker.addEventListener('input', function() {
            hex.value = picker.value;
            hex.dispatchEvent(new Event('input', { bubbles: true }));
        });
        hex.addEventListener('input', function() {
            var expanded = expandHex(hex.value);
            if (expanded !== null) picker.value = expanded;
        });
        hex.addEventListener('change', function() {
            var expanded = expandHex(hex.value);
            if (expanded !== null) {
                hex.value = expanded;
                picker.value = expanded;
            }
        });
    }

    document.querySelectorAll('#rankSettingsTable .ranks-color-row').forEach(bindRankColorRow);

    var addBtn = document.getElementById('addRankRow');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            window.setTimeout(function() {
                var rows = document.querySelectorAll('#rankSettingsTable .ranks-color-row');
                if (rows.length) bindRankColorRow(rows[rows.length - 1]);
            }, 0);
        });
    }
})();
</script>
@push('scripts')
<script>
(function () {
    const inputs = document.querySelectorAll('[name="rank_aggregation_type"]');
    const panel = document.getElementById('rank_period_fields');
    const update = () => { panel.hidden = document.querySelector('[name="rank_aggregation_type"]:checked')?.value !== 'period'; };
    inputs.forEach(input => input.addEventListener('change', update));
    update();
})();
</script>
@endpush
@endsection
