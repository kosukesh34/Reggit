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
                <p class="form-text text-muted">英数字混在は大文字英字と数字（I,O,1,0 を除く）を使用します。</p>
            </div>
            <div class="form-group">
                <label for="member_code_length" class="form-label">桁数（プレフィックス除く）</label>
                <input type="number" name="member_code_length" id="member_code_length" class="form-control" value="{{ old('member_code_length', $view->memberCodeLength ?? 10) }}" min="1" max="20">
            </div>
            <div class="form-group">
                <label for="member_code_prefix" class="form-label">プレフィックス（任意・英数字のみ・10文字以内）</label>
                <input type="text" name="member_code_prefix" id="member_code_prefix" class="form-control" value="{{ old('member_code_prefix', $view->memberCodePrefix ?? '') }}" maxlength="10" pattern="[A-Za-z0-9]*" placeholder="例: M">
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
                    <input type="checkbox" name="show_points_display" value="1" {{ old('show_points_display', $view->showPointsDisplay ?? true) ? 'checked' : '' }}>
                    ポイント数を表示する
                </label>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="show_rank_progress" value="1" {{ old('show_rank_progress', $view->showRankProgress ?? true) ? 'checked' : '' }}>
                    ランクアップまでの条件（「次のランクまで XXX pt」「あと X 日」など）を表示する
                </label>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="show_member_card_for_guest" value="1" {{ old('show_member_card_for_guest', $view->showMemberCardForGuest ?? true) ? 'checked' : '' }}>
                    会員未登録（ゲスト）のときも会員証エリアを表示する
                </label>
                <p class="form-text text-muted">オフにすると、端末は認証済みだが会員登録していない場合、会員証カードは表示せず「会員登録すると会員証が表示されます」などのメッセージのみ表示します。オンの場合は従来どおり会員登録を促す表示になります。</p>
            </div>
            <div class="form-group">
                <label for="member_card_design" class="form-label">会員証デザイン</label>
                <select name="member_card_design" id="member_card_design" class="form-control">
                    <option value="default" {{ old('member_card_design', $view->memberCardDesign ?? 'default') === 'default' ? 'selected' : '' }}>標準（名前・ランク・バーコード）</option>
                    <option value="logo_top" {{ old('member_card_design', $view->memberCardDesign ?? '') === 'logo_top' ? 'selected' : '' }}>ロゴ上部（店舗ロゴをカード上部に表示）</option>
                    <option value="logo_center" {{ old('member_card_design', $view->memberCardDesign ?? '') === 'logo_center' ? 'selected' : '' }}>ロゴ中央（店舗ロゴを中央に表示）</option>
                    <option value="logo_header" {{ old('member_card_design', $view->memberCardDesign ?? '') === 'logo_header' ? 'selected' : '' }}>ロゴヘッダー（ロゴとランクを1行で表示）</option>
                    <option value="compact" {{ old('member_card_design', $view->memberCardDesign ?? '') === 'compact' ? 'selected' : '' }}>コンパクト（余白を抑えたレイアウト）</option>
                </select>
                <p class="form-text text-muted">会員証のレイアウトです。ロゴはプロジェクト設定の「フロント用ロゴURL」を使用します。下のプレビューでタップして選択できます。</p>
            </div>
            <div class="form-group">
                <label for="member_card_display_type" class="form-label">会員証の表示形式</label>
                <select name="member_card_display_type" id="member_card_display_type" class="form-control">
                    <option value="card" {{ old('member_card_display_type', $view->memberCardDisplayType ?? 'card') === 'card' ? 'selected' : '' }}>カード型（裏表あり・タップで裏返し）</option>
                    <option value="sheet" {{ old('member_card_display_type', $view->memberCardDisplayType ?? '') === 'sheet' ? 'selected' : '' }}>シート型（1面表示）</option>
                </select>
                <p class="form-text text-muted">カード型は従来の裏表フリップ、シート型は1面で情報を表示します。</p>
            </div>
            <div class="form-group">
                <label for="member_card_code_type" class="form-label">会員番号の表示</label>
                <select name="member_card_code_type" id="member_card_code_type" class="form-control">
                    <option value="barcode" {{ old('member_card_code_type', $view->memberCardCodeType ?? 'barcode') === 'barcode' ? 'selected' : '' }}>バーコード</option>
                    <option value="qrcode" {{ old('member_card_code_type', $view->memberCardCodeType ?? '') === 'qrcode' ? 'selected' : '' }}>QRコード</option>
                </select>
                <p class="form-text text-muted">会員番号をバーコードまたはQRコードで表示します。読み取り端末に合わせて選択してください。</p>
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

    @php
        $previewDesign = old('member_card_design', $view->memberCardDesign ?? 'default');
        $previewLogoUrl = $view->memberCardLogoUrl ?? null;
        $previewCodeType = old('member_card_code_type', $view->memberCardCodeType ?? 'barcode');
        $codePlaceholder = $previewCodeType === 'qrcode' ? '会員証QRコード' : '会員証バーコード';
        $sampleRank = $view->rankSettings[0] ?? ['key' => 'gold', 'label' => 'ゴールド', 'card_color' => '#ffd700', 'icon_url' => null];
        $sampleKey = $sampleRank['key'] ?? 'gold';
        $sampleLabel = $sampleRank['label'] ?? 'ゴールド';
        $sampleColor = $sampleRank['card_color'] ?? '#ffd700';
        $sampleIconUrl = $sampleRank['icon_url'] ?? null;
        $designsForCompare = [
            ['value' => 'default', 'label' => '標準'],
            ['value' => 'logo_top', 'label' => 'ロゴ上部'],
            ['value' => 'logo_center', 'label' => 'ロゴ中央'],
            ['value' => 'logo_header', 'label' => 'ロゴヘッダー'],
            ['value' => 'compact', 'label' => 'コンパクト'],
        ];
    @endphp
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-image"></i> 会員証デザイン比較</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted mb-3">各デザインの見た目です。タップするとそのデザインが選択されます。同じサンプルデータ（ランク: {{ e($sampleLabel) }}）で表示しています。</p>
            <div class="rank-card-preview-wrap rank-card-preview-wrap--compare">
                <div class="rank-card-preview-grid rank-card-preview-grid--multiple" id="designCompareGrid">
                    @foreach($designsForCompare as $d)
                    @php
                        $designClass = 'idnect-card-wrapper--design-' . $d['value'];
                        $wrapperAttr = $sampleColor ? ' data-rank-color="true" style="--idnect-card-rank-color: ' . e($sampleColor) . '"' : '';
                        $isSelected = $previewDesign === $d['value'];
                    @endphp
                    <div class="rank-card-preview-item {{ $isSelected ? 'rank-card-preview-item--selected' : '' }}" data-design="{{ e($d['value']) }}" role="button" tabindex="0" aria-pressed="{{ $isSelected ? 'true' : 'false' }}" aria-label="デザイン「{{ e($d['label']) }}」を選択">
                        <div class="idnect-card-wrapper {{ $designClass }}" data-rank-key="{{ e($sampleKey) }}"{!! $wrapperAttr !!}>
                            <div class="idnect-card" data-rank="{{ e($sampleKey) }}">
                                <div class="idnect-card-shimmer" aria-hidden="true"></div>
                                <div class="idnect-card-face idnect-card-face--front">
                                    <div class="idnect-card-front-inner">
                                        @if(in_array($d['value'], ['logo_top', 'logo_center', 'logo_header']))
                                        <div class="idnect-card-logo-wrap">
                                            @if(!empty($previewLogoUrl))
                                            <img src="{{ $previewLogoUrl }}" class="idnect-card-logo" alt="" onerror="this.nextElementSibling?.classList.remove('d-none')">
                                            @endif
                                            <span class="idnect-card-logo-placeholder {{ !empty($previewLogoUrl) ? 'd-none' : '' }}">ロゴ</span>
                                        </div>
                                        @endif
                                        <p class="idnect-card-display-name">会員名サンプル</p>
                                        <div class="idnect-card-meta-row">
                                            <div class="idnect-card-rank-wrap">
                                                @if(!empty($sampleIconUrl))
                                                <img src="{{ $sampleIconUrl }}" class="idnect-card-rank-icon" alt="" onerror="this.style.display='none'">
                                                @endif
                                                <span class="idnect-card-rank-badge" data-rank="{{ e($sampleKey) }}">{{ e($sampleLabel) }}</span>
                                            </div>
                                            <div class="idnect-card-points idnect-card-points--front">
                                                <span class="idnect-card-points-value">1,234</span>
                                                <span class="idnect-card-points-label">pt</span>
                                            </div>
                                        </div>
                                        <div class="idnect-card-barcode-wrap idnect-card-code-wrap--{{ e($previewCodeType) }}">
                                            <span class="idnect-card-barcode-placeholder">{{ $codePlaceholder }}</span>
                                        </div>
                                        <p class="idnect-card-tap-hint"><i class="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true"></i> タップで裏面を見る</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="rank-card-preview-label">{{ e($d['label']) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-palette"></i> 選択中のデザインで各ランクを表示</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted mb-3">現在選択中の「会員証デザイン」で、各ランクのカード色・表示名・アイコンをプレビューします。</p>
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
                    <div class="rank-card-preview-item" data-rank-index="{{ $loop->index }}">
                        <div class="idnect-card-wrapper {{ $designClass }}" data-rank-key="{{ e($key) }}"{!! $wrapperAttr !!}>
                            <div class="idnect-card" data-rank="{{ e($key) }}">
                                <div class="idnect-card-shimmer" aria-hidden="true"></div>
                                <div class="idnect-card-face idnect-card-face--front">
                                    <div class="idnect-card-front-inner">
                                        <div class="idnect-card-logo-wrap">
                                            @if(!empty($previewLogoUrl))
                                            <img src="{{ $previewLogoUrl }}" class="idnect-card-logo" alt="" onerror="this.nextElementSibling?.classList.remove('d-none')">
                                            @endif
                                            <span class="idnect-card-logo-placeholder {{ !empty($previewLogoUrl) ? 'd-none' : '' }}">ロゴ</span>
                                        </div>
                                        <p class="idnect-card-display-name">会員名サンプル</p>
                                        <div class="idnect-card-meta-row">
                                            <div class="idnect-card-rank-wrap">
                                                @if(!empty($iconUrl))
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
                                            <span class="idnect-card-barcode-placeholder">{{ $codePlaceholder }}</span>
                                        </div>
                                        <p class="idnect-card-tap-hint"><i class="fas fa-sync-alt idnect-card-tap-hint-icon" aria-hidden="true"></i> タップで裏面を見る</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="rank-card-preview-label">{{ e($label) }}（{{ e($key) }}）</span>
                    </div>
                    @endforeach
                </div>
            </div>
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

// ランク設定の入力変更時にプレビューを更新
(function() {
    const table = document.getElementById('rankSettingsTable');
    const grid = document.getElementById('rankCardPreviewGrid');
    if (!table || !grid) return;

    function updatePreview(index) {
        const item = grid.querySelector('.rank-card-preview-item[data-rank-index="' + index + '"]');
        if (!item) return;
        const tbody = table.querySelector('tbody');
        const row = tbody && tbody.rows[index];
        if (!row) return;
        const cells = row.cells;
        const keyInput = row.querySelector('input[name$="[key]"]');
        const labelInput = row.querySelector('input[name$="[label]"]');
        const colorInput = row.querySelector('input[name$="[card_color]"]');
        const iconInput = row.querySelector('input[name$="[icon_url]"]');
        const key = (keyInput && keyInput.value) ? keyInput.value.trim() : '';
        const label = (labelInput && labelInput.value) ? labelInput.value.trim() : key;
        const color = (colorInput && colorInput.value) ? colorInput.value.trim() : '';
        const iconUrl = (iconInput && iconInput.value) ? iconInput.value.trim() : '';

        const wrapper = item.querySelector('.idnect-card-wrapper');
        const card = item.querySelector('.idnect-card');
        const badge = item.querySelector('.idnect-card-rank-badge');
        const labelSpan = item.querySelector('.rank-card-preview-label');
        const iconImg = item.querySelector('.idnect-card-rank-icon');

        if (card) card.setAttribute('data-rank', key || 'bronze');
        if (badge) {
            badge.textContent = label || key || '—';
            badge.setAttribute('data-rank', key || 'bronze');
        }
        if (labelSpan) labelSpan.textContent = (label || key || '—') + (key ? '（' + key + '）' : '');

        if (wrapper) {
            if (color && /^#[0-9A-Fa-f]{3,6}$/.test(color)) {
                wrapper.setAttribute('data-rank-color', 'true');
                wrapper.style.setProperty('--idnect-card-rank-color', color);
            } else {
                wrapper.removeAttribute('data-rank-color');
                wrapper.style.removeProperty('--idnect-card-rank-color');
            }
        }

        const rankWrap = item.querySelector('.idnect-card-rank-wrap');
        if (rankWrap) {
            if (iconUrl) {
                let img = iconImg;
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
        const name = e.target && e.target.getAttribute('name');
        if (!name || name.indexOf('ranks[') !== 0) return;
        const m = name.match(/^ranks\[(\d+)\]/);
        if (m) updatePreview(parseInt(m[1], 10));
    });
    table.addEventListener('change', function(e) {
        const name = e.target && e.target.getAttribute('name');
        if (!name || name.indexOf('ranks[') !== 0) return;
        const m = name.match(/^ranks\[(\d+)\]/);
        if (m) updatePreview(parseInt(m[1], 10));
    });
})();

// 会員証デザイン変更時：選択中デザインで各ランクを表示のプレビューを更新＋比較エリアの選択状態
function updatePreviewDesign(design) {
    design = design || 'default';
    const designSelect = document.getElementById('member_card_design');
    const rankWrap = document.querySelector('.rank-card-preview-wrap[data-preview-design]');
    const rankWrappers = document.querySelectorAll('#rankCardPreviewGrid .idnect-card-wrapper');
    const designClasses = ['default', 'logo_top', 'logo_center', 'logo_header', 'compact'];
    if (designSelect) designSelect.value = design;
    if (rankWrap) rankWrap.setAttribute('data-preview-design', design);
    if (rankWrappers.length) {
        rankWrappers.forEach(function(w) {
            designClasses.forEach(function(c) { w.classList.remove('idnect-card-wrapper--design-' + c); });
            w.classList.add('idnect-card-wrapper--design-' + design);
        });
    }
    document.querySelectorAll('#designCompareGrid .rank-card-preview-item').forEach(function(item) {
        const isSelected = item.getAttribute('data-design') === design;
        item.classList.toggle('rank-card-preview-item--selected', isSelected);
        item.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
    });
}

(function() {
    const designSelect = document.getElementById('member_card_design');
    const rankWrap = document.querySelector('.rank-card-preview-wrap[data-preview-design]');
    const rankWrappers = document.querySelectorAll('#rankCardPreviewGrid .idnect-card-wrapper');
    if (!designSelect || !rankWrap || !rankWrappers.length) return;
    designSelect.addEventListener('change', function() { updatePreviewDesign(this.value); });
})();

// デザイン比較エリア：タップでデザインを選択
(function() {
    const grid = document.getElementById('designCompareGrid');
    if (!grid) return;
    grid.querySelectorAll('.rank-card-preview-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const design = this.getAttribute('data-design');
            if (design) updatePreviewDesign(design);
        });
        item.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const design = this.getAttribute('data-design');
                if (design) updatePreviewDesign(design);
            }
        });
    });
})();

// 会員番号表示（バーコード/QRコード）変更時：プレビュー文言を更新
(function() {
    const codeSelect = document.getElementById('member_card_code_type');
    if (!codeSelect) return;
    codeSelect.addEventListener('change', function() {
        const val = this.value;
        const text = val === 'qrcode' ? '会員証QRコード' : '会員証バーコード';
        document.querySelectorAll('.rank-card-preview-wrap .idnect-card-barcode-placeholder').forEach(function(el) { el.textContent = text; });
        document.querySelectorAll('.rank-card-preview-wrap .idnect-card-barcode-wrap').forEach(function(wrap) {
            wrap.classList.remove('idnect-card-code-wrap--barcode', 'idnect-card-code-wrap--qrcode');
            wrap.classList.add('idnect-card-code-wrap--' + val);
        });
    });
})();
</script>
@endsection
