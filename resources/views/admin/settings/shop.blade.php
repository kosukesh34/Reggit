@extends('admin.layout')

@section('title', 'ショップ設定')
@section('page-title', 'ショップ設定')

@section('content')
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>ショップ</span>
</nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-store"></i> ショップ設定</h1>
</div>

@if($view->shopEnabled)
@php
    $shopQrUrl = $view->customDomainUrl !== '' ? $view->customDomainUrl : $view->publicShopUrl;
@endphp
<div class="card mb-3">
    <div class="card-body">
        <p class="mb-1"><strong>公開URL</strong></p>
        <p><a href="{{ $shopQrUrl }}" target="_blank" rel="noopener noreferrer">{{ $shopQrUrl }}</a></p>
        @if($view->customDomainUrl !== '')
            <p class="mb-1 text-muted">標準URL: <a href="{{ $view->publicShopUrl }}" target="_blank" rel="noopener noreferrer">{{ $view->publicShopUrl }}</a></p>
            <small class="form-text d-block mb-2">独自ドメインの変更はシステム管理のプロジェクト編集から行います。</small>
        @endif
        <div data-issued-qr data-url="{{ $shopQrUrl }}" data-filename="shop-{{ request()->route('project_key') }}.png" aria-label="オンラインショップのQRコード" class="shop-settings-qr"></div>
        <button type="button" class="btn btn-secondary mt-2" data-save-issued-qr disabled>QR画像を保存</button>
        <p class="text-danger" data-qr-error hidden role="alert"></p>
        <p class="form-text mb-0">店頭掲示用のQRは <a href="{{ admin_route('admin.qr-codes') }}">QRコード発行</a> でも確認できます。</p>
    </div>
</div>
@endif

<form action="{{ admin_route('admin.settings.shop.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="hidden" name="feature_shop_enabled" value="0">
                    <input type="checkbox" name="feature_shop_enabled" value="1" {{ old('feature_shop_enabled', $view->shopEnabled) ? 'checked' : '' }}>
                    <span>ショップ（配送EC）を有効にする</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label" for="shop_shipping_fee">基本送料（円）</label>
                <input type="number" name="shop_shipping_fee" id="shop_shipping_fee" class="form-control" value="{{ old('shop_shipping_fee', $view->shippingFee) }}" min="0" max="100000" required>
                <small class="form-text">都道府県別の上書きが無い場合に使います。</small>
                @error('shop_shipping_fee')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">都道府県別送料（円）</label>
                <small class="form-text d-block mb-2">空欄は基本送料を使います。沖縄など遠い地域だけ高くする用途です。</small>
                <div class="row">
                    @foreach($view->prefectures as $prefecture)
                        @php
                            $oldFees = old('shop_shipping_fees_by_prefecture', $view->shippingFeesByPrefecture);
                            $feeValue = is_array($oldFees) && array_key_exists($prefecture, $oldFees) ? $oldFees[$prefecture] : '';
                        @endphp
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="form-label mb-0" for="shop_fee_{{ $loop->index }}">{{ $prefecture }}</label>
                            <input type="number" name="shop_shipping_fees_by_prefecture[{{ $prefecture }}]" id="shop_fee_{{ $loop->index }}" class="form-control" value="{{ $feeValue }}" min="0" max="100000" placeholder="{{ $view->shippingFee }}">
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="shop_hero_headline">トップ見出し</label>
                <input type="text" name="shop_hero_headline" id="shop_hero_headline" class="form-control" value="{{ old('shop_hero_headline', $view->heroHeadline) }}" maxlength="120" placeholder="今だけのオンラインショップ">
                @error('shop_hero_headline')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="shop_hero_subcopy">トップリード文</label>
                <textarea name="shop_hero_subcopy" id="shop_hero_subcopy" class="form-control" rows="3" maxlength="500" placeholder="お気に入りを自宅にお届けします。">{{ old('shop_hero_subcopy', $view->heroSubcopy) }}</textarea>
                @error('shop_hero_subcopy')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="shop_hero_image">ヒーロー画像</label>
                @if($view->heroImageUrl !== '')
                    <div class="mb-2"><img src="{{ $view->heroImageUrl }}" alt="" style="max-width:320px;max-height:160px;object-fit:cover;border-radius:8px;"></div>
                    <label class="checkbox-label mb-2">
                        <input type="hidden" name="clear_shop_hero_image" value="0">
                        <input type="checkbox" name="clear_shop_hero_image" value="1">
                        <span>画像を削除する</span>
                    </label>
                @endif
                <input type="file" name="shop_hero_image" id="shop_hero_image" class="form-control" accept="image/*">
                @error('shop_hero_image')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">注目商品（ピックアップ）</label>
                <small class="form-text d-block mb-2">未選択の場合はすべての商品を表示します。選んだ商品は先頭に並びます。</small>
                <div class="row">
                    @forelse($view->products as $product)
                        <div class="col-md-6">
                            <label class="checkbox-label">
                                <input type="checkbox" name="shop_featured_product_ids[]" value="{{ $product['id'] }}"
                                    {{ in_array($product['id'], old('shop_featured_product_ids', $view->featuredProductIds), true) ? 'checked' : '' }}>
                                <span>{{ $product['name'] }}（¥{{ number_format($product['price']) }}）</span>
                            </label>
                        </div>
                    @empty
                        <p class="text-muted">表示できる商品がありません。先に商品を登録してください。</p>
                    @endforelse
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                <a class="btn btn-secondary" href="{{ $view->publicShopUrl }}" target="_blank" rel="noopener noreferrer">プレビュー</a>
            </div>
        </div>
    </div>
</form>
@endsection
@push('styles')
<style>
.shop-settings-qr { display:grid; place-items:center; width:max-content; max-width:100%; margin-top:12px; padding:12px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; }
.shop-settings-qr canvas { max-width:100%; height:auto!important; }
</style>
@endpush
