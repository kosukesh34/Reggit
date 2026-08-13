@extends('admin.layout')

@section('title', 'スタンプ付与QR')
@section('page-title', 'スタンプ付与QR')

@push('styles')
<style>
    .stamp-grant-form { margin-bottom: 24px; }
    .stamp-grant-settings { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .stamp-grant-limit { grid-column: 1 / -1; }
    .stamp-grant-daily { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 14px 16px; border: 1px solid #dbe4ee; border-radius: 14px; background: #f8fafc; }
    .stamp-grant-daily strong { display: block; font-size: 14px; }
    .stamp-grant-daily p { margin: 4px 0 0; color: #64748b; font-size: 13px; line-height: 1.55; }
    .stamp-grant-switch { position: relative; display: inline-block; width: 48px; height: 28px; flex-shrink: 0; }
    .stamp-grant-switch input { opacity: 0; width: 0; height: 0; }
    .stamp-grant-switch span { position: absolute; inset: 0; cursor: pointer; background: #cbd5e1; border-radius: 999px; transition: background .15s; }
    .stamp-grant-switch span::after { content: ""; position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; background: #fff; border-radius: 50%; transition: transform .15s; }
    .stamp-grant-switch input:checked + span { background: #0f766e; }
    .stamp-grant-switch input:checked + span::after { transform: translateX(20px); }
    .stamp-grant-other { display: grid; gap: 8px; margin-top: 12px; }
    .stamp-grant-limit-option { display: grid; grid-template-columns: 18px minmax(0, 1fr); gap: 10px; align-items: start; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; cursor: pointer; }
    .stamp-grant-limit-option strong { display: block; font-size: 14px; }
    .stamp-grant-limit-option small { display: block; margin-top: 4px; color: #64748b; line-height: 1.5; }
    .stamp-grant-quota { width: 4.8em; margin: 0 4px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 700; }
    .stamp-grant-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .stamp-grant-issued { display: grid; grid-template-columns: minmax(220px, 280px) minmax(0, 1fr); gap: 20px; align-items: start; }
    .stamp-grant-qr { display: grid; place-items: center; min-height: 240px; padding: 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; }
    .stamp-grant-qr canvas { width: min(100%, 240px) !important; height: auto !important; }
    .stamp-grant-url { width: 100%; min-height: 72px; resize: vertical; word-break: break-all; }
    .stamp-grant-note { margin-top: 12px; padding: 12px; color: #475569; background: #f8fafc; border-radius: 10px; line-height: 1.65; }
    .stamp-grant-empty { padding: 24px; color: #64748b; }
    .stamp-grant-history { width: 100%; }
    @media (max-width: 800px) {
        .stamp-grant-settings { grid-template-columns: 1fr; }
        .stamp-grant-issued { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>付与QR</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-qrcode"></i> スタンプ付与QR</h1>
    <p class="page-header-desc">{{ $stampCard->name }}の付与QRをその都度発行します。QR画像は発行直後だけ表示され、履歴には条件だけが残ります。</p>
</div>

<form method="POST" action="{{ admin_route('admin.stamps.grant-qr.store', ['id' => $stampCard->id]) }}" class="card stamp-grant-form">
    @csrf
    <div class="card-header"><h2 class="card-title">新しいQRを発行</h2></div>
    <div class="card-body">
        <div class="stamp-grant-settings">
            <div class="form-group">
                <label for="expires_minutes" class="form-label">QRの有効期限</label>
                <select name="expires_minutes" id="expires_minutes" class="form-control">
                    @foreach(\App\Enums\StampGrantLimit::EXPIRES_OPTIONS as $minutes => $label)
                        <option value="{{ $minutes }}" {{ (int) $expiresMinutes === (int) $minutes ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="amount" class="form-label">付与する個数</label>
                <input type="number" name="amount" id="amount" class="form-control" min="1" max="{{ \App\Enums\StampGrantLimit::MAX_AMOUNT }}" value="{{ $amount }}" required>
            </div>
            @php
                $dailyCase = \App\Enums\StampGrantLimit::useCases()[\App\Enums\StampGrantLimit::DAILY];
                $isDailyLimit = $limit === \App\Enums\StampGrantLimit::DAILY;
            @endphp
            <div class="form-group stamp-grant-limit" data-stamp-grant-limit>
                <label class="form-label">利用上限</label>
                <div class="stamp-grant-daily">
                    <div>
                        <strong>{{ $dailyCase['label'] }}</strong>
                        <p>{{ $dailyCase['description'] }}</p>
                        <p>例）{{ $dailyCase['example'] }}</p>
                    </div>
                    <label class="stamp-grant-switch">
                        <input type="checkbox" data-stamp-grant-daily-toggle {{ $isDailyLimit ? 'checked' : '' }} aria-label="1日1回までに制限する">
                        <span></span>
                    </label>
                </div>
                <input type="hidden" name="limit" value="{{ $limit }}" data-stamp-grant-limit-value>
                <div class="stamp-grant-other" data-stamp-grant-other {{ $isDailyLimit ? 'hidden' : '' }}>
                    @foreach(\App\Enums\StampGrantLimit::useCases() as $value => $useCase)
                        @if($value === \App\Enums\StampGrantLimit::DAILY)
                            @continue
                        @endif
                        <label class="stamp-grant-limit-option">
                            <input type="radio" data-stamp-grant-other-limit value="{{ $value }}" {{ ($isDailyLimit === false && $limit === $value) || ($isDailyLimit && $value === \App\Enums\StampGrantLimit::PER_MEMBER) ? 'checked' : '' }}>
                            <span>
                                @if($value === \App\Enums\StampGrantLimit::ONCE)
                                    <strong>先着<input type="number" name="quota" class="stamp-grant-quota" min="1" max="{{ \App\Enums\StampGrantLimit::MAX_QUOTA }}" value="{{ $quota }}" data-stamp-grant-quota aria-label="先着人数">名</strong>
                                @else
                                    <strong>{{ $useCase['label'] }}</strong>
                                @endif
                                <small>{{ $useCase['description'] }} 例）{{ $useCase['example'] }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="stamp-grant-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> QRを発行</button>
        </div>
    </div>
</form>

@if($issuedCode !== null)
    @php
        $openUrl = $issuedCode->openUrl();
        $limitNote = \App\Enums\StampGrantLimit::displayLabel($issuedCode->usage_limit, (int) $issuedCode->usage_quota);
        $expiresNote = $issuedCode->isExpired()
            ? '期限切れ'
            : ($issuedCode->expires_at === null
                ? '無期限'
                : $issuedCode->expires_at->format('Y/m/d H:i').'まで');
    @endphp
    <div class="card">
        <div class="card-header"><h2 class="card-title">今回発行したQR</h2></div>
        <div class="card-body">
            <div class="stamp-grant-issued">
                <div class="stamp-grant-qr" data-stamp-grant-qr data-url="{{ $openUrl }}" aria-label="スタンプ付与QRコード"></div>
                <div>
                    <label class="form-label" for="stampGrantUrlIssued">読み取り用URL</label>
                    <textarea id="stampGrantUrlIssued" class="form-control stamp-grant-url" readonly>{{ $openUrl }}</textarea>
                    <div class="stamp-grant-actions">
                        <button type="button" class="btn btn-primary" data-download-stamp-grant-qr data-url="{{ $openUrl }}" data-filename="stamp-grant-{{ $stampCard->id }}-{{ $issuedCode->id }}.png"><i class="fas fa-download"></i> QRをダウンロード</button>
                        <button type="button" class="btn btn-outline" data-copy-stamp-grant-url data-copy-target="stampGrantUrlIssued"><i class="fas fa-copy"></i> URLをコピー</button>
                    </div>
                    <p class="stamp-grant-note">この画面を離れるとQRは表示されません。印刷や保存が必要なら今ダウンロードしてください。<br>{{ $expiresNote }} / 1回 {{ $issuedCode->amount }} 個 / {{ $limitNote }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header"><h2 class="card-title">発行履歴</h2></div>
    <div class="card-body">
        @if($codes->isEmpty())
            <p class="stamp-grant-empty">まだQRはありません。上の条件で発行してください。</p>
        @else
            <table class="table stamp-grant-history">
                <thead>
                    <tr>
                        <th>発行日時</th>
                        <th>個数</th>
                        <th>利用上限</th>
                        <th>有効期限</th>
                        <th>利用</th>
                        <th class="table-actions-cell">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($codes as $code)
                        @php
                            $openUrl = $code->openUrl();
                            $limitNote = \App\Enums\StampGrantLimit::displayLabel($code->usage_limit, (int) $code->usage_quota);
                            $expiresNote = $code->isExpired()
                                ? '期限切れ'
                                : ($code->expires_at === null
                                    ? '無期限'
                                    : $code->expires_at->format('Y/m/d H:i').'まで');
                            $usedCount = (int) ($code->redemptions_count ?? 0);
                            $quotaNote = \App\Enums\StampGrantLimit::normalizeQuota((int) $code->usage_quota);
                            $usedLabel = $code->isExpired()
                                ? '期限切れ'
                                : ($code->usage_limit === \App\Enums\StampGrantLimit::ONCE
                                    ? $usedCount.' / '.$quotaNote.'名'
                                    : ($usedCount > 0 ? $usedCount.'件利用' : '未使用'));
                        @endphp
                        <tr>
                            <td>{{ $code->created_at?->format('Y/m/d H:i') }}</td>
                            <td>1回 {{ $code->amount }} 個</td>
                            <td>{{ $limitNote }}</td>
                            <td>{{ $expiresNote }}</td>
                            <td>{{ $usedLabel }}</td>
                            <td class="table-actions-cell">
                                <button type="button" class="btn btn-action btn-outline" data-download-stamp-grant-qr data-url="{{ $openUrl }}" data-filename="stamp-grant-{{ $stampCard->id }}-{{ $code->id }}.png"><i class="fas fa-download"></i> ダウンロード</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
