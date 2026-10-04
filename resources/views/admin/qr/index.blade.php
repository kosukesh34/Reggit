@extends('admin.layout')
@section('title', 'QRコード発行')
@section('page-title', 'QRコード発行')
@section('content')
<p class="text-muted">店頭のご案内や会員への特典付与に使うQRコードをまとめて管理できます。</p>
<div class="qr-grid">
    @foreach($view->links as $key => $link)
    <section class="card qr-card">
        <div class="card-header"><h2 class="card-title">{{ $link['title'] }}</h2></div>
        <div class="card-body">
            <p>{{ $link['description'] }}</p>
            @if($link['url'] !== '')
                <div data-issued-qr data-url="{{ $link['url'] }}" data-filename="{{ $key }}-{{ request()->route('project_key') }}.png" aria-label="{{ $link['title'] }}のQRコード"></div>
                <p class="qr-url">{{ $link['url'] }}</p>
                <button type="button" class="btn btn-primary" data-save-issued-qr disabled>QR画像を保存</button>
                <a class="btn btn-secondary" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">リンクを開く</a>
                <p class="text-danger" data-qr-error hidden role="alert"></p>
            @else
                <div class="qr-empty">URLが未設定です</div>
                <p class="text-muted">{{ $key === 'line' ? 'LINE連携設定でミニアプリURLを設定してください。' : 'システム管理者にダウンロードURLの設定を依頼してください。' }}</p>
            @endif
        </div>
    </section>
    @endforeach
    <section class="card qr-card">
        <div class="card-header"><h2 class="card-title">ポイント付与QR</h2></div>
        <div class="card-body">
            <p>指定ポイントを会員が受け取るQRです。同じ会員は1回だけ受け取れます。</p>
            @if(auth('admin')->user()->hasPermission('members.manage'))
            <form method="POST" action="{{ admin_route('admin.qr-codes.points.store') }}">
                @csrf
                @foreach(['points' => ['付与ポイント数', 100, 100000], 'expires_minutes' => ['QRの有効期間（分）', 60, 525600], 'usage_quota' => ['利用回数の上限（全会員の合計）', 1, 10000]] as $field => $config)
                <div class="form-group">
                    <label class="form-label" for="{{ $field }}">{{ $config[0] }}</label>
                    <input class="form-control" id="{{ $field }}" name="{{ $field }}" type="number" min="1" max="{{ $config[2] }}" value="{{ old($field, $config[1]) }}" required>
                    @error($field)<p class="text-danger">{{ $message }}</p>@enderror
                </div>
                @endforeach
                <p class="text-muted">受け取ったポイントの有効期限は店舗のポイント設定に従います。</p>
                <button type="submit" class="btn btn-primary">ポイント付与QRを発行</button>
            </form>
            @if($view->issuedPointCode !== null)
                @php($claimUrl = route('points.grant.open', ['project_key' => request()->route('project_key'), 'token' => $view->issuedPointCode->token]))
                <p>{{ $view->issuedPointCode->points }}ポイント / 有効期限 {{ $view->issuedPointCode->expires_at->format('Y/m/d H:i') }} / 利用上限 {{ $view->issuedPointCode->usage_quota }}回</p>
                <div data-issued-qr data-url="{{ $claimUrl }}" data-filename="points-{{ $view->issuedPointCode->id }}.png" aria-label="ポイント付与QRコード"></div>
                <p class="qr-url">{{ $claimUrl }}</p>
                <button type="button" class="btn btn-primary" data-save-issued-qr disabled>QR画像を保存</button>
                <p class="text-danger" data-qr-error hidden role="alert"></p>
                <p class="text-muted">画面を離れるとQRは表示されません。画像を保存してご利用ください。</p>
            @endif
            @else
                <p class="text-muted">ポイント付与QRの発行には会員管理権限が必要です。</p>
            @endif
        </div>
    </section>
    <section class="card qr-card">
        <div class="card-header"><h2 class="card-title">スタンプ付与QR</h2></div>
        <div class="card-body">
            <p>スタンプカードごとに付与数・有効期限・利用条件を指定して発行できます。</p>
            @if(auth('admin')->user()->hasPermission('stamps.manage'))
                @if($view->stampCards->isNotEmpty())
                <form method="POST" action="{{ admin_route('admin.qr-codes.stamps.store') }}">
                    @csrf
                    <div class="form-group"><label class="form-label" for="stamp_card_id">スタンプカード</label>
                        <select class="form-control" id="stamp_card_id" name="stamp_card_id" required>
                            @foreach($view->stampCards as $card)<option value="{{ $card->id }}" @selected((int) old('stamp_card_id') === $card->id)>{{ $card->name }}</option>@endforeach
                        </select>
                        @error('stamp_card_id')<p class="text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group"><label class="form-label" for="amount">付与スタンプ数</label><input class="form-control" id="amount" name="amount" type="number" min="1" max="{{ \App\Enums\StampGrantLimit::MAX_AMOUNT }}" value="{{ old('amount', 1) }}" required>@error('amount')<p class="text-danger">{{ $message }}</p>@enderror</div>
                    <div class="form-group"><label class="form-label" for="stamp_expires_minutes">QRの有効期間</label>
                        <select class="form-control" id="stamp_expires_minutes" name="expires_minutes">@foreach(\App\Enums\StampGrantLimit::EXPIRES_OPTIONS as $minutes => $label)<option value="{{ $minutes }}" @selected((int) old('expires_minutes', 60) === $minutes)>{{ $label }}</option>@endforeach</select>
                        @error('expires_minutes')<p class="text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group"><label class="form-label" for="limit">利用条件</label>
                        <select class="form-control" id="limit" name="limit">@foreach(\App\Enums\StampGrantLimit::getValues() as $value => $label)<option value="{{ $value }}" @selected(old('limit', \App\Enums\StampGrantLimit::PER_MEMBER) === $value)>{{ $label }}</option>@endforeach</select>
                        @error('limit')<p class="text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group"><label class="form-label" for="quota">先着人数（先着○名の場合）</label><input class="form-control" id="quota" name="quota" type="number" min="1" max="{{ \App\Enums\StampGrantLimit::MAX_QUOTA }}" value="{{ old('quota', 10) }}" required>@error('quota')<p class="text-danger">{{ $message }}</p>@enderror</div>
                    <button type="submit" class="btn btn-primary">スタンプ付与QRを発行</button>
                </form>
                @else
                    <p class="text-muted">有効なスタンプカードがありません。スタンプ管理でカードを作成してください。</p>
                @endif
                @if($view->issuedStampCode !== null)
                    <p>{{ $view->issuedStampCode->amount }}個付与 / {{ \App\Enums\StampGrantLimit::displayLabel($view->issuedStampCode->usage_limit, $view->issuedStampCode->usage_quota) }}</p>
                    <div data-issued-qr data-url="{{ $view->issuedStampCode->openUrl() }}" data-filename="stamps-{{ $view->issuedStampCode->id }}.png" aria-label="スタンプ付与QRコード"></div>
                    <p class="qr-url">{{ $view->issuedStampCode->openUrl() }}</p>
                    <button type="button" class="btn btn-primary" data-save-issued-qr disabled>QR画像を保存</button>
                    <p class="text-danger" data-qr-error hidden role="alert"></p>
                    <p class="text-muted">画面を離れるとQRは表示されません。画像を保存してご利用ください。</p>
                @endif
            @else
                <p class="text-muted">スタンプ付与QRの発行にはスタンプ管理権限が必要です。</p>
            @endif
        </div>
    </section>
</div>
@endsection
@push('styles')
<style>
.qr-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr)); gap:20px; }
.qr-card { margin:0; }
[data-issued-qr] { display:grid; place-items:center; min-height:240px; padding:16px; background:white; }
[data-issued-qr] canvas { max-width:100%; height:auto!important; }
.qr-url { overflow-wrap:anywhere; color:#64748b; font-size:13px; }
.qr-empty { padding:48px 16px; background:#f8fafc; border-radius:12px; text-align:center; margin:16px 0; }
.qr-stamp-link { display:block; margin:10px 0; }
</style>
@endpush
