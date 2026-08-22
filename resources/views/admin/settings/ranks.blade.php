@extends('admin.layout')

@section('title', 'ランク・ポイント設定')
@section('page-title', 'ランク・ポイント設定')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/rank-card-preview.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/liff-membership-preview.css') }}">
@endpush

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>ランク・ポイント</span>
</nav>

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
                    <p class="ranks-settings-lead">会員コード、機能の利用可否、ランクの判定方法、ポイント／スタンプの付与を設定します。</p>

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
                    <p class="ranks-settings-lead">会員証の表示内容とデザインを設定します。</p>

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
                        <h4 class="ranks-settings-subtitle">デザイン・表示形式</h4>
                        <p class="form-text text-muted" style="margin-top:0;">会員証レイアウトは「プレビュー」タブで一覧から選べます。こちらでは表示形式を設定します。</p>
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
                        <button type="button" class="btn btn-secondary btn-sm" id="ranksGotoDesignPreview">
                            <i class="fas fa-id-card"></i> プレビューでデザインを選ぶ
                        </button>
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
                    <p class="ranks-settings-lead">会員のランクを定義します。基準値は判定指標の累計または期間内獲得数です。付与レートが未設定のランクにはデフォルト値が入ります。</p>
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

        {{-- プレビュー --}}
        @php
            $previewDesign = old('member_card_design', $view->memberCardDesign ?? 'default');
            $previewTemplate = \App\Support\MemberCardDesignMap::templateForDesign($previewDesign) ?? 'classic';
            $designLabelMap = [
                'default' => 'スタンダード',
                'logo_top' => 'ポイント',
                'logo_center' => 'ロゴ中央',
                'logo_header' => 'レジ提示',
                'compact' => 'シンプル',
                'ticket' => 'チケット',
                'split_banner' => 'スプリット',
                'minimal_line' => 'ミニマル',
                'emblem' => 'デジタルパス',
                'wallet' => 'ウォレット',
            ];
            $previewDesignLabel = $designLabelMap[$previewDesign] ?? 'スタンダード';
            $allTemplates = ['classic', 'simple', 'ticket', 'minimal', 'split', 'barcodeFocus', 'pointFocus', 'wallet', 'pass'];
        @endphp
        <section class="ranks-settings-panel" id="panel-preview" role="tabpanel" aria-labelledby="tab-preview" hidden>
            <div class="card card-mb-24 ranks-preview-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-image"></i> 会員証プレビュー</h3>
                </div>
                <div class="card-body">
                    <div class="ranks-preview-section">
                        <h4 class="ranks-preview-section-title">会員証デザインを選択</h4>
                        <p class="ranks-preview-desc">お客様向け画面と同じテンプレートです。サイズ・レイアウトの違いを確認して選んでください。</p>
                        @include('admin.settings._member_card_design_picker')
                    </div>

                    <div class="ranks-preview-section" style="margin-top:24px;">
                        <h4 class="ranks-preview-section-title">ランク別のカード色を確認</h4>
                        <p class="ranks-preview-desc">選択中「<span id="ranksPreviewDesignLabel">{{ e($previewDesignLabel) }}</span>」で、各ランクの色が会員証にどう映るかを確認できます。</p>
                        <div class="rank-liff-preview-grid" id="rankCardPreviewGrid" data-preview-design="{{ e($previewDesign) }}" data-preview-template="{{ e($previewTemplate) }}">
                            @foreach($view->rankSettings as $rank)
                            @php
                                $key = $rank['key'] ?? 'bronze';
                                $label = $rank['label'] ?? $key;
                                $cardColor = $rank['card_color'] ?? '#4b5563';
                                if (!is_string($cardColor) || $cardColor === '') {
                                    $cardColor = '#4b5563';
                                }
                            @endphp
                            <div class="rank-liff-preview-item" data-rank-index="{{ $loop->index }}">
                                <div class="js-rank-liff-card-host">
                                    @include('admin.settings._liff_membership_card_preview', [
                                        'template' => $previewTemplate,
                                        'rankLabel' => $label,
                                        'cardColor' => $cardColor,
                                        'points' => 1234,
                                        'showNextRank' => true,
                                        'nextRankLabel' => '次のランクまで 766 pt',
                                    ])
                                </div>
                                <span class="rank-liff-preview-label"><span class="js-rank-preview-design-label">{{ e($previewDesignLabel) }}</span> / <span class="js-rank-preview-rank-label">{{ e($label) }}</span> <span class="rank-liff-preview-key">（{{ e($key) }}）</span></span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="ranks-preview-section ranks-preview-extras-section" style="margin-top:28px;">
                        <h4 class="ranks-preview-section-title">カード下の付属表示</h4>
                        <p class="ranks-preview-desc">ホームの会員ステータスと、会員証ページの会員情報です。「ポイント表示」「次のランクまで」の設定に連動します。</p>
                        <div class="liff-membership-extras js-rank-liff-extras ranks-preview-extras-stage">
                            <div class="liff-preview-summary">
                                <div class="liff-preview-summary__top">
                                    <div>
                                        <div class="liff-preview-summary__label">MEMBER STATUS</div>
                                        <h2 class="liff-preview-summary__name js-preview-rank">ゴールド</h2>
                                    </div>
                                    <span class="liff-preview-summary__member">山田 太郎 様</span>
                                </div>
                                <div class="liff-preview-summary__body">
                                    <div>
                                        <div class="liff-preview-summary__points js-extras-points">1,234 <span>pt</span></div>
                                        <div class="liff-preview-summary__meta js-extras-next-rank">次のランクまで 766 pt</div>
                                    </div>
                                </div>
                                <div class="liff-preview-summary__track js-extras-track" aria-hidden="true"><span style="width:41%"></span></div>
                            </div>
                            <div class="liff-preview-detail">
                                <h3 class="liff-preview-detail__title">会員情報</h3>
                                <div class="liff-preview-detail__grid">
                                    <div class="liff-preview-detail__row">
                                        <div class="liff-preview-detail__label">会員番号</div>
                                        <div class="liff-preview-detail__value">1000001234</div>
                                    </div>
                                    <div class="liff-preview-detail__row">
                                        <div class="liff-preview-detail__label">有効期限</div>
                                        <div class="liff-preview-detail__value">2027/12/31</div>
                                    </div>
                                    <div class="liff-preview-detail__row js-extras-points-row">
                                        <div class="liff-preview-detail__label">現在ポイント</div>
                                        <div class="liff-preview-detail__value">1,234 pt</div>
                                    </div>
                                    <div class="liff-preview-detail__row">
                                        <div class="liff-preview-detail__label">ランク</div>
                                        <div class="liff-preview-detail__value js-preview-rank-detail">ゴールド</div>
                                    </div>
                                    <div class="liff-preview-detail__row js-extras-next-rank-row" style="grid-column:1/-1">
                                        <div class="liff-preview-detail__label">次のランク</div>
                                        <div class="liff-preview-detail__value">次のランクまで 766 pt</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- デザイン切替用テンプレート庫 --}}
            <div id="liffPreviewCardLibrary" hidden aria-hidden="true">
                @foreach($allTemplates as $libTemplate)
                    <div data-library-template="{{ $libTemplate }}">
                        @include('admin.settings._liff_membership_card_preview', [
                            'template' => $libTemplate,
                            'rankLabel' => '__RANK__',
                            'cardColor' => '#4b5563',
                            'points' => 1234,
                            'showNextRank' => true,
                            'nextRankLabel' => '次のランクまで 766 pt',
                        ])
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="form-actions form-actions-ranks">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.settings.index') }}" class="btn btn-secondary">戻る</a>
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
        document.querySelectorAll('.js-rank-preview-points').forEach(function(el) {
            el.classList.toggle('is-hidden', !pointsOn);
        });
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
    var table = document.getElementById('rankSettingsTable');
    var grid = document.getElementById('rankCardPreviewGrid');
    if (!table || !grid) return;

    function applyCardColor(card, color) {
        if (!card) return;
        var template = card.getAttribute('data-template') || 'classic';
        var isLight = template === 'simple' || template === 'ticket';
        var safeColor = color || card.getAttribute('data-card-color') || '#4b5563';
        card.setAttribute('data-card-color', safeColor);
        if (isLight) {
            card.style.background = template === 'ticket' ? '#fffef8' : '#ffffff';
            card.style.color = '#171717';
            return;
        }
        card.style.background = 'linear-gradient(135deg,' + safeColor + ',#1f2937)';
        card.style.color = '#ffffff';
    }

    function updatePreview(index) {
        var item = grid.querySelector('.rank-liff-preview-item[data-rank-index="' + index + '"]');
        if (!item) return;
        var tbody = table.querySelector('tbody');
        var row = tbody && tbody.rows[index];
        if (!row) return;
        var keyInput = row.querySelector('input[name$="[key]"]');
        var labelInput = row.querySelector('input[name$="[label]"]');
        var colorInput = row.querySelector('input[name$="[card_color]"]');
        var key = (keyInput && keyInput.value) ? keyInput.value.trim() : '';
        var label = (labelInput && labelInput.value) ? labelInput.value.trim() : key;
        var color = (colorInput && colorInput.value) ? colorInput.value.trim() : '';
        var rankText = label || key || 'MEMBER';

        applyCardColor(item.querySelector('.liff-membership-preview'), color);
        item.querySelectorAll('.rank-badge.js-preview-rank').forEach(function(el) {
            el.textContent = rankText + ' MEMBER';
        });
        var rankLabel = item.querySelector('.js-rank-preview-rank-label');
        if (rankLabel) rankLabel.textContent = rankText;
        var keySpan = item.querySelector('.rank-liff-preview-key');
        if (keySpan) keySpan.textContent = key ? '（' + key + '）' : '';

        if (index === 0) {
            document.querySelectorAll('.ranks-preview-extras-stage .liff-preview-summary__name.js-preview-rank').forEach(function(el) {
                el.textContent = rankText;
            });
            document.querySelectorAll('.ranks-preview-extras-stage .js-preview-rank-detail').forEach(function(el) {
                el.textContent = rankText;
            });
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

    grid.querySelectorAll('.rank-liff-preview-item[data-rank-index]').forEach(function(item) {
        var idx = parseInt(item.getAttribute('data-rank-index') || '', 10);
        if (!isNaN(idx)) updatePreview(idx);
    });

    window.__ranksUpdatePreview = updatePreview;
})();

function syncMembershipExtrasVisibility() {
    var usePoints = document.getElementById('use_points');
    var showPoints = document.querySelector('input[name="show_points_display"][value="1"]');
    var showProgress = document.querySelector('input[name="show_rank_progress"][value="1"]');
    var pointsOn = usePoints === null || usePoints.checked;
    var showPointsOn = pointsOn && (showPoints === null || showPoints.checked);
    var showProgressOn = showProgress === null || showProgress.checked;

    document.querySelectorAll('.js-extras-points, .js-extras-points-row, .js-rank-preview-points').forEach(function(el) {
        if (showPointsOn) {
            el.removeAttribute('hidden');
            el.classList.remove('is-hidden');
        } else {
            el.setAttribute('hidden', 'hidden');
            el.classList.add('is-hidden');
        }
    });
    document.querySelectorAll('.js-extras-next-rank, .js-extras-next-rank-row, .js-extras-track, .js-preview-next-rank').forEach(function(el) {
        if (showProgressOn) {
            el.removeAttribute('hidden');
        } else {
            el.setAttribute('hidden', 'hidden');
        }
    });
}

function updatePreviewDesign(design) {
    design = design || 'default';
    var designInputs = document.querySelectorAll('input[name="member_card_design"]');
    var grid = document.getElementById('rankCardPreviewGrid');
    var library = document.getElementById('liffPreviewCardLibrary');
    var designLabels = {
        default: 'スタンダード',
        logo_top: 'ポイント',
        logo_center: 'ロゴ中央',
        logo_header: 'レジ提示',
        compact: 'シンプル',
        ticket: 'チケット',
        split_banner: 'スプリット',
        minimal_line: 'ミニマル',
        emblem: 'デジタルパス',
        wallet: 'ウォレット'
    };
    var template = 'classic';
    designInputs.forEach(function(input) {
        if (input.value === design) {
            input.checked = true;
            template = input.getAttribute('data-template') || 'classic';
        }
        var option = input.closest('.ranks-design-option');
        if (option) {
            option.classList.toggle('is-selected', input.value === design);
        }
    });
    var label = designLabels[design] || 'スタンダード';
    if (grid) {
        grid.setAttribute('data-preview-design', design);
        grid.setAttribute('data-preview-template', template);
    }
    var titleLabel = document.getElementById('ranksPreviewDesignLabel');
    if (titleLabel) titleLabel.textContent = label;
    document.querySelectorAll('.js-rank-preview-design-label').forEach(function(el) {
        el.textContent = label;
    });

    if (!grid || !library) return;
    var source = library.querySelector('[data-library-template="' + template + '"] .liff-membership-preview');
    if (!source) return;
    grid.querySelectorAll('.rank-liff-preview-item').forEach(function(item) {
        var host = item.querySelector('.js-rank-liff-card-host');
        if (!host) return;
        var clone = source.cloneNode(true);
        host.innerHTML = '';
        host.appendChild(clone);
        var idx = parseInt(item.getAttribute('data-rank-index') || '', 10);
        if (!isNaN(idx) && typeof window.__ranksUpdatePreview === 'function') {
            window.__ranksUpdatePreview(idx);
        }
    });
    syncMembershipExtrasVisibility();
}

(function() {
    var designInputs = document.querySelectorAll('input[name="member_card_design"]');
    if (!designInputs.length) return;
    designInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.checked) updatePreviewDesign(this.value);
        });
        input.addEventListener('click', function() {
            updatePreviewDesign(this.value);
        });
    });
})();

(function() {
    var showPoints = document.querySelector('input[name="show_points_display"][value="1"]');
    var showProgress = document.querySelector('input[name="show_rank_progress"][value="1"]');
    var usePoints = document.getElementById('use_points');
    if (showPoints) showPoints.addEventListener('change', syncMembershipExtrasVisibility);
    if (showProgress) showProgress.addEventListener('change', syncMembershipExtrasVisibility);
    if (usePoints) usePoints.addEventListener('change', syncMembershipExtrasVisibility);
    syncMembershipExtrasVisibility();
})();

(function() {
    var gotoBtn = document.getElementById('ranksGotoDesignPreview');
    var previewTab = document.getElementById('tab-preview');
    if (!gotoBtn || !previewTab) return;
    gotoBtn.addEventListener('click', function() {
        previewTab.click();
        var picker = document.querySelector('.ranks-design-picker');
        if (picker) picker.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
})();

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
