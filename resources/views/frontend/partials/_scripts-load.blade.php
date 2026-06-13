@php $v = '?v=' . urlencode($assetVersion); @endphp
<script defer charset="utf-8" src="https://static.line-scdn.net/liff/edge/versions/2.22.3/sdk.js"></script>
<script defer src="https://js.stripe.com/v3/"></script>
@php
    $vendorScriptPaths = [
        'js/vendor/JsBarcode.all.min.js',
        'js/vendor/qrcode.min.js',
    ];
    $localScriptPaths = [
        'js/shared/constants.js',
        'js/shared/utils/apiClient.js',
        'js/shared/utils/domHelper.js',
        'js/shared/utils/tooltip.js',
        'js/shared/utils/toast.js',
        'js/shared/utils/confirmDialog.js',
        'js/frontend/managers/CartManager.js',
        'js/frontend/managers/ProductManager.js',
        'js/frontend/managers/OrderManager.js',
        'js/frontend/managers/DeviceManager.js',
        'js/frontend/managers/MemberManager.js',
        'js/frontend/managers/StampsManager.js',
        'js/frontend/managers/CouponsManager.js',
        'js/frontend/managers/QueueManager.js',
        'js/frontend/managers/AnnouncementManager.js',
        'js/frontend/managers/GalleryManager.js',
        'js/frontend/managers/LiffLayoutManager.js',
        'js/frontend/managers/CheckoutManager.js',
        'js/frontend/managers/ReservationManager.js',
        'js/frontend/stripe.js',
        'js/frontend/liff-init.js',
        'js/frontend/app.js',
    ];
    $inlineScriptPaths = array_merge($vendorScriptPaths, $localScriptPaths);
@endphp
@if(!$useInlineLocalAssets)
<script src="/js/vendor/JsBarcode.all.min.js{{ $v }}"></script>
<script src="/js/vendor/qrcode.min.js{{ $v }}"></script>
@endif
@if($useInlineLocalAssets)
    @foreach($inlineScriptPaths as $scriptPath)
        @php $inlineJs = @file_get_contents(public_path($scriptPath)) ?: ''; @endphp
        @if($inlineJs !== '')
            <script data-inline-path="{{ $scriptPath }}">{!! $inlineJs !!}</script>
        @endif
    @endforeach
@else
    <script defer src="/js/shared/constants.js{{ $v }}"></script>
    <script defer src="/js/shared/utils/apiClient.js{{ $v }}"></script>
    <script defer src="/js/shared/utils/domHelper.js{{ $v }}"></script>
    <script defer src="/js/shared/utils/tooltip.js{{ $v }}"></script>
    <script defer src="/js/shared/utils/toast.js{{ $v }}"></script>
    <script defer src="/js/shared/utils/confirmDialog.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/CartManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/ProductManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/OrderManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/DeviceManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/MemberManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/StampsManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/CouponsManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/QueueManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/AnnouncementManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/GalleryManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/LiffLayoutManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/CheckoutManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/managers/ReservationManager.js{{ $v }}"></script>
    <script defer src="/js/frontend/stripe.js{{ $v }}"></script>
    <script defer src="/js/frontend/liff-init.js{{ $v }}"></script>
    <script defer src="/js/frontend/app.js{{ $v }}"></script>
@endif
<script>
    (function () {
        function hasCoreScriptBindings() {
            return (
                typeof ApiClient !== 'undefined' &&
                typeof DeviceManager !== 'undefined' &&
                typeof ReggitApp !== 'undefined'
            );
        }

        function appendStylesheet(href) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        }

        function ensureStylesLoaded() {
            if (window.__INLINE_LOCAL_ASSETS__ === true) return;
            var loadingEl = document.getElementById('liff-loading');
            if (!loadingEl || typeof window.getComputedStyle !== 'function') return;
            var style = window.getComputedStyle(loadingEl);
            if (style && style.position === 'fixed') {
                if (window.__clientDebugLog) window.__clientDebugLog('css_ok', { position: style.position });
                return;
            }
            if (window.__clientDebugLog) window.__clientDebugLog('css_fallback_start', { detectedPosition: style ? style.position : 'unknown' });
            appendStylesheet('/css/frontend/style.css{{ $v }}');
            appendStylesheet('{{ asset('css/frontend/style.css') }}{{ $v }}');
        }

        function scriptCandidates(path) {
            return [
                path,
                '/public' + path
            ];
        }

        function loadScriptWithCandidates(candidates, index, done) {
            if (index >= candidates.length) {
                if (window.__clientDebugLog) window.__clientDebugLog('script_all_candidates_failed', { candidates: candidates });
                done(false);
                return;
            }
            var candidateSrc = candidates[index];
            var canonical = candidateSrc.split('?')[0];
            var existing = document.querySelector('script[src*="' + canonical + '"]');
            if (existing) {
                done(true);
                return;
            }
            var script = document.createElement('script');
            script.src = candidateSrc;
            script.defer = true;
            script.onload = function () {
                if (window.__clientDebugLog) window.__clientDebugLog('script_loaded', { src: script.src });
                done(true);
            };
            script.onerror = function () {
                if (window.__clientDebugLog) window.__clientDebugLog('script_load_failed', { src: script.src });
                loadScriptWithCandidates(candidates, index + 1, done);
            };
            document.body.appendChild(script);
        }

        function loadScriptsSequentially(paths, current) {
            if (current >= paths.length) return;
            loadScriptWithCandidates(scriptCandidates(paths[current]), 0, function () {
                loadScriptsSequentially(paths, current + 1);
            });
        }

        function ensureScriptsLoaded() {
            if (window.__INLINE_LOCAL_ASSETS__ === true) return;
            if (window.__LIFF_APP_BOOTSTRAP__ || hasCoreScriptBindings()) {
                if (window.__clientDebugLog) window.__clientDebugLog('app_bootstrap_already_done', {});
                return;
            }
            if (window.__SCRIPT_FALLBACK_STARTED__) {
                return;
            }
            window.__SCRIPT_FALLBACK_STARTED__ = true;
            if (window.__clientDebugLog) window.__clientDebugLog('script_fallback_start', {});
            var paths = [
                '/js/shared/constants.js{{ $v }}',
                '/js/shared/utils/apiClient.js{{ $v }}',
                '/js/shared/utils/domHelper.js{{ $v }}',
                '/js/shared/utils/tooltip.js{{ $v }}',
                '/js/shared/utils/toast.js{{ $v }}',
                '/js/shared/utils/confirmDialog.js{{ $v }}',
                '/js/frontend/managers/CartManager.js{{ $v }}',
                '/js/frontend/managers/ProductManager.js{{ $v }}',
                '/js/frontend/managers/OrderManager.js{{ $v }}',
                '/js/frontend/managers/DeviceManager.js{{ $v }}',
                '/js/frontend/managers/MemberManager.js{{ $v }}',
                '/js/frontend/managers/StampsManager.js{{ $v }}',
                '/js/frontend/managers/CouponsManager.js{{ $v }}',
                '/js/frontend/managers/QueueManager.js{{ $v }}',
                '/js/frontend/managers/AnnouncementManager.js{{ $v }}',
                '/js/frontend/managers/GalleryManager.js{{ $v }}',
                '/js/frontend/managers/LiffLayoutManager.js{{ $v }}',
                '/js/frontend/managers/CheckoutManager.js{{ $v }}',
                '/js/frontend/managers/ReservationManager.js{{ $v }}',
                '/js/frontend/stripe.js{{ $v }}',
                '/js/frontend/liff-init.js{{ $v }}',
                '/js/frontend/app.js{{ $v }}'
            ];
            loadScriptsSequentially(paths, 0);
        }

        setTimeout(function () {
            if (window.__clientDebugLog) window.__clientDebugLog('fallback_timer_fired', {});
            ensureStylesLoaded();
            ensureScriptsLoaded();
        }, 5000);
    })();
</script>
