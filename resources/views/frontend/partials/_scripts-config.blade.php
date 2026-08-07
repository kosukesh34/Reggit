@php
    $frontendHeaderConfig = [
        'title' => $frontendHeaderText ?? $projectName ?? config('app.name'),
        'logoUrl' => $frontendLogoUrl ?? null,
        'friendAddUrl' => $lineFriendAddUrl ?? null,
    ];
    $isDemoFixedUi = isset($isFixedUi) && $isFixedUi === true;
    $scriptLiffTabLayouts = $isDemoFixedUi ? [] : ($liffTabLayouts ?? []);
    $scriptLiffDetailLayouts = $isDemoFixedUi ? [] : ($liffDetailLayouts ?? []);
    $scriptLiffTabSettings = $isDemoFixedUi ? [] : ($liffTabSettings ?? []);
    $scriptFrontendMenu = $isDemoFixedUi
        ? [
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house'],
            ['tab' => 'products', 'label' => 'オーダー', 'icon' => 'fa-utensils'],
            ['tab' => 'member', 'label' => 'カード', 'icon' => 'fa-id-card'],
            ['tab' => 'queue', 'label' => '来店', 'icon' => 'fa-list-ol'],
        ]
        : array_values($filteredMenu ?? []);
@endphp
<meta name="stripe-key" content="{{ env('STRIPE_KEY', '') }}">
<script>
    window.__PROJECT_KEY__ = "{{ $projectKey ?? '' }}";
    (function () {
        var apiBaseMeta = document.querySelector('meta[name="api-base"]');
        window.__API_BASE__ = (apiBaseMeta ? apiBaseMeta.getAttribute('content') : null) || '/api';
    })();
    window.__WALLET_PASS_ENABLED__ = {{ ($walletPassEnabled ?? false) ? 'true' : 'false' }};
    window.__GOOGLE_WALLET_ENABLED__ = {{ ($googleWalletEnabled ?? false) ? 'true' : 'false' }};
    window.__WALLET_PASS_ADD_BUTTON_IMAGE__ = "/images/add-to-apple-wallet-logo.png";
    window.__GOOGLE_WALLET_ADD_BUTTON_IMAGE__ = "/images/add-to-google-wallet-logo.png";
    window.__ORDER_MODE__ = @json($orderMode ?? \App\Domain\Settings\ShopSettingKeys::ORDER_MODE_BOTH);
    window.__LINE_FRIEND_ADD_URL__ = @json($lineFriendAddUrl ?? '');
    window.__LIFF_TAB_LAYOUTS__ = @json($scriptLiffTabLayouts);
    window.__LIFF_DETAIL_LAYOUTS__ = @json($scriptLiffDetailLayouts);
    window.__LIFF_TAB_SETTINGS__ = @json($scriptLiffTabSettings);
    window.__FEATURE_FLAGS__ = @json($featureFlags ?? []);
    window.__QUEUE_REQUIRE_MEMBERSHIP__ = {{ ($queueRequireMembership ?? false) ? 'true' : 'false' }};
    window.__MEMBER_REGISTRATION_MODE__ = @json($memberRegistrationMode ?? \App\Enums\MemberRegistrationMode::OPTIONAL);
    window.__FRONTEND_MENU__ = @json($scriptFrontendMenu);
    window.__FRONTEND_HEADER__ = @json($frontendHeaderConfig);
    window.__IS_DESIGNER_EMBED_PREVIEW__ = {{ ($isDesignerEmbedPreview ?? false) ? 'true' : 'false' }};
    window.__DESIGNER_PREVIEW_MEMBER_PAYLOAD__ = @json($designerPreviewMemberPayload ?? null);
    window.__INLINE_LOCAL_ASSETS__ = {{ $useInlineLocalAssets ? 'true' : 'false' }};
    window.__clientDebugLog = function (eventName, payloadObj) {
        try {
            var payload = '';
            if (payloadObj && typeof payloadObj === 'object' && payloadObj !== null) {
                payload = JSON.stringify(payloadObj);
            }
            var body = {
                event: String(eventName || 'unknown'),
                project_key: String(window.__PROJECT_KEY__ || ''),
                path: String(window.location.pathname || ''),
                ts: String(Date.now()),
                ua: (navigator && navigator.userAgent) ? String(navigator.userAgent) : '',
                payload: payload
            };
            var url = '/api/client-debug';
            if (navigator && typeof navigator.sendBeacon === 'function') {
                var blob = new Blob([JSON.stringify(body)], { type: 'application/json' });
                navigator.sendBeacon(url, blob);
            } else if (typeof fetch === 'function') {
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                    keepalive: true,
                    credentials: 'same-origin'
                }).catch(function () {
                    var i = new Image();
                    i.src = url
                        + '?event=' + encodeURIComponent(body.event)
                        + '&project_key=' + encodeURIComponent(body.project_key)
                        + '&path=' + encodeURIComponent(body.path)
                        + '&ts=' + encodeURIComponent(body.ts)
                        + '&ua=' + encodeURIComponent(body.ua)
                        + '&payload=' + encodeURIComponent(body.payload);
                });
            } else {
                var i = new Image();
                i.src = url
                    + '?event=' + encodeURIComponent(body.event)
                    + '&project_key=' + encodeURIComponent(body.project_key)
                    + '&path=' + encodeURIComponent(body.path)
                    + '&ts=' + encodeURIComponent(body.ts)
                    + '&ua=' + encodeURIComponent(body.ua)
                    + '&payload=' + encodeURIComponent(body.payload);
            }
        } catch (e) { /* ignore */ }
    };
    window.__clientDebugLog('page_init', { href: window.location.href });
    window.__clientDebugLog('asset_mode', { inlineLocalAssets: window.__INLINE_LOCAL_ASSETS__ });
    window.addEventListener('error', function (ev) {
        try {
            window.__clientDebugLog('window_error', {
                message: ev && ev.message ? String(ev.message) : '',
                filename: ev && ev.filename ? String(ev.filename) : '',
                lineno: ev && typeof ev.lineno !== 'undefined' ? String(ev.lineno) : '',
                colno: ev && typeof ev.colno !== 'undefined' ? String(ev.colno) : ''
            });
        } catch (e) { /* ignore */ }
    });
    window.addEventListener('unhandledrejection', function (ev) {
        try {
            var reason = '';
            if (ev && typeof ev.reason === 'string') {
                reason = ev.reason;
            } else if (ev && ev.reason && ev.reason.message) {
                reason = String(ev.reason.message);
            } else {
                reason = 'unknown';
            }
            window.__clientDebugLog('unhandled_rejection', { reason: reason });
        } catch (e) { /* ignore */ }
    });
</script>
