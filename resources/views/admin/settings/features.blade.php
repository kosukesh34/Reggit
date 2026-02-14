@extends('admin.layout')

@section('title', '機能・カスタマイズ設定')
@section('page-title', '機能・カスタマイズ設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.basic') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>機能・カスタマイズ</span>
</nav>

<form action="{{ admin_route('admin.settings.features.update') }}" method="POST">
    @csrf

    {{-- 機能ON/OFF --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-toggle-on"></i> 機能ON/OFF</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted text-muted-mb-16">有効化されていない機能はフロントエンド・管理画面の両方で非表示になります。</p>
            @foreach($view->featureFlagLabels as $key => $label)
                <label class="checkbox-label checkbox-block">
                    <input type="checkbox" name="features[{{ $key }}]" value="1" {{ ($view->featureFlags[$key] ?? false) ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- 管理画面 UI カスタマイズ --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-palette"></i> 管理画面カスタマイズ</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="admin_sidebar_color" class="form-label">サイドバー背景色</label>
                <div class="flex-row-gap-8">
                    <input type="color" name="admin_sidebar_color" id="admin_sidebar_color" value="{{ $view->adminSidebarColor ?? '#2c3e50' }}" class="color-input">
                    <input type="text" class="form-control input-max-200" value="{{ $view->adminSidebarColor ?? '' }}" placeholder="#2c3e50" oninput="document.getElementById('admin_sidebar_color').value=this.value">
                </div>
                <p class="form-text text-muted">空にするとデフォルトカラーが使用されます。</p>
            </div>
            <div class="form-group">
                <label for="admin_header_color" class="form-label">ヘッダー背景色</label>
                <div class="flex-row-gap-8">
                    <input type="color" name="admin_header_color" id="admin_header_color" value="{{ $view->adminHeaderColor ?? '#ffffff' }}" class="color-input">
                    <input type="text" class="form-control input-max-200" value="{{ $view->adminHeaderColor ?? '' }}" placeholder="#ffffff" oninput="document.getElementById('admin_header_color').value=this.value">
                </div>
            </div>
            <div class="form-group">
                <label for="admin_primary_color" class="form-label">アクセントカラー</label>
                <div class="flex-row-gap-8">
                    <input type="color" name="admin_primary_color" id="admin_primary_color" value="{{ $view->adminPrimaryColor ?? '#0d9488' }}" class="color-input">
                    <input type="text" class="form-control input-max-200" value="{{ $view->adminPrimaryColor ?? '' }}" placeholder="#0d9488" oninput="document.getElementById('admin_primary_color').value=this.value">
                </div>
                <p class="form-text text-muted">ボタンや強調表示の色に使用されます。</p>
            </div>
            <div class="form-group">
                <label for="stamp_default_image_url" class="form-label">スタンプデフォルト画像URL</label>
                <input type="url" name="stamp_default_image_url" id="stamp_default_image_url" class="form-control" value="{{ $view->stampDefaultImageUrl ?? '' }}" placeholder="https://example.com/stamp-default.png">
                <p class="form-text text-muted">スタンプカードに画像が設定されていない場合に使用されます。</p>
            </div>
            <div class="form-group">
                <label for="coupon_default_image_url" class="form-label">クーポンデフォルト画像URL</label>
                <input type="url" name="coupon_default_image_url" id="coupon_default_image_url" class="form-control" value="{{ $view->couponDefaultImageUrl ?? '' }}" placeholder="https://example.com/coupon-default.png">
                <p class="form-text text-muted">クーポンに画像が設定されていない場合に使用されます。</p>
            </div>
        </div>
    </div>

    {{-- フロントエンドカスタマイズ --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-mobile-alt"></i> フロントエンドカスタマイズ</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="frontend_logo_url" class="form-label">ロゴ画像URL</label>
                <input type="url" name="frontend_logo_url" id="frontend_logo_url" class="form-control" value="{{ $view->frontendLogoUrl ?? '' }}" placeholder="https://example.com/logo.png">
                <p class="form-text text-muted">フロントエンドのヘッダーに表示されるロゴ画像のURLを指定します。</p>
            </div>
            <div class="form-group">
                <label for="frontend_header_text" class="form-label">ヘッダーテキスト</label>
                <input type="text" name="frontend_header_text" id="frontend_header_text" class="form-control" value="{{ $view->frontendHeaderText ?? '' }}" placeholder="店舗名を入力">
                <p class="form-text text-muted">フロントエンドのヘッダーに表示されるテキスト（店舗名など）を指定します。</p>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
    </div>
</form>
@endsection
