<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">店舗設定</a><span class="breadcrumb-sep">/</span><span>アプリデザイン</span>
</nav>
<p class="form-section-desc">お客様向けアプリの見た目を設定します。各画面で変更を保存してから切り替えてください。</p>
<nav class="settings-category-tabs app-design-navigation" aria-label="アプリデザインの設定">
    @foreach([
        'layout' => ['admin.settings.home', '画面・メニュー'],
        'branding' => ['admin.settings.app-branding', '店舗名・画像'],
        'colors' => ['admin.settings.advanced', '配色'],
        'card' => ['admin.settings.member-card', '会員証'],
    ] as $key => [$route, $label])
        <a href="{{ admin_route($route) }}" @if($activeDesign === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
    @if(\App\Support\PlanFeatures::hasFeature((string) ($currentProject->plan ?? \App\Enums\ProjectPlan::NORMAL), \App\Support\PlanFeatures::WALLET))
        <a href="{{ admin_route('admin.settings.wallet') }}" @if($activeDesign === 'wallet') aria-current="page" @endif>Wallet会員証</a>
    @endif
    @if(auth('admin')->user()->hasPermission('liff_designer.manage') && \App\Support\PlanFeatures::hasFeature((string) ($currentProject->plan ?? \App\Enums\ProjectPlan::NORMAL), \App\Support\PlanFeatures::LIFF_DESIGNER_ADVANCED))
        <a href="{{ admin_route('admin.settings.liff-designer') }}" @if($activeDesign === 'advanced') aria-current="page" @endif>LINEアプリの詳細デザイン</a>
    @endif
</nav>
