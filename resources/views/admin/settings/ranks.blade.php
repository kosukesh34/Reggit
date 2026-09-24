@extends('admin.layout')

@section('title', '会員証・ランク設定')
@section('page-title', '会員証・ランク設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>会員証・ランク</span>
</nav>

<form action="{{ admin_route('admin.settings.ranks.update') }}" method="POST" id="ranksSettingsForm">
    @csrf

    {{-- タブナビゲーション --}}
    <div class="ranks-settings-tabs" role="tablist" aria-label="設定セクション">
        <button type="button" class="ranks-settings-tab ranks-settings-tab--active" role="tab" aria-selected="true" aria-controls="panel-basic" id="tab-basic">基本設定</button>
        <button type="button" class="ranks-settings-tab" role="tab" aria-selected="false" aria-controls="panel-card" id="tab-card">会員証表示</button>
        <button type="button" class="ranks-settings-tab" role="tab" aria-selected="false" aria-controls="panel-ranks" id="tab-ranks">ランク一覧</button>
    </div>

    <div class="ranks-settings-panels">
        {{-- 基本設定 --}}
        <section class="ranks-settings-panel ranks-settings-panel--active" id="panel-basic" role="tabpanel" aria-labelledby="tab-basic">
            <div class="card card-mb-24">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog"></i> 基本設定</h3>
                </div>
                <div class="card-body">

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle"><i class="fas fa-toggle-on"></i> 機能の利用</h4>
                        <p class="form-text text-muted">オフにした機能は顧客画面に出さず、スマレジWebhookでも付与しません。</p>
                        <div class="ranks-settings-checkboxes">
                            <label class="checkbox-label checkbox-block">
                                <input type="hidden" name="use_points" value="0">
                                <input type="checkbox" name="use_points" id="use_points" value="1" {{ old('use_points', $view->usePoints ?? true) ? 'checked' : '' }}>
                                <span>ポイント機能を利用する</span>
                            </label>
                            <label class="checkbox-label checkbox-block">
                                <input type="hidden" name="use_stamps" value="0">
                                <input type="checkbox" name="use_stamps" id="use_stamps" value="1" {{ old('use_stamps', $view->useStamps ?? true) ? 'checked' : '' }}>
                                <span>スタンプ機能を利用する</span>
                            </label>
                            <label class="checkbox-label checkbox-block" id="use_transaction_stamps_wrap">
                                <input type="hidden" name="use_transaction_stamps" value="0">
                                <input type="checkbox" name="use_transaction_stamps" id="use_transaction_stamps" value="1" {{ old('use_transaction_stamps', $view->useTransactionStamps ?? true) ? 'checked' : '' }}>
                                <span>取引連動スタンプを利用する</span>
                            </label>
                        </div>
                        <p class="form-text text-muted">取引連動スタンプは、スマレジPOSの会計時にランク別レートで、会員が現在持っているスタンプラリーへ付与します。スタンプ機能がオフのときは使えません。</p>
                    </div>

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle"><i class="fas fa-id-card"></i> 会員コード</h4>
                        <p class="form-text text-muted">新規会員登録時に発行する会員番号の生成方法。スマレジ連携時は20文字以内で送信されます。</p>
                        <div class="ranks-settings-fields">
                            <div class="form-group">
                                <label for="member_code_type" class="form-label">生成方式</label>
                                <select name="member_code_type" id="member_code_type" class="form-control">
                                    <option value="random" {{ old('member_code_type', $view->memberCodeType ?? 'random') === 'random' ? 'selected' : '' }}>ランダム</option>
                                    <option value="sequential" {{ old('member_code_type', $view->memberCodeType ?? '') === 'sequential' ? 'selected' : '' }}>連番</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="member_code_format" class="form-label">形式</label>
                                <select name="member_code_format" id="member_code_format" class="form-control">
                                    <option value="numeric" {{ old('member_code_format', $view->memberCodeFormat ?? 'numeric') === 'numeric' ? 'selected' : '' }}>数字のみ</option>
                                    <option value="alphanumeric" {{ old('member_code_format', $view->memberCodeFormat ?? '') === 'alphanumeric' ? 'selected' : '' }}>英数字混在</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="member_code_length" class="form-label">桁数（プレフィックス除く）</label>
                                <input type="number" name="member_code_length" id="member_code_length" class="form-control" value="{{ old('member_code_length', $view->memberCodeLength ?? 10) }}" min="1" max="20">
                            </div>
                            <div class="form-group">
                                <label for="member_code_prefix" class="form-label">プレフィックス（任意）</label>
                                <input type="text" name="member_code_prefix" id="member_code_prefix" class="form-control" value="{{ old('member_code_prefix', $view->memberCodePrefix ?? '') }}" maxlength="10" pattern="[A-Za-z0-9]*" placeholder="例: M">
                            </div>
                        </div>
                    </div>

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
                </div>
            </div>
        </section>

        {{-- 会員証表示 --}}
        <section class="ranks-settings-panel" id="panel-card" role="tabpanel" aria-labelledby="tab-card" hidden>
            <div class="card card-mb-24">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-eye"></i> 会員証の表示</h3>
                </div>
                <div class="card-body">

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">表示オプション</h4>
                        <div class="ranks-settings-checkboxes">
                            <label class="form-check" id="show_points_display_wrap">
                                <input type="hidden" name="show_points_display" value="0">
                                <input type="checkbox" name="show_points_display" value="1" {{ old('show_points_display', $view->showPointsDisplay ?? true) ? 'checked' : '' }}>
                                ポイント数を表示する
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="show_rank_progress" value="0">
                                <input type="checkbox" name="show_rank_progress" value="1" {{ old('show_rank_progress', $view->showRankProgress ?? true) ? 'checked' : '' }}>
                                ランクアップまでの条件を表示する
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="show_member_card_for_guest" value="0">
                                <input type="checkbox" name="show_member_card_for_guest" value="1" {{ old('show_member_card_for_guest', $view->showMemberCardForGuest ?? true) ? 'checked' : '' }}>
                                ゲストにも会員証エリアを表示する
                            </label>
                        </div>
                        <p class="form-text text-muted">オフにすると会員未登録時は「会員登録すると会員証が表示されます」などのメッセージのみ表示します。</p>
                    </div>

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">会員証画面の情報</h4>
                        <p class="form-text text-muted">氏名・電話番号などのユーザー情報は表示せず、会員証の下に表示する情報を選択できます。</p>
                        <div class="ranks-settings-checkboxes">
                            @foreach([
                                'member_show_rank_progress' => ['次のランクまでの進捗', $view->memberShowRankProgress ?? true],
                                'member_show_point_history' => ['ポイント付与・利用履歴', $view->memberShowPointHistory ?? true],
                                'member_show_coupon_activity' => ['最近獲得したクーポン', $view->memberShowCouponActivity ?? true],
                            ] as $field => [$label, $enabled])
                                <label class="form-check">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $enabled) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">デザイン・表示形式</h4>
                        <div class="form-group">
                            <label for="member_card_design" class="form-label">会員証デザイン</label>
                            <select name="member_card_design" id="member_card_design" class="form-control">
                                @foreach(['default' => 'スタンダード', 'compact' => 'シンプル', 'ticket' => 'チケット', 'minimal_line' => 'ミニマル', 'split_banner' => 'スプリット', 'logo_header' => 'レジ提示', 'logo_top' => 'ポイント', 'wallet' => 'ウォレット', 'emblem' => 'デジタルパス', 'logo_center' => 'ロゴ中央'] as $designKey => $designLabel)
                                    <option value="{{ $designKey }}" {{ old('member_card_design', $view->memberCardDesign ?? 'default') === $designKey ? 'selected' : '' }}>{{ $designLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ranks-settings-fields ranks-settings-fields--wide">
                            <div class="form-group">
                                <label for="member_card_display_type" class="form-label">表示形式</label>
                                <select name="member_card_display_type" id="member_card_display_type" class="form-control">
                                    <option value="card" {{ old('member_card_display_type', $view->memberCardDisplayType ?? 'card') === 'card' ? 'selected' : '' }}>カード型（裏表・タップで裏返し）</option>
                                    <option value="sheet" {{ old('member_card_display_type', $view->memberCardDisplayType ?? '') === 'sheet' ? 'selected' : '' }}>シート型（1面）</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="member_card_code_type" class="form-label">会員番号の表示</label>
                                <select name="member_card_code_type" id="member_card_code_type" class="form-control">
                                    <option value="barcode" {{ old('member_card_code_type', $view->memberCardCodeType ?? 'barcode') === 'barcode' ? 'selected' : '' }}>バーコード</option>
                                    <option value="qrcode" {{ old('member_card_code_type', $view->memberCardCodeType ?? '') === 'qrcode' ? 'selected' : '' }}>QRコード</option>
                                </select>
                            </div>
                        </div>
                        <p class="form-text text-muted">ロゴ画像は <a href="{{ admin_route('admin.settings.features') }}">機能設定</a> のヘッダー画像です。</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ランク一覧 --}}
        <section class="ranks-settings-panel" id="panel-ranks" role="tabpanel" aria-labelledby="tab-ranks" hidden>
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
(function() {
    var tabButtons = document.querySelectorAll('.ranks-settings-tab');
    var panels = document.querySelectorAll('.ranks-settings-panel');
    function switchTab(activeId) {
        tabButtons.forEach(function(btn) {
            var isActive = btn.id === 'tab-' + activeId;
            btn.classList.toggle('ranks-settings-tab--active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach(function(panel) {
            var match = panel.id === 'panel-' + activeId;
            panel.classList.toggle('ranks-settings-panel--active', match);
            panel.hidden = !match;
        });
    }
    tabButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.id.replace('tab-', '');
            switchTab(id);
        });
    });
})();

(function() {
    var useStamps = document.getElementById('use_stamps');
    var useTx = document.getElementById('use_transaction_stamps');
    var useTxWrap = document.getElementById('use_transaction_stamps_wrap');
    var usePoints = document.getElementById('use_points');
    var pointsSection = document.getElementById('points_settings_section');
    var stampsSection = document.getElementById('stamps_settings_section');
    var periodFields = document.getElementById('rank_period_fields');
    var showPointsWrap = document.getElementById('show_points_display_wrap');

    function isPeriod() {
        var checked = document.querySelector('input[name="rank_aggregation_type"]:checked');
        return checked !== null && checked.value === 'period';
    }

    function syncLoyaltyUi() {
        var stampsOn = useStamps !== null && useStamps.checked;
        var pointsOn = usePoints !== null && usePoints.checked;
        if (useTxWrap !== null) {
            useTxWrap.classList.toggle('is-hidden', !stampsOn);
        }
        if (useTx !== null && !stampsOn) {
            useTx.checked = false;
        }
        if (pointsSection !== null) {
            pointsSection.classList.toggle('is-hidden', !pointsOn);
        }
        if (showPointsWrap !== null) {
            showPointsWrap.classList.toggle('is-hidden', !pointsOn);
        }
        if (stampsSection !== null) {
            stampsSection.classList.toggle('is-hidden', !stampsOn);
        }
        if (periodFields !== null) {
            periodFields.classList.toggle('is-hidden', !isPeriod());
        }
    }

    if (useStamps !== null) {
        useStamps.addEventListener('change', syncLoyaltyUi);
    }
    if (usePoints !== null) {
        usePoints.addEventListener('change', syncLoyaltyUi);
    }
    document.querySelectorAll('input[name="rank_aggregation_type"]').forEach(function(radio) {
        radio.addEventListener('change', syncLoyaltyUi);
    });
    syncLoyaltyUi();
})();

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
@endsection
