@extends('admin.layout')

@section('title', 'ランク・ポイント設定')
@section('page-title', 'ランク・ポイント設定')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/rank-card-preview.css') }}">
@endpush

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.basic') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>ランク・ポイント</span>
</nav>
@include('admin.settings._context', ['pageKey' => 'ranks'])

<form action="{{ admin_route('admin.settings.ranks.update') }}" method="POST" id="ranksSettingsForm">
    @csrf

    {{-- タブナビゲーション --}}
    <div class="ranks-settings-tabs" role="tablist" aria-label="設定セクション">
        <button type="button" class="ranks-settings-tab ranks-settings-tab--active" role="tab" aria-selected="true" aria-controls="panel-basic" id="tab-basic">基本設定</button>
        <button type="button" class="ranks-settings-tab" role="tab" aria-selected="false" aria-controls="panel-card" id="tab-card">会員証表示</button>
        <button type="button" class="ranks-settings-tab" role="tab" aria-selected="false" aria-controls="panel-ranks" id="tab-ranks">ランク一覧</button>
        <button type="button" class="ranks-settings-tab" role="tab" aria-selected="false" aria-controls="panel-preview" id="tab-preview">プレビュー</button>
    </div>

    <div class="ranks-settings-panels">
        {{-- 基本設定 --}}
        <section class="ranks-settings-panel ranks-settings-panel--active" id="panel-basic" role="tabpanel" aria-labelledby="tab-basic">
            <div class="card card-mb-24">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog"></i> 基本設定</h3>
                </div>
                <div class="card-body">
                    <p class="ranks-settings-lead">会員コードの生成方法、ランクの判定基準、ポイント付与を設定します。</p>

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
                        <div class="form-group" style="max-width: 320px;">
                            <select name="rank_basis" id="rank_basis" class="form-control" required>
                                <option value="points" {{ ($view->rankBasis ?? 'points') === 'points' ? 'selected' : '' }}>ポイント</option>
                                <option value="stamps" {{ ($view->rankBasis ?? '') === 'stamps' ? 'selected' : '' }}>スタンプ</option>
                                <option value="max" {{ ($view->rankBasis ?? '') === 'max' ? 'selected' : '' }}>ポイント/スタンプ（多い方）</option>
                            </select>
                            <p class="form-text text-muted">スタンプ基準は会員の現在スタンプ合計で判定します。</p>
                        </div>
                    </div>

                    <div class="ranks-settings-subsection">
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

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle"><i class="fas fa-stamp"></i> スタンプ（注文時付与）</h4>
                        <p class="form-text text-muted">{{ config('native_app.order_type_mobile_label', 'Reggit') }}での注文完了時、およびスマレジPOSでの会計時にスタンプを付与する場合は設定します。未設定の場合は付与しません。</p>
                        <div class="ranks-settings-fields">
                            <div class="form-group">
                                <label for="stamp_earn_per_amount" class="form-label">付与レート（円/1スタンプ）</label>
                                <input type="number" name="stamp_earn_per_amount" id="stamp_earn_per_amount" class="form-control" value="{{ old('stamp_earn_per_amount', $view->stampEarnPerAmount ?? 0) }}" min="0" max="10000000" placeholder="0で無効">
                                <p class="form-text text-muted">例: 1000 で1000円ごとに1スタンプ付与。0の場合は付与しません。</p>
                            </div>
                            <div class="form-group">
                                <label for="order_stamp_card_id" class="form-label">付与先スタンプカード</label>
                                <select name="order_stamp_card_id" id="order_stamp_card_id" class="form-control">
                                    <option value="">付与しない</option>
                                    @foreach($view->stampCardsForSelect ?? [] as $card)
                                        <option value="{{ $card->id }}" {{ (string)old('order_stamp_card_id', $view->orderStampCardId ?? '') === (string)$card->id ? 'selected' : '' }}>{{ $card->name }}（{{ $card->required_stamps }}個で達成）</option>
                                    @endforeach
                                </select>
                                <p class="form-text text-muted">スタンプカードが無い場合は <a href="{{ admin_route('admin.stamps.create') }}">スタンプ</a> から作成してください。</p>
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
                    <p class="ranks-settings-lead">会員証の表示内容とデザインを設定します。</p>

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">表示オプション</h4>
                        <div class="ranks-settings-checkboxes">
                            <label class="form-check">
                                <input type="checkbox" name="show_points_display" value="1" {{ old('show_points_display', $view->showPointsDisplay ?? true) ? 'checked' : '' }}>
                                ポイント数を表示する
                            </label>
                            <label class="form-check">
                                <input type="checkbox" name="show_rank_progress" value="1" {{ old('show_rank_progress', $view->showRankProgress ?? true) ? 'checked' : '' }}>
                                ランクアップまでの条件を表示する
                            </label>
                            <label class="form-check">
                                <input type="checkbox" name="show_member_card_for_guest" value="1" {{ old('show_member_card_for_guest', $view->showMemberCardForGuest ?? true) ? 'checked' : '' }}>
                                ゲストにも会員証エリアを表示する
                            </label>
                        </div>
                        <p class="form-text text-muted">オフにすると会員未登録時は「会員登録すると会員証が表示されます」などのメッセージのみ表示します。</p>
                    </div>

                    <div class="ranks-settings-subsection">
                        <h4 class="ranks-settings-subtitle">会員証デザイン</h4>
                        <div class="ranks-settings-fields ranks-settings-fields--wide">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                @php
                                    $currentMemberCardDesign = old('member_card_design', $view->memberCardDesign ?? 'default');
                                    $currentMemberCardDesignLabel = [
                                        'default' => 'スタンダード',
                                        'logo_top' => 'ポイント',
                                        'logo_center' => 'デジタルパス',
                                        'logo_header' => 'レジ提示',
                                        'compact' => 'レジ提示',
                                        'split_banner' => 'スプリット',
                                        'minimal_line' => 'ミニマル',
                                        'emblem' => 'デジタルパス',
                                    ][$currentMemberCardDesign] ?? 'スタンダード';
                                @endphp
                                <p class="form-text" style="margin: 0 0 10px;">
                                    レイアウトの選択と表示項目は「LIFFデザイン」に集約しています。この画面ではランク条件とランク別のカード色・アイコンを管理します。
                                </p>
                                <a class="btn btn-secondary btn-sm" href="{{ admin_route('admin.settings.liff-designer') }}#features">
                                    <i class="fas fa-id-card"></i> 会員証デザインを変更
                                </a>
                                <p class="form-text text-muted" style="margin: 10px 0 0;">
                                    現在: レイアウト「{{ $currentMemberCardDesignLabel }}」 /
                                    表示「{{ ($view->memberCardDisplayType ?? 'card') === 'sheet' ? 'シート型' : 'カード型' }}」 /
                                    番号「{{ ($view->memberCardCodeType ?? 'barcode') === 'qrcode' ? 'QRコード' : 'バーコード' }}」
                                </p>
                                <input type="hidden" name="member_card_design" value="{{ $currentMemberCardDesign }}">
                                <input type="hidden" name="member_card_display_type" value="{{ old('member_card_display_type', $view->memberCardDisplayType ?? 'card') }}">
                                <input type="hidden" name="member_card_code_type" value="{{ old('member_card_code_type', $view->memberCardCodeType ?? 'barcode') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ランク一覧 --}}
        <section class="ranks-settings-panel" id="panel-ranks" role="tabpanel" aria-labelledby="tab-ranks" hidden>
            <div class="card card-mb-24">
                <div class="card-header ranks-card-header-with-action">
                    <h3 class="card-title"><i class="fas fa-medal"></i> ランク一覧</h3>
                    <button type="button" class="btn btn-primary btn-sm" id="addRankRow"><i class="fas fa-plus"></i> ランクを追加</button>
                </div>
                <div class="card-body">
                    <p class="ranks-settings-lead">会員のランクを定義します。基準値は「ポイント基準」の場合は累計ポイント、「スタンプ基準」の場合はスタンプ数です。付与レートはランクごとに設定でき、未入力時はデフォルト付与レートを使います。</p>
                    <div class="table-responsive">
                        <table class="table ranks-settings-table" id="rankSettingsTable">
                            <thead>
                                <tr>
                                    <th scope="col" class="ranks-th-basic">キー</th>
                                    <th scope="col" class="ranks-th-basic">表示名</th>
                                    <th scope="col" class="ranks-th-basic">基準値</th>
                                    <th scope="col" class="ranks-th-basic">付与レート<br><span class="ranks-th-sub">(円/1pt)</span></th>
                                    <th scope="col" class="ranks-th-basic ranks-th--center">有効</th>
                                    <th scope="col" class="ranks-th-design">カード色</th>
                                    <th scope="col" class="ranks-th-design">アイコンURL</th>
                                    <th scope="col" class="ranks-th-design">スタンプ背景URL</th>
                                    <th scope="col" class="ranks-th-basic">順序</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($view->rankSettings as $i => $rank)
                                <tr>
                                    <td><input type="text" name="ranks[{{ $i }}][key]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.key', $rank['key'] ?? '') }}" required placeholder="例: gold"></td>
                                    <td><input type="text" name="ranks[{{ $i }}][label]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.label', $rank['label'] ?? '') }}" placeholder="例: ゴールド"></td>
                                    <td><input type="number" name="ranks[{{ $i }}][min_points]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.min_points', $rank['min_points'] ?? 0) }}" min="0"></td>
                                    <td><input type="number" name="ranks[{{ $i }}][point_earn_rate]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.point_earn_rate', $rank['point_earn_rate'] ?? '') }}" min="1" max="99999" placeholder="{{ $view->pointEarnRate ?? 100 }}"></td>
                                    <td class="text-center"><input type="checkbox" name="ranks[{{ $i }}][is_active]" value="1" {{ ((bool) old('ranks.' . $i . '.is_active', $rank['is_active'] ?? true)) ? 'checked' : '' }}></td>
                                    <td><input type="text" name="ranks[{{ $i }}][card_color]" class="form-control form-control-sm ranks-input-color" value="{{ old('ranks.' . $i . '.card_color', $rank['card_color'] ?? '') }}" placeholder="#cd7f32" maxlength="20"></td>
                                    <td><input type="url" name="ranks[{{ $i }}][icon_url]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.icon_url', $rank['icon_url'] ?? '') }}" placeholder="https://..."></td>
                                    <td><input type="url" name="ranks[{{ $i }}][stamp_background_url]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.stamp_background_url', $rank['stamp_background_url'] ?? '') }}" placeholder="https://..."></td>
                                    <td><input type="number" name="ranks[{{ $i }}][order]" class="form-control form-control-sm" value="{{ old('ranks.' . $i . '.order', $rank['order'] ?? ($i + 1)) }}" min="0"></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- プレビュー --}}
        @php
            $previewDesign = old('member_card_design', $view->memberCardDesign ?? 'default');
            $previewLogoUrl = $view->memberCardLogoUrl ?? null;
            $previewCodeType = old('member_card_code_type', $view->memberCardCodeType ?? 'barcode');
            $designLabelMap = [
                'default' => '標準',
                'logo_top' => 'ロゴ上部',
                'logo_center' => 'ロゴ中央',
                'logo_header' => 'ロゴヘッダー',
                'compact' => 'コンパクト',
                'split_banner' => 'スプリットバナー',
                'minimal_line' => 'ミニマルライン',
                'emblem' => 'エンブレム',
            ];
            $previewDesignLabel = $designLabelMap[$previewDesign] ?? '標準';
            $logoEnabledDesigns = ['logo_top', 'logo_center', 'logo_header', 'split_banner', 'emblem'];
        @endphp
        <section class="ranks-settings-panel" id="panel-preview" role="tabpanel" aria-labelledby="tab-preview" hidden>
            <div class="card card-mb-24 ranks-preview-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-image"></i> 会員証プレビュー</h3>
                </div>
                <div class="card-body">
                    <div class="ranks-preview-section">
                        <h4 class="ranks-preview-section-title">ランク別のカード色・アイコンを確認</h4>
                        <p class="ranks-preview-desc">レイアウトはLIFFデザインで選択中の「{{ e($previewDesignLabel) }}」です。この画面では各ランクの色とアイコンの違いを確認できます。</p>
                        <p class="ranks-preview-desc">ロゴ画像: {{ (isset($previewLogoUrl) && (string)$previewLogoUrl !== '') ? '設定済み（機能設定でアップロード）' : '未設定（機能設定でアップロード）' }}</p>
                        <div class="rank-card-preview-wrap" data-preview-design="{{ e($previewDesign) }}">
                            <div class="rank-card-preview-grid" id="rankCardPreviewGrid">
                                @foreach($view->rankSettings as $rank)
                                @php
                                    $key = $rank['key'] ?? 'bronze';
                                    $label = $rank['label'] ?? $key;
                                    $cardColor = $rank['card_color'] ?? null;
                                    $iconUrl = $rank['icon_url'] ?? null;
                                    $wrapperAttr = $cardColor ? ' data-rank-color="true" style="--idnect-card-rank-color: ' . e($cardColor) . '"' : '';
                                    $designClass = 'idnect-card-wrapper--design-' . $previewDesign;
                                @endphp
                                <div class="rank-card-preview-item rank-card-preview-item--static" data-rank-index="{{ $loop->index }}">
                                    <div class="idnect-card-wrapper {{ $designClass }}" data-rank-key="{{ e($key) }}"{!! $wrapperAttr !!}>
                                        <div class="idnect-card" data-rank="{{ e($key) }}">
                                            <div class="idnect-card-shimmer" aria-hidden="true"></div>
                                            <div class="idnect-card-face idnect-card-face--front">
                                                <div class="idnect-card-front-inner">
                                                    @if(in_array($previewDesign, $logoEnabledDesigns, true))
                                                    <div class="idnect-card-logo-wrap">
                                                        @if(isset($previewLogoUrl) && (string)$previewLogoUrl !== '')
                                                        <img src="{{ $previewLogoUrl }}" class="idnect-card-logo" alt="" onerror="this.nextElementSibling?.classList.remove('d-none')">
                                                        @endif
                                                        <span class="idnect-card-logo-placeholder {{ (isset($previewLogoUrl) && (string)$previewLogoUrl !== '') ? 'd-none' : '' }}">ロゴ</span>
                                                    </div>
                                                    @endif
                                                    <p class="idnect-card-display-name">会員名サンプル</p>
                                                    <div class="idnect-card-meta-row">
                                                        <div class="idnect-card-rank-wrap">
                                                            @if(isset($iconUrl) && (string)$iconUrl !== '')
                                                            <img src="{{ $iconUrl }}" class="idnect-card-rank-icon" alt="" onerror="this.style.display='none'">
                                                            @endif
                                                            <span class="idnect-card-rank-badge" data-rank="{{ e($key) }}">{{ e($label) }}</span>
                                                        </div>
                                                        <div class="idnect-card-points idnect-card-points--front">
                                                            <span class="idnect-card-points-value">1,234</span>
                                                            <span class="idnect-card-points-label">pt</span>
                                                        </div>
                                                    </div>
                                                    <div class="idnect-card-barcode-wrap idnect-card-code-wrap--{{ e($previewCodeType) }}">
                                                        <div class="idnect-card-code-sample" aria-hidden="true"></div>
                                                    </div>
                                                    <p class="idnect-card-tap-hint">会員バーコードのサンプル表示</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="rank-card-preview-label">{{ e($previewDesignLabel) }} / {{ e($label) }} <span class="rank-card-preview-key">（{{ e($key) }}）</span></span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="form-actions form-actions-ranks">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">戻る</a>
    </div>
</form>

<template id="rankRowTemplate">
    <tr>
        <td><input type="text" name="__NAME__[key]" class="form-control form-control-sm" required placeholder="例: gold"></td>
        <td><input type="text" name="__NAME__[label]" class="form-control form-control-sm" placeholder="例: ゴールド"></td>
        <td><input type="number" name="__NAME__[min_points]" class="form-control form-control-sm" value="0" min="0"></td>
        <td><input type="number" name="__NAME__[point_earn_rate]" class="form-control form-control-sm" min="1" max="99999" placeholder="{{ $view->pointEarnRate ?? 100 }}"></td>
        <td class="text-center"><input type="checkbox" name="__NAME__[is_active]" value="1" checked></td>
        <td><input type="text" name="__NAME__[card_color]" class="form-control form-control-sm ranks-input-color" placeholder="#cd7f32" maxlength="20"></td>
        <td><input type="url" name="__NAME__[icon_url]" class="form-control form-control-sm" placeholder="https://..."></td>
        <td><input type="url" name="__NAME__[stamp_background_url]" class="form-control form-control-sm" placeholder="https://..."></td>
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

document.getElementById('addRankRow')?.addEventListener('click', function() {
    var tableBody = document.querySelector('#rankSettingsTable tbody');
    var template = document.getElementById('rankRowTemplate');
    if (!tableBody || !template) return;
    var index = tableBody.children.length;
    var html = template.innerHTML.replaceAll('__NAME__', 'ranks[' + index + ']');
    var temp = document.createElement('tbody');
    temp.innerHTML = html.trim();
    tableBody.appendChild(temp.firstElementChild);
});

(function() {
    var table = document.getElementById('rankSettingsTable');
    var grid = document.getElementById('rankCardPreviewGrid');
    if (!table || !grid) return;

    function updatePreview(index) {
        var item = grid.querySelector('.rank-card-preview-item[data-rank-index="' + index + '"]');
        if (!item) return;
        var tbody = table.querySelector('tbody');
        var row = tbody && tbody.rows[index];
        if (!row) return;
        var keyInput = row.querySelector('input[name$="[key]"]');
        var labelInput = row.querySelector('input[name$="[label]"]');
        var colorInput = row.querySelector('input[name$="[card_color]"]');
        var iconInput = row.querySelector('input[name$="[icon_url]"]');
        var key = (keyInput && keyInput.value) ? keyInput.value.trim() : '';
        var label = (labelInput && labelInput.value) ? labelInput.value.trim() : key;
        var color = (colorInput && colorInput.value) ? colorInput.value.trim() : '';
        var iconUrl = (iconInput && iconInput.value) ? iconInput.value.trim() : '';

        var wrapper = item.querySelector('.idnect-card-wrapper');
        var card = item.querySelector('.idnect-card');
        var badge = item.querySelector('.idnect-card-rank-badge');
        var labelSpan = item.querySelector('.rank-card-preview-label');
        var keySpan = item.querySelector('.rank-card-preview-key');
        var iconImg = item.querySelector('.idnect-card-rank-icon');

        if (card) card.setAttribute('data-rank', key || 'bronze');
        if (badge) {
            badge.textContent = label || key || '—';
            badge.setAttribute('data-rank', key || 'bronze');
        }
        if (labelSpan) labelSpan.innerHTML = (label || key || '—') + (key ? ' <span class="rank-card-preview-key">（' + key + '）</span>' : '');

        if (wrapper) {
            if (color && /^#[0-9A-Fa-f]{3,6}$/.test(color)) {
                wrapper.setAttribute('data-rank-color', 'true');
                wrapper.style.setProperty('--idnect-card-rank-color', color);
            } else {
                wrapper.removeAttribute('data-rank-color');
                wrapper.style.removeProperty('--idnect-card-rank-color');
            }
        }

        var rankWrap = item.querySelector('.idnect-card-rank-wrap');
        if (rankWrap) {
            if (iconUrl) {
                var img = iconImg;
                if (!img) {
                    img = document.createElement('img');
                    img.className = 'idnect-card-rank-icon';
                    img.alt = '';
                    img.onerror = function() { this.style.display = 'none'; };
                    rankWrap.insertBefore(img, badge);
                }
                img.src = iconUrl;
                img.style.display = '';
            } else if (iconImg) {
                iconImg.remove();
            }
        }
    }

    table.addEventListener('input', function(e) {
        var name = e.target && e.target.getAttribute('name');
        if (!name || name.indexOf('ranks[') !== 0) return;
        var m = name.match(/^ranks\[(\d+)\]/);
        if (m) updatePreview(parseInt(m[1], 10));
    });
    table.addEventListener('change', function(e) {
        var name = e.target && e.target.getAttribute('name');
        if (!name || name.indexOf('ranks[') !== 0) return;
        var m = name.match(/^ranks\[(\d+)\]/);
        if (m) updatePreview(parseInt(m[1], 10));
    });

    grid.querySelectorAll('.rank-card-preview-item[data-rank-index]').forEach(function(item) {
        var idx = parseInt(item.getAttribute('data-rank-index') || '', 10);
        if (!isNaN(idx)) updatePreview(idx);
    });
})();

function updatePreviewDesign(design) {
    design = design || 'default';
    var designSelect = document.getElementById('member_card_design');
    var rankWrap = document.querySelector('.rank-card-preview-wrap[data-preview-design]');
    var rankWrappers = document.querySelectorAll('#rankCardPreviewGrid .idnect-card-wrapper');
    var designClasses = ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'];
    if (designSelect) designSelect.value = design;
    if (rankWrap) rankWrap.setAttribute('data-preview-design', design);
    if (rankWrappers.length) {
        rankWrappers.forEach(function(w) {
            designClasses.forEach(function(c) { w.classList.remove('idnect-card-wrapper--design-' + c); });
            w.classList.add('idnect-card-wrapper--design-' + design);
        });
    }
}

(function() {
    var designSelect = document.getElementById('member_card_design');
    var rankWrap = document.querySelector('.rank-card-preview-wrap[data-preview-design]');
    var rankWrappers = document.querySelectorAll('#rankCardPreviewGrid .idnect-card-wrapper');
    if (!designSelect || !rankWrap || !rankWrappers.length) return;
    designSelect.addEventListener('change', function() { updatePreviewDesign(this.value); });
})();

(function() {
    var codeSelect = document.getElementById('member_card_code_type');
    if (!codeSelect) return;
    codeSelect.addEventListener('change', function() {
        var val = this.value;
        document.querySelectorAll('.rank-card-preview-wrap .idnect-card-barcode-wrap').forEach(function(wrap) {
            wrap.classList.remove('idnect-card-code-wrap--barcode', 'idnect-card-code-wrap--qrcode');
            wrap.classList.add('idnect-card-code-wrap--' + val);
        });
    });
})();
</script>
@endsection
