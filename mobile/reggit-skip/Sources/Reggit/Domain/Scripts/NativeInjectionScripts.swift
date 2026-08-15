import Foundation

/// ネイティブ WebView 起動時に注入する JS の各セクション。
enum NativeInjectionScripts {
    static func redirectGuard(expectedLiffURL: String) -> String {
        """
        try {
            const currentHref = String(window.location && window.location.href ? window.location.href : '');
            const currentUrl = currentHref ? new URL(currentHref) : null;
            const expectedUrl = new URL(\(expectedLiffURL.jsStringLiteral));
            const currentHost = currentUrl ? currentUrl.host : '';
            const expectedHost = expectedUrl.host;
            const hostMismatch = currentHost && expectedHost && currentHost !== expectedHost;
            // ngrok 無料プランの警告ページのみリダイレクト対象。
            // ホストが ngrok であること自体を条件にすると、?tab= 差分で無限リロードになる。
            const isNgrokInterstitialPage = !!(document.body && document.body.id === 'ngrok');
            if (hostMismatch || isNgrokInterstitialPage) {
                window.location.replace(\(expectedLiffURL.jsStringLiteral));
                return 'redirecting';
            }
        } catch (e) {}
        """
    }

    static func deviceInfoBlock(device: NativeDeviceContextProvider) -> String {
        """
        const nativeDeviceInfo = {
            platform: \(device.platform.rawValue.jsStringLiteral),
            device_identifier: \(device.deviceIdentifier.jsStringLiteral),
            os_version: \(device.osVersion.jsStringLiteral),
            app_version: \(device.appVersion.jsStringLiteral),
            push_token: \(device.pushToken.map { $0.jsStringLiteral } ?? "window.__MOBILE_PENDING_PUSH_TOKEN__ || null")
        };
        window.__MOBILE_APP_MODE__ = true;
        if (document.documentElement) document.documentElement.classList.add('native-app-embed');
        if (document.body) document.body.classList.add('native-app-embed');
        window.__MOBILE_DEVICE_INFO__ = Object.assign({}, window.__MOBILE_DEVICE_INFO__ || {}, nativeDeviceInfo);
        if (window.app && window.app.deviceManager && typeof window.app.deviceManager.registerDevice === 'function') {
            window.app.deviceManager.registerDevice();
        }
        try {
            window.dispatchEvent(new CustomEvent('mobile-device-info-injected'));
        } catch (e) {}
        """
    }

    static func bridgeFunctions(urlScheme: String) -> String {
        """
        window.__nativeSetPushToken = window.__nativeSetPushToken || function(token) {
            const normalized = token == null ? null : String(token).trim();
            window.__MOBILE_PENDING_PUSH_TOKEN__ = normalized || null;
            window.__MOBILE_DEVICE_INFO__ = Object.assign({}, window.__MOBILE_DEVICE_INFO__ || {}, {
                push_token: normalized || null
            });
            if (window.app && window.app.deviceManager && typeof window.app.deviceManager.updatePushToken === 'function') {
                window.app.deviceManager.updatePushToken(normalized || null);
            }
            return true;
        };

        window.__nativeOpenExternalUrl__ = window.__nativeOpenExternalUrl__ || function(url) {
            if (typeof url !== 'string' || !url) return false;
            try {
                const encoded = encodeURIComponent(url);
                window.location.href = '\(urlScheme)://openExternal?url=' + encoded;
                return true;
            } catch (e) { return false; }
        };

        if (nativeDeviceInfo.platform === 'ios') {
            window.__nativeAddAppleWalletPass__ = window.__nativeAddAppleWalletPass__ || function(url) {
                if (typeof url !== 'string' || !url) return false;
                try {
                    const encoded = encodeURIComponent(url);
                    window.location.href = '\(urlScheme)://addAppleWalletPass?url=' + encoded;
                    return true;
                } catch (e) { return false; }
            };
        }
        """
    }

    static let viewportEnforcement = """
        const enforceViewport = () => {
            const viewportContent = "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover";
            let viewport = document.querySelector('meta[name="viewport"]');
            if (!viewport) {
                viewport = document.createElement('meta');
                viewport.setAttribute('name', 'viewport');
                if (document.head) document.head.appendChild(viewport);
            }
            if (viewport) viewport.setAttribute('content', viewportContent);

            // タブバーは WebView 内の .bottom-nav が担当する。viewport 高さは画面全体を使う。
            var viewportHeight = (typeof window.innerHeight === 'number' && window.innerHeight > 0)
                ? window.innerHeight
                : (typeof window.visualViewport !== 'undefined' && window.visualViewport && window.visualViewport.height > 0)
                    ? window.visualViewport.height
                    : 800;
            var viewportPx = viewportHeight + 'px';
            var contentHeight = 'calc(' + viewportPx + ' - var(--native-header-height, 56px))';

            if (document.documentElement) {
                document.documentElement.style.setProperty('--native-viewport-height', viewportPx);
                document.documentElement.style.setProperty('--native-content-height', contentHeight);
                document.documentElement.style.overflow = 'hidden';
                document.documentElement.style.overflowX = 'hidden';
                document.documentElement.style.overflowY = 'hidden';
                document.documentElement.style.touchAction = 'pan-y';
                document.documentElement.style.maxWidth = '100vw';
                document.documentElement.style.height = viewportPx;
                document.documentElement.style.maxHeight = viewportPx;
            }
            if (document.body) {
                document.body.style.setProperty('--native-viewport-height', viewportPx);
                document.body.style.setProperty('--native-content-height', contentHeight);
                document.body.style.overflow = 'hidden';
                document.body.style.overflowX = 'hidden';
                document.body.style.overflowY = 'hidden';
                document.body.style.touchAction = 'pan-y';
                document.body.style.maxWidth = '100vw';
                document.body.style.height = viewportPx;
                document.body.style.maxHeight = viewportPx;
            }

            if (!window.__NATIVE_VIEWPORT_STYLE_INSTALLED__) {
                const style = document.createElement('style');
                style.textContent = `
                    html.native-app-embed, body.native-app-embed {
                        --native-header-height: 56px;
                        min-height: var(--native-viewport-height, 100vh) !important;
                        max-height: var(--native-viewport-height, 100vh) !important;
                        height: var(--native-viewport-height, 100vh) !important;
                        width: 100% !important;
                        max-width: 100vw !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        box-sizing: border-box !important;
                        overflow: hidden !important;
                        overscroll-behavior: none !important;
                        touch-action: pan-y !important;
                    }
                    .native-app-embed .app-container {
                        display: flex !important;
                        flex-direction: column !important;
                        min-height: var(--native-viewport-height, 100vh) !important;
                        max-height: var(--native-viewport-height, 100vh) !important;
                        height: var(--native-viewport-height, 100vh) !important;
                        width: 100% !important;
                        max-width: 100vw !important;
                        box-sizing: border-box !important;
                        overflow: hidden !important;
                        overscroll-behavior: none !important;
                    }
                    .native-app-embed .header {
                        display: block !important;
                        visibility: visible !important;
                        flex-shrink: 0 !important;
                    }
                    .native-app-embed .main-content {
                        flex: 1 1 auto !important;
                        min-height: 0 !important;
                        height: var(--native-content-height, calc(var(--native-viewport-height, 100vh) - var(--native-header-height, 56px))) !important;
                        max-height: var(--native-content-height, calc(var(--native-viewport-height, 100vh) - var(--native-header-height, 56px))) !important;
                        overflow: hidden !important;
                        width: 100% !important;
                        max-width: 100vw !important;
                        box-sizing: border-box !important;
                    }
                    .native-app-embed #react-frontend-tabs-root {
                        display: flex !important;
                        flex-direction: column !important;
                        flex: 1 1 auto !important;
                        min-height: 0 !important;
                        height: 100% !important;
                        max-height: 100% !important;
                        overflow: hidden !important;
                        width: 100% !important;
                        max-width: 100vw !important;
                        box-sizing: border-box !important;
                    }
                    .native-app-embed .tab-content.active {
                        display: flex !important;
                        flex-direction: column !important;
                        flex: 1 1 auto !important;
                        min-height: 0 !important;
                        height: 100% !important;
                        max-height: 100% !important;
                        overflow-y: auto !important;
                        overflow-x: hidden !important;
                        overscroll-behavior-y: contain !important;
                        -webkit-overflow-scrolling: touch !important;
                        touch-action: pan-y !important;
                    }
                    .native-app-embed .tab-content.active .tab-section {
                        display: block !important;
                        flex: 0 0 auto !important;
                        min-height: min-content !important;
                        height: auto !important;
                        max-height: none !important;
                        overflow: visible !important;
                        touch-action: pan-y !important;
                        box-sizing: border-box !important;
                    }
                    .native-app-embed .app-shell,
                    .native-app-embed .app-shell__frame,
                    .native-app-embed #app-container {
                        min-height: var(--native-viewport-height, 100vh) !important;
                        max-height: var(--native-viewport-height, 100vh) !important;
                        height: var(--native-viewport-height, 100vh) !important;
                        width: 100% !important;
                        max-width: 100vw !important;
                        overflow: hidden !important;
                    }
                    .native-app-embed .app-shell__main {
                        flex: 1 1 auto !important;
                        min-height: 0 !important;
                        overflow-y: auto !important;
                        overflow-x: hidden !important;
                        -webkit-overflow-scrolling: touch !important;
                    }
                    .native-app-embed #app, .native-app-embed [data-v-app], .native-app-embed main {
                        width: 100% !important;
                        max-width: 100vw !important;
                        box-sizing: border-box !important;
                        overflow: hidden !important;
                    }
                `;
                if (document.head) document.head.appendChild(style);
                window.__NATIVE_VIEWPORT_STYLE_INSTALLED__ = true;
            }

            var applyVisualViewportHeight = function() {
                if (typeof window.visualViewport === 'undefined') return;
                var vh = window.visualViewport.height;
                if (!(vh > 0)) return;
                var px = vh + 'px';
                var contentHeight = 'calc(' + px + ' - var(--native-header-height, 56px))';
                if (document.documentElement) {
                    document.documentElement.style.setProperty('--native-viewport-height', px);
                    document.documentElement.style.setProperty('--native-content-height', contentHeight);
                    document.documentElement.style.height = px;
                    document.documentElement.style.maxHeight = px;
                }
                if (document.body) {
                    document.body.style.setProperty('--native-viewport-height', px);
                    document.body.style.setProperty('--native-content-height', contentHeight);
                    document.body.style.height = px;
                    document.body.style.maxHeight = px;
                }
            };
            applyVisualViewportHeight();
            if (typeof window.visualViewport !== 'undefined' && !window.__NATIVE_VISUAL_VIEWPORT_BOUND__) {
                window.__NATIVE_VISUAL_VIEWPORT_BOUND__ = true;
                window.visualViewport.addEventListener('resize', applyVisualViewportHeight);
            }
            if (typeof window.addEventListener === 'function' && !window.__NATIVE_WINDOW_RESIZE_BOUND__) {
                window.__NATIVE_WINDOW_RESIZE_BOUND__ = true;
                window.addEventListener('resize', applyVisualViewportHeight);
            }
        };
        """

    static let zoomLock = """
        const enforceNoZoom = () => {
            if (window.__NATIVE_ZOOM_LOCK_INSTALLED__) return;
            const cancelIfMultiTouch = (event) => {
                if (event && event.touches && event.touches.length > 1) event.preventDefault();
            };
            document.addEventListener('touchstart', cancelIfMultiTouch, { passive: false });
            document.addEventListener('touchmove', cancelIfMultiTouch, { passive: false });
            document.addEventListener('gesturestart', (event) => event.preventDefault(), { passive: false });
            document.addEventListener('gesturechange', (event) => event.preventDefault(), { passive: false });
            document.addEventListener('gestureend', (event) => event.preventDefault(), { passive: false });
            document.addEventListener('dblclick', (event) => event.preventDefault(), { passive: false });
            window.addEventListener('wheel', (event) => {
                if (event.ctrlKey) event.preventDefault();
            }, { passive: false });
            window.__NATIVE_ZOOM_LOCK_INSTALLED__ = true;
        };
        """

    static func applyInitialTab(activeTab: String, applyInitialTab: Bool) -> String {
        let applyInitialTabLiteral = applyInitialTab ? "true" : "false"
        return """
        const applyInitialTabIfNeeded = () => {
            if (!\(applyInitialTabLiteral)) return false;
            if (window.__NATIVE_INITIAL_TAB_APPLIED__) return false;
            const inMemberFlow = (() => {
                try {
                    const params = new URLSearchParams(window.location.search || '');
                    return params.get('member_register') === '1' || params.get('member_screen') === 'register' || params.get('member_screen') === 'edit';
                } catch (e) { return false; }
            })();
            if (inMemberFlow) {
                window.__NATIVE_INITIAL_TAB_APPLIED__ = true;
                return false;
            }
            \(MobileWebShellScripts.hashNavigateJavaScript(tabKey: activeTab))
            if (window.app && typeof window.app.switchTab === 'function') {
                const current = String(window.app.currentTab || '');
                if (current !== \(activeTab.jsStringLiteral)) {
                    window.app.switchTab(\(activeTab.jsStringLiteral));
                }
            }
            window.__NATIVE_INITIAL_TAB_APPLIED__ = true;
            return true;
        };
        """
    }
}
