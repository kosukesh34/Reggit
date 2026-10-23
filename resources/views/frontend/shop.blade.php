<!DOCTYPE html>
@php
    $configuredAssetVersion = (string) config('app.asset_version', env('APP_VERSION', ''));
    $latestAssetMtime = @filemtime(public_path('build/manifest.json')) ?: 0;
    $assetVersion = $configuredAssetVersion !== ''
        ? $configuredAssetVersion
        : (string) ($latestAssetMtime > 0 ? $latestAssetMtime : time());
@endphp
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="project-key" content="{{ $projectKey ?? '' }}">
    <meta name="api-base" content="{{ config('app.api_base', '/api') }}">
    <meta name="shop-mode" content="1">
    @include('frontend.partials._native-app-config')
    <title>{{ ($shopPage->heroHeadline !== '' ? $shopPage->heroHeadline.' | ' : '').($projectName ?? config('app.name')) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Shippori+Mincho+B1:wght@800&family=Zen+Kaku+Gothic+New:wght@500;700;900&display=swap" rel="stylesheet">
    @include('frontend.partials._head-assets')
    <style>
      :root {
        --shop-primary: {{ $shopPage->theme['primary'] }};
        --shop-primary-dark: {{ $shopPage->theme['primaryDark'] }};
      }
    </style>
</head>
<body class="skip-native shop-web liff-ui-ready">
    <div id="shop-app-root"></div>
    @include('frontend.partials._scripts-config')
    @php
        $shopUserPayload = $shopPage->user === null ? null : [
            'id' => $shopPage->user->id,
            'name' => $shopPage->user->name,
            'email' => $shopPage->user->email,
            'hasPasskey' => $shopPage->user->hasPasskey,
        ];
        $shopBootstrap = [
            'projectName' => $shopPage->projectName,
            'loginUrl' => $shopPage->loginUrl,
            'logoutUrl' => $shopPage->logoutUrl,
            'heroHeadline' => $shopPage->heroHeadline,
            'heroSubcopy' => $shopPage->heroSubcopy,
            'heroImageUrl' => $shopPage->heroImageUrl,
            'logoUrl' => $shopPage->logoUrl,
            'shippingFee' => $shopPage->shippingFee,
            'shippingFeesByPrefecture' => $shopPage->shippingFeesByPrefecture,
            'prefectures' => $shopPage->prefectures,
            'featuredProductIds' => $shopPage->featuredProductIds,
            'theme' => $shopPage->theme,
            'user' => $shopUserPayload,
            'passkeyRegisterOptionsUrl' => $shopPage->passkeyRegisterOptionsUrl,
            'passkeyRegisterSubmitUrl' => $shopPage->passkeyRegisterSubmitUrl,
            'legalUrl' => $shopPage->legalUrl,
            'legalReady' => $shopPage->legalReady,
            'freeShippingThreshold' => $shopPage->freeShippingThreshold,
            'delivery' => $shopPage->delivery,
        ];
    @endphp
    <script>
        window.__SHOP_BOOTSTRAP__ = @json($shopBootstrap);
        window.__SHOP_USER__ = @json($shopUserPayload);
        window.__SHOP_LOGIN_URL__ = @json($shopPage->loginUrl);
        window.__LIFF_READY__ = true;
    </script>
    @php
        $shopVite = clone app(\Illuminate\Foundation\Vite::class);
    @endphp
    {{ $shopVite->reactRefresh() }}
    {{ $shopVite(['resources/js/shop/main.tsx']) }}
</body>
</html>
