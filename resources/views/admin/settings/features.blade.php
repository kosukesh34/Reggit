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
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-toggle-on"></i> 機能ON/OFF</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted" style="margin-bottom:16px;">有効化されていない機能はフロントエンド・管理画面の両方で非表示になります。</p>
            @foreach($featureFlagLabels as $key => $label)
                <label class="checkbox-label" style="display:block; margin-bottom:8px;">
                    <input type="checkbox" name="features[{{ $key }}]" value="1" {{ ($featureFlags[$key] ?? false) ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- 管理画面 UI カスタマイズ --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-palette"></i> 管理画面カスタマイズ</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="admin_sidebar_color" class="form-label">サイドバー背景色</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="color" name="admin_sidebar_color" id="admin_sidebar_color" value="{{ $adminSidebarColor ?? '#2c3e50' }}" style="width:48px; height:36px; border:none; cursor:pointer;">
                    <input type="text" class="form-control" value="{{ $adminSidebarColor ?? '' }}" placeholder="#2c3e50" oninput="document.getElementById('admin_sidebar_color').value=this.value" style="max-width:200px;">
                </div>
                <p class="form-text text-muted">空にするとデフォルトカラーが使用されます。</p>
            </div>
            <div class="form-group">
                <label for="admin_header_color" class="form-label">ヘッダー背景色</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="color" name="admin_header_color" id="admin_header_color" value="{{ $adminHeaderColor ?? '#ffffff' }}" style="width:48px; height:36px; border:none; cursor:pointer;">
                    <input type="text" class="form-control" value="{{ $adminHeaderColor ?? '' }}" placeholder="#ffffff" oninput="document.getElementById('admin_header_color').value=this.value" style="max-width:200px;">
                </div>
            </div>
            <div class="form-group">
                <label for="admin_primary_color" class="form-label">アクセントカラー</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="color" name="admin_primary_color" id="admin_primary_color" value="{{ $adminPrimaryColor ?? '#0d9488' }}" style="width:48px; height:36px; border:none; cursor:pointer;">
                    <input type="text" class="form-control" value="{{ $adminPrimaryColor ?? '' }}" placeholder="#0d9488" oninput="document.getElementById('admin_primary_color').value=this.value" style="max-width:200px;">
                </div>
                <p class="form-text text-muted">ボタンや強調表示の色に使用されます。</p>
            </div>
            <div class="form-group">
                <label for="stamp_default_image_url" class="form-label">スタンプデフォルト画像URL</label>
                <input type="url" name="stamp_default_image_url" id="stamp_default_image_url" class="form-control" value="{{ $stampDefaultImageUrl ?? '' }}" placeholder="https://example.com/stamp-default.png">
                <p class="form-text text-muted">スタンプカードに画像が設定されていない場合に使用されます。</p>
            </div>
            <div class="form-group">
                <label for="coupon_default_image_url" class="form-label">クーポンデフォルト画像URL</label>
                <input type="url" name="coupon_default_image_url" id="coupon_default_image_url" class="form-control" value="{{ $couponDefaultImageUrl ?? '' }}" placeholder="https://example.com/coupon-default.png">
                <p class="form-text text-muted">クーポンに画像が設定されていない場合に使用されます。</p>
            </div>
        </div>
    </div>

    {{-- フロントエンドカスタマイズ --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-mobile-alt"></i> フロントエンドカスタマイズ</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="frontend_logo_url" class="form-label">ロゴ画像URL</label>
                <input type="url" name="frontend_logo_url" id="frontend_logo_url" class="form-control" value="{{ $frontendLogoUrl ?? '' }}" placeholder="https://example.com/logo.png">
                <p class="form-text text-muted">フロントエンドのヘッダーに表示されるロゴ画像のURLを指定します。</p>
            </div>
            <div class="form-group">
                <label for="frontend_header_text" class="form-label">ヘッダーテキスト</label>
                <input type="text" name="frontend_header_text" id="frontend_header_text" class="form-control" value="{{ $frontendHeaderText ?? '' }}" placeholder="店舗名を入力">
                <p class="form-text text-muted">フロントエンドのヘッダーに表示されるテキスト（店舗名など）を指定します。</p>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
    </div>
</form>
@endsection
