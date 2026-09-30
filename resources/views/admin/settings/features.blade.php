@extends('admin.layout')

@section('title', $featureSection === 'branding' ? 'アプリデザイン' : ($featureSection === 'admin' ? '管理画面デザイン' : '利用機能'))
@section('page-title', $featureSection === 'branding' ? 'アプリデザイン' : ($featureSection === 'admin' ? '管理画面デザイン' : '利用機能'))

@section('content')
@if($featureSection === 'branding')
    @include('admin.settings._app-design-navigation', ['activeDesign' => 'branding'])
@else
    <p class="form-section-desc">{{ $featureSection === 'admin' ? 'スタッフが利用する管理画面の配色を設定します。' : 'お客様向けアプリで利用する機能を選びます。' }}</p>
@endif

<form action="{{ admin_route($featureRoute.'.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- 機能ON/OFF --}}
    @if($featureSection === 'features')
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-toggle-on"></i> 利用する機能</h3>
        </div>
        <div class="card-body">
            <p class="form-text text-muted text-muted-mb-16">無効にした機能は顧客向け画面（LIFF）では非表示になります。管理画面のメニューは権限に応じて表示されたままです。</p>
            @foreach($view->featureFlagLabels as $key => $label)
                <label class="checkbox-label checkbox-block">
                    <input type="checkbox" name="features[{{ $key }}]" value="1" {{ ($view->featureFlags[$key] ?? false) ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
            <hr>
            @foreach(['use_points' => ['ポイントを利用する', $view->usePoints], 'use_transaction_stamps' => ['スマレジ会計時にスタンプを付与する', $view->useTransactionStamps]] as $key => [$label, $enabled])
                <label class="checkbox-label checkbox-block"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $enabled))>{{ $label }}</label>
            @endforeach
            <p class="form-text">取引連動スタンプは、スタンプカード機能が有効な場合に利用できます。</p>

        </div>
    </div>

    @endif
    {{-- 管理画面 UI カスタマイズ --}}
    @if($featureSection === 'admin')
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-palette"></i> 管理画面の配色</h3>
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
                    <p class="text-danger" data-setting-error>{{ $message }}</p>
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
        </div>
    </div>

    @endif
    {{-- フロントエンドカスタマイズ --}}
    @if($featureSection === 'branding')
    @include('admin.settings._section-tabs', ['tabsLabel' => '店舗名と画像', 'sectionTabs' => ['images-identity' => '店舗名・ロゴ', 'images-home' => 'ホーム・ヘッダー', 'images-promotion' => 'スタンプ・クーポン']])
    <section id="images-identity">
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-mobile-alt"></i> 店舗名・ロゴ</h3>
        </div>
        <div class="card-body">
            @include('admin.partials._frontend_logo_dropzone', [
                'logoCurrentUrl' => (string) ($view->frontendLogoUrl ?? ''),
                'logoLabel' => 'アプリに表示するロゴ',
                'logoHelp' => 'LIFF各画面の左上に表示するロゴです。未設定の場合は店舗名の頭文字を表示します。',
            ])
            <div class="form-group">
                <label for="frontend_header_text" class="form-label">アプリに表示する店舗名</label>
                <input type="text" name="frontend_header_text" id="frontend_header_text" class="form-control" value="{{ old('frontend_header_text', $view->frontendHeaderText ?? '') }}" placeholder="店舗名を入力">
                <p class="form-text text-muted">お客様向けアプリの画面上部に表示する店舗名を指定します。</p>
            </div>
        </div>
    </div>

    </section>
    <section id="images-home">
    <div class="card card-mb-24"><div class="card-header"><h3 class="card-title">ホーム画像・ヘッダー画像</h3></div><div class="card-body">
            <div class="form-group">
                <label class="form-label" for="home_images">ホーム画像</label>
                <p class="section-help">最大10枚。複数枚は登録順にスライド表示されます。推奨 1200×1200px、JPEG・PNG・WebP、各8MB以下。</p>
                <input type="hidden" name="home_images_present" value="1">
                @if(!empty($view->homeImageUrls))
                    <div class="home-image-list">
                        @foreach($view->homeImageUrls as $index => $url)
                            <div class="home-image-item">
                                <img src="{{ $url }}" alt="ホーム画像 {{ $index + 1 }}">
                                <label><input type="checkbox" name="home_image_remove[]" value="{{ $index }}" {{ in_array($index, array_map('intval', old('home_image_remove', [])), true) ? 'checked' : '' }}> 削除</label>
                            </div>
                        @endforeach
                    </div>
                @endif
                <input type="file" id="home_images" name="home_images[]" accept="image/png,image/jpeg,image/webp" multiple>
                @error('home_images')<p class="text-danger" data-setting-error>{{ $message }}</p>@enderror
                @error('home_images.*')<p class="text-danger" data-setting-error>{{ $message }}</p>@enderror
            </div>
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'navigation_header_image',
                'logoClearName' => 'navigation_header_image_clear',
                'logoFieldId' => 'navigation_header_image',
                'logoCurrentUrl' => (string) old('navigation_header_image_url', $view->navigationHeaderImageUrl ?? ''),
                'logoLabel' => 'ヘッダー中央の画像',
                'logoHelp' => '推奨 600×120px。未設定時は店舗名を表示します。',
                'logoHint' => 'PNG / JPG / WebP 4MBまで',
            ])    </div></div>
    </section>
    <section id="images-promotion">
    <div class="card card-mb-24">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-stamp"></i> スタンプ画像</h3></div>
        <div class="card-body">
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'stamp_default_image',
                'logoClearName' => 'stamp_default_image_clear',
                'logoFieldId' => 'stamp_default_image',
                'logoCurrentUrl' => (string) ($view->stampDefaultImageUrl ?? ''),
                'logoLabel' => 'スタンプの既定画像',
                'logoHelp' => 'スタンプカードに画像が設定されていない場合に使用されます。',
            ])

        </div>
    </div>
    <div class="card card-mb-24"><div class="card-header"><h3 class="card-title">クーポン画像</h3></div><div class="card-body">
        @include('admin.partials._frontend_logo_dropzone', ['logoInputName' => 'coupon_default_image', 'logoClearName' => 'coupon_default_image_clear', 'logoFieldId' => 'coupon_default_image', 'logoCurrentUrl' => (string) ($view->couponDefaultImageUrl ?? ''), 'logoLabel' => 'クーポンの既定画像', 'logoHelp' => 'クーポンに個別の画像が設定されていない場合に表示します。'])
    </div></div>
    </section>
    @endif
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
    </div>
</form>
@endsection
