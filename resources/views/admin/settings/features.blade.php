@extends('admin.layout')

@section('title', '機能・カスタマイズ設定')
@section('page-title', '機能・カスタマイズ設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>機能・カスタマイズ</span>
</nav>

<form action="{{ admin_route('admin.settings.features.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- 機能ON/OFF --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-toggle-on"></i> 機能ON/OFF</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted text-muted-mb-16">無効にした機能は顧客向け画面（LIFF）では非表示になります。管理画面のメニューは権限に応じて表示されたままです。</p>
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
                    <input type="color" id="admin_sidebar_color_picker" value="{{ old('admin_sidebar_color', $view->adminSidebarColor) ?: '#00344d' }}" class="color-input" aria-label="サイドバー背景色を選択" oninput="document.getElementById('admin_sidebar_color').value=this.value">
                    <input type="text" name="admin_sidebar_color" id="admin_sidebar_color" class="form-control input-max-200" value="{{ old('admin_sidebar_color', $view->adminSidebarColor) }}" placeholder="#00344d" pattern="#[0-9a-fA-F]{6}" maxlength="7" oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('admin_sidebar_color_picker').value=this.value" aria-describedby="admin_sidebar_color_help">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('admin_sidebar_color').value=''; document.getElementById('admin_sidebar_color_picker').value='#00344d'">標準色に戻す</button>
                </div>
                <p id="admin_sidebar_color_help" class="form-text text-muted">カラーピッカーまたはカラーコード（例: #00344d）で指定できます。文字色は背景に合わせて自動調整されます。空欄で保存すると標準色に戻ります。</p>
                @error('admin_sidebar_color')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
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
                    <input type="color" name="admin_primary_color" id="admin_primary_color" value="{{ $view->adminPrimaryColor ?? '#2563eb' }}" class="color-input">
                    <input type="text" class="form-control input-max-200" value="{{ $view->adminPrimaryColor ?? '' }}" placeholder="#2563eb" oninput="document.getElementById('admin_primary_color').value=this.value">
                </div>
                <p class="form-text text-muted">ボタン、選択中メニュー、リンク色に使われます。</p>
            </div>
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'stamp_default_image',
                'logoClearName' => 'stamp_default_image_clear',
                'logoFieldId' => 'stamp_default_image',
                'logoCurrentUrl' => (string) ($view->stampDefaultImageUrl ?? ''),
                'logoLabel' => 'スタンプデフォルト画像',
                'logoHelp' => 'スタンプカードに画像が設定されていない場合に使用されます。',
            ])
            <p class="form-text text-muted">クーポンのデフォルト画像は <a href="{{ admin_route('admin.settings.coupons') }}">クーポン設定</a> で変更できます。</p>
        </div>
    </div>

    {{-- フロントエンドカスタマイズ --}}
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-mobile-alt"></i> フロントエンドカスタマイズ</h3>
        </div>
        <div class="card-body">
            @include('admin.partials._frontend_logo_dropzone', [
                'logoCurrentUrl' => (string) ($view->frontendLogoUrl ?? ''),
                'logoHelp' => 'LIFF各画面の左上に表示するロゴです。未設定の場合は店舗名の頭文字を表示します。',
            ])
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
