import Foundation

struct BuildNativeInjectionScriptUseCase {
    func execute(config: MobileWebShellConfig, device: NativeDeviceContextProvider) -> String {
        """
        (() => {
            const targetTab = \(config.fixedTab.jsStringLiteral);
            const expectedLiffUrl = \(config.liffURLString.jsStringLiteral);
            const nativeDeviceInfo = {
                platform: \(device.platform.rawValue.jsStringLiteral),
                device_identifier: \(device.deviceIdentifier.jsStringLiteral),
                os_version: \(device.osVersion.jsStringLiteral),
                app_version: \(device.appVersion.jsStringLiteral),
                push_token: \(device.pushToken.map { $0.jsStringLiteral } ?? "window.__MOBILE_PENDING_PUSH_TOKEN__ || null")
            };
            const isAndroidPlatform = nativeDeviceInfo.platform === 'android';

            try {
                const currentHref = String(window.location && window.location.href ? window.location.href : '');
                const currentUrl = currentHref ? new URL(currentHref) : null;
                const expectedUrl = new URL(expectedLiffUrl);
                const currentHost = currentUrl ? currentUrl.host : '';
                const expectedHost = expectedUrl.host;
                const hostMismatch = currentHost && expectedHost && currentHost !== expectedHost;
                const isNgrokInterception = currentHost.includes('ngrok-free.dev') || currentHost.includes('ngrok.app');
                if ((hostMismatch || isNgrokInterception) && currentHref !== expectedLiffUrl) {
                    window.location.replace(expectedLiffUrl);
                    return 'redirecting';
                }
            } catch (e) {}

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
                    window.location.href = 'mobileorderapp://openExternal?url=' + encoded;
                    return true;
                } catch (e) { return false; }
            };

            window.__NATIVE_TARGET_TAB__ = targetTab;

            const tabId = targetTab + 'Tab';
            document.querySelectorAll('.tab-content').forEach(function(el) {
                el.classList.remove('active');
                if (el.id === tabId) el.classList.add('active');
            });
            document.querySelectorAll('.main-tab-btn').forEach(function(btn) {
                btn.classList.remove('active');
                if (btn.getAttribute('data-tab') === targetTab) btn.classList.add('active');
            });
            if (typeof requestAnimationFrame === 'function') {
                requestAnimationFrame(function() {
                    if (document.body) document.body.offsetHeight;
                });
            }

            const enforceViewport = () => {
                const viewportContent = "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover";
                let viewport = document.querySelector('meta[name="viewport"]');
                if (!viewport) {
                    viewport = document.createElement('meta');
                    viewport.setAttribute('name', 'viewport');
                    if (document.head) document.head.appendChild(viewport);
                }
                if (viewport) viewport.setAttribute('content', viewportContent);

                if (document.documentElement) {
                    document.documentElement.style.overflowX = 'hidden';
                    document.documentElement.style.touchAction = 'pan-y';
                    document.documentElement.style.maxWidth = '100vw';
                    if (isAndroidPlatform) {
                        document.documentElement.style.minHeight = '800px';
                        document.documentElement.style.height = '100%';
                    } else {
                        document.documentElement.style.minHeight = '100%';
                        document.documentElement.style.height = '100%';
                    }
                }
                if (document.body) {
                    document.body.style.overflowX = 'hidden';
                    document.body.style.touchAction = 'pan-y';
                    document.body.style.maxWidth = '100vw';
                    if (isAndroidPlatform) {
                        document.body.style.minHeight = '800px';
                        document.body.style.height = '100%';
                    } else {
                        document.body.style.minHeight = '100%';
                        document.body.style.height = '100%';
                    }
                }

                if (!window.__NATIVE_VIEWPORT_STYLE_INSTALLED__) {
                    const style = document.createElement('style');
                    const minHeightFallback = isAndroidPlatform ? 'max(100vh, 800px)' : '100vh';
                    const mainContentMinHeight = isAndroidPlatform ? 'max(calc(max(100vh, 800px) - 120px), 500px)' : `min(68vh, calc(${minHeightFallback} - 120px))`;
                    const androidEmbedStyle = `
                        html.native-app-embed, body.native-app-embed { --native-safe-bottom: max(env(safe-area-inset-bottom, 0px), 8px); --native-tab-height: calc(64px + var(--native-safe-bottom)); min-height: ${minHeightFallback} !important; height: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; overflow-x: hidden !important; background: #f5f5f5 !important; box-sizing: border-box !important; }
                        .native-app-embed .app-container { display: flex !important; flex-direction: column !important; min-height: ${minHeightFallback} !important; height: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
                        .native-app-embed .header { display: block !important; visibility: visible !important; flex-shrink: 0 !important; }
                        .native-app-embed .main-content { display: block !important; flex: 1 1 auto !important; min-height: ${mainContentMinHeight} !important; height: auto !important; overflow-y: auto !important; padding-bottom: calc(var(--native-tab-height) + 12px) !important; box-sizing: border-box !important; }
                        .native-app-embed .tab-content.active { display: block !important; min-height: ${mainContentMinHeight} !important; height: auto !important; }
                        .native-app-embed .tab-content.active .tab-section { display: block !important; min-height: ${mainContentMinHeight} !important; height: auto !important; }
                        .native-app-embed .main-tabs { display: none !important; visibility: hidden !important; height: 0 !important; overflow: hidden !important; pointer-events: none !important; }
                        .native-app-embed .main-content { padding-bottom: 0 !important; }
                    `;
                    const iOSEmbedStyle = `
                        html.native-app-embed, body.native-app-embed { --native-safe-bottom: max(env(safe-area-inset-bottom, 0px), 8px); --native-tab-height: calc(64px + var(--native-safe-bottom)); min-height: 100vh !important; height: auto !important; width: 100% !important; max-width: 100vw !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; overflow-x: hidden !important; overflow-y: hidden !important; }
                        .native-app-embed .app-container { display: flex !important; flex-direction: column !important; min-height: 100vh !important; height: auto !important; max-height: none !important; width: 100% !important; max-width: 100vw !important; box-sizing: border-box !important; padding: 0 !important; overflow: hidden !important; }
                        .native-app-embed .header { display: block !important; visibility: visible !important; }
                        .native-app-embed .main-content { flex: 1 1 auto !important; min-height: 72vh !important; overflow-x: hidden !important; overflow-y: auto !important; -webkit-overflow-scrolling: touch !important; width: 100% !important; max-width: 100vw !important; box-sizing: border-box !important; padding-bottom: calc(var(--native-tab-height) + 12px) !important; }
                        .native-app-embed .tab-content.active { display: block !important; min-height: 68vh !important; height: auto !important; flex: none !important; overflow-y: auto !important; -webkit-overflow-scrolling: touch !important; }
                        .native-app-embed .tab-content.active .tab-section { display: block !important; min-height: 68vh !important; height: auto !important; overflow-y: auto !important; -webkit-overflow-scrolling: touch !important; }
                        .native-app-embed .main-tabs { display: none !important; visibility: hidden !important; height: 0 !important; overflow: hidden !important; pointer-events: none !important; }
                        .native-app-embed .main-content { padding-bottom: 0 !important; }
                        .native-app-embed #app, .native-app-embed [data-v-app], .native-app-embed main { width: 100% !important; max-width: 100vw !important; box-sizing: border-box !important; overflow-x: hidden !important; }
                    `;
                    style.textContent = isAndroidPlatform ? androidEmbedStyle : iOSEmbedStyle;
                    if (document.head) document.head.appendChild(style);
                    window.__NATIVE_VIEWPORT_STYLE_INSTALLED__ = true;
                }
                var applyVisualViewportHeight = function() {
                    if (isAndroidPlatform) return;
                    if (typeof window.visualViewport === 'undefined') return;
                    var vh = window.visualViewport.height;
                    if (document.documentElement) document.documentElement.style.setProperty('--native-viewport-height', vh + 'px');
                    if (document.body) document.body.style.setProperty('--native-viewport-height', vh + 'px');
                };
                applyVisualViewportHeight();
                if (typeof window.visualViewport !== 'undefined' && !window.__NATIVE_VISUAL_VIEWPORT_BOUND__) {
                    window.__NATIVE_VISUAL_VIEWPORT_BOUND__ = true;
                    window.visualViewport.addEventListener('resize', applyVisualViewportHeight);
                    window.visualViewport.addEventListener('scroll', applyVisualViewportHeight);
                }
                if (isAndroidPlatform && typeof window.addEventListener === 'function' && !window.__NATIVE_ANDROID_RESIZE_BOUND__) {
                    window.__NATIVE_ANDROID_RESIZE_BOUND__ = true;
                    const applyAndroidViewport = () => {
                        var h = (typeof window.innerHeight === 'number' && window.innerHeight > 0) ? window.innerHeight : 800;
                        var minH = h + 'px';
                        if (document.documentElement) {
                            document.documentElement.style.overflowX = 'hidden';
                            document.documentElement.style.touchAction = 'pan-y';
                            document.documentElement.style.maxWidth = '100vw';
                            document.documentElement.style.minHeight = minH;
                            document.documentElement.style.height = '100%';
                        }
                        if (document.body) {
                            document.body.style.overflowX = 'hidden';
                            document.body.style.touchAction = 'pan-y';
                            document.body.style.maxWidth = '100vw';
                            document.body.style.minHeight = minH;
                            document.body.style.height = '100%';
                        }
                    };
                    window.addEventListener('resize', applyAndroidViewport);
                    setTimeout(applyAndroidViewport, 0);
                    setTimeout(applyAndroidViewport, 100);
                    setTimeout(applyAndroidViewport, 400);
                }
            };

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

            const applyTab = () => {
                enforceViewport();
                enforceNoZoom();

                const footer = document.querySelector('.main-tabs');
                if (footer) {
                    footer.style.display = 'none';
                    footer.style.visibility = 'hidden';
                    footer.style.height = '0';
                    footer.style.overflow = 'hidden';
                    footer.style.pointerEvents = 'none';
                }

                if (window.app && typeof window.app.switchTab === 'function') {
                    const current = String(window.app.currentTab || '');
                    if (current !== targetTab) {
                        window.app.switchTab(targetTab);
                    }
                    window.__NATIVE_LAST_APPLIED_TAB__ = targetTab;
                    return true;
                }
                const btn = document.querySelector(`.main-tab-btn[data-tab="${targetTab}"]`);
                if (btn && typeof btn.click === 'function' && !btn.classList.contains('active')) {
                    btn.click();
                    window.__NATIVE_LAST_APPLIED_TAB__ = targetTab;
                    return true;
                }
                return false;
            };

            if (window.__NATIVE_LAST_APPLIED_TAB__ !== targetTab && !window.__NATIVE_TAB_SYNC_TIMER__) {
                let retryCount = 0;
                window.__NATIVE_TAB_SYNC_TIMER__ = setInterval(() => {
                    retryCount += 1;
                    if (applyTab()) {
                        clearInterval(window.__NATIVE_TAB_SYNC_TIMER__);
                        window.__NATIVE_TAB_SYNC_TIMER__ = null;
                        return;
                    }
                    if (retryCount >= 40) {
                        clearInterval(window.__NATIVE_TAB_SYNC_TIMER__);
                        window.__NATIVE_TAB_SYNC_TIMER__ = null;
                    }
                }, 180);
            }

            if (applyTab()) {
                clearInterval(window.__NATIVE_TAB_SYNC_TIMER__);
                window.__NATIVE_TAB_SYNC_TIMER__ = null;
                return 'switched';
            }
            return 'pending';
        })();
        """
    }
}
