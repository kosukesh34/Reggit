@extends('admin.layout')
@section('title', '流入経路')
@section('page-title', '流入経路')

@section('content')
@include('admin.engagement._assets')
<div class="eg-page">
<p class="eg-lead">店頭POP・チラシ・レシートなど、経路ごとにQRコードを作ると、どこから会員登録したかを記録できます。登録した会員には「流入:経路名」のタグが付くため、経路別にセグメント配信できます。</p>

<section class="eg-card">
    <div class="eg-card-head">
        <span class="eg-icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
        <div><h2>経路を追加</h2><p>QRを読み取った人は、選んだ画面が開いた状態でアプリが始まります。</p></div>
    </div>
    <form action="{{ admin_route('admin.acquisition-sources.store') }}" method="POST">
        @csrf
        <div class="eg-row">
            <div class="eg-field">
                <label class="eg-label" for="name">経路名<span class="eg-required">必須</span></label>
                <input type="text" id="name" name="name" class="form-control" required maxlength="40" placeholder="例: 店頭POP" value="{{ old('name') }}">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="eg-field">
                <label class="eg-label" for="landing_tab">QRで開く画面</label>
                <select id="landing_tab" name="landing_tab" class="form-control">
                    @foreach($screens as $tab => $label)
                    <option value="{{ $tab }}" {{ old('landing_tab', 'home') === $tab ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="eg-field eg-field--action">
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> 追加</button>
            </div>
        </div>
    </form>
</section>

@if($sources === [])
    <section class="eg-card eg-hint--spaced">
        <div class="eg-empty">
            <i class="fas fa-route" aria-hidden="true"></i>
            <h3>まだ経路がありません</h3>
            <p>上のフォームで「店頭POP」などを追加すると、専用のQRコードができます。</p>
        </div>
    </section>
@else
<div class="eg-source-grid">
    @foreach($sources as $source)
    <section class="eg-card eg-share-qr">
        <div class="eg-card-head eg-card-head--compact">
            <span class="eg-icon eg-icon--orange"><i class="fas fa-qrcode" aria-hidden="true"></i></span>
            <div><h2>{{ $source->name }}</h2><p>開く画面：{{ $source->landingLabel }}</p></div>
        </div>
        <div class="eg-source-stats">
            <div><strong>{{ number_format($source->membersCount) }}</strong><small>登録した会員</small></div>
            <div><strong>{{ number_format($source->recentMembersCount) }}</strong><small>直近30日</small></div>
        </div>
        <div data-issued-qr data-url="{{ $source->url }}" data-filename="source-{{ $source->code }}.png" aria-label="{{ $source->name }}のQRコード"></div>
        <p class="eg-url">{{ $source->url }}</p>
        <div class="eg-actions">
            <button type="button" class="btn btn-primary" data-save-issued-qr disabled><i class="fas fa-download" aria-hidden="true"></i> QR画像を保存</button>
            <form action="{{ admin_route('admin.acquisition-sources.destroy', ['id' => $source->id]) }}" method="POST" class="delete-form" data-item-name="流入経路「{{ $source->name }}」（会員の登録記録は消え、付いたタグは残ります）">
                @csrf @method('DELETE')
                <button type="submit" class="eg-icon-button is-danger" aria-label="{{ $source->name }}を削除"><i class="fas fa-trash" aria-hidden="true"></i></button>
            </form>
        </div>
        <p class="text-danger" data-qr-error hidden role="alert"></p>
    </section>
    @endforeach
</div>
@endif
</div>
@endsection
