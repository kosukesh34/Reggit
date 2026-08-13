import Foundation

/// web の `tabPath.ts` TAB_PATHS と揃える。HashRouter は `#/queue` 形式のみ見る。
enum NativeTabHashPath {
    static func path(for tabKey: String) -> String {
        switch tabKey {
        case "home": return "/home"
        case "member", "membership", "card": return "/membership"
        case "designlab": return "/designlab"
        case "coupons", "coupon": return "/coupon"
        case "stamps", "stamp": return "/stamp"
        case "queue", "visit": return "/queue"
        case "reservations", "reservation": return "/reservation"
        case "products", "menu", "order": return "/menu"
        case "orders", "orderHistory": return "/orderHistory"
        case "announcements", "news": return "/news"
        case "gallery": return "/gallery"
        case "more": return "/more"
        case "link_1": return "/link_1"
        case "link_2": return "/link_2"
        case "link_3": return "/link_3"
        default: return "/" + tabKey
        }
    }
}

enum MobileWebShellScripts {
    static func hashNavigateJavaScript(tabKey: String) -> String {
        let path = NativeTabHashPath.path(for: tabKey)
        return """
        window.__NATIVE_TARGET_TAB__ = \(tabKey.jsStringLiteral);
        try {
            const url = new URL(window.location.href);
            url.hash = \(path.jsStringLiteral);
            window.history.replaceState({}, '', url.toString());
            document.dispatchEvent(new CustomEvent('liff-ui-navigate', { detail: { path: \(path.jsStringLiteral), replace: true } }));
        } catch (e) {}
        """
    }

    static func pushTokenInjection(token: String) -> String {
        "typeof window.__nativeSetPushToken === 'function' && window.__nativeSetPushToken(\(token.jsStringLiteral));"
    }

    static func foregroundCouponNotification() -> String {
        """
        (() => {
            try {
                window.dispatchEvent(new CustomEvent('mobile-coupon-received', { detail: { tab: 'coupons', foreground: true } }));
            } catch (e) {}
            return 'dispatched';
        })();
        """
    }

    static func notificationDeepLink(tab: String, isCoupon: Bool) -> String {
        """
        (() => {
            \(hashNavigateJavaScript(tabKey: tab))
            if (typeof window.__nativeOpenTab__ === 'function') {
                return window.__nativeOpenTab__(\(tab.jsStringLiteral), { couponReceived: \(isCoupon ? "true" : "false") }) ? 'opened' : 'pending';
            }
            window.__NATIVE_PENDING_TAB__ = \(tab.jsStringLiteral);
            if (window.app && typeof window.app.switchTab === 'function') {
                window.app.switchTab(\(tab.jsStringLiteral));
                return 'switched';
            }
            return 'queued';
        })();
        """
    }

    static func queueDeepLink(queueToken: String) -> String {
        """
        (() => {
            try {
                const token = \(queueToken.jsStringLiteral);
                try { sessionStorage.setItem('mobile_order_pending_queue_token', token); } catch (e) {}
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'queue');
                url.searchParams.set('queue_token', token);
                url.searchParams.delete('queue_id');
                url.hash = \(NativeTabHashPath.path(for: "queue").jsStringLiteral);
                window.history.replaceState({}, '', url.toString());
                document.dispatchEvent(new CustomEvent('liff-ui-navigate', { detail: { path: \(NativeTabHashPath.path(for: "queue").jsStringLiteral), replace: true } }));
            } catch (e) {}
            window.__NATIVE_TARGET_TAB__ = 'queue';
            if (typeof window.__nativeOpenTab__ === 'function') {
                return window.__nativeOpenTab__('queue', { queueToken: \(queueToken.jsStringLiteral) }) ? 'opened' : 'pending';
            }
            window.__NATIVE_PENDING_TAB__ = 'queue';
            if (window.app && typeof window.app.switchTab === 'function') {
                window.app.switchTab('queue');
                return 'switched';
            }
            return 'queued';
        })();
        """
    }

    static func switchTab(_ tabKey: String) -> String {
        """
        (() => {
            \(hashNavigateJavaScript(tabKey: tabKey))
            if (window.app && typeof window.app.switchTab === 'function') {
                window.app.switchTab(\(tabKey.jsStringLiteral));
                return 'switched';
            }
            return 'hashed';
        })();
        """
    }

    static func pageVisibilityInspection() -> String {
        """
        (() => {
            const appShell = document.querySelector('.app-shell');
            const appContainer = document.querySelector('#app-container, .app-container');
            const activeTab = document.querySelector('.tab-content.active');
            const activeTabSection = document.querySelector('.tab-content.active .tab-section');
            const footer = document.querySelector('.main-tabs, .bottom-nav');
            const loading = document.getElementById('liff-loading');
            const body = document.body;
            const appReady = !!(window.app && typeof window.app.switchTab === 'function');
            const liffUiReady = !!(body && body.classList.contains('reggit-liff-ui') && appShell && appShell.offsetHeight > 80);
            const sectionChildren = activeTabSection ? activeTabSection.children.length : 0;
            const sectionTextLength = activeTabSection && activeTabSection.textContent ? activeTabSection.textContent.trim().length : 0;
            const layoutVisible =
                (appShell && appShell.offsetHeight > 120) ||
                (appContainer && appContainer.offsetHeight > 120) ||
                (activeTab && activeTab.offsetHeight > 80) ||
                (footer && footer.offsetHeight > 30);
            const visible = appReady || liffUiReady || !!layoutVisible;
            const loadingDisplay = loading ? getComputedStyle(loading).display : 'none';
            const bodyChildren = body ? body.children.length : 0;
            const href = window.location ? String(window.location.href || '') : '';
            const ready = document.readyState || 'unknown';
            const summary = [ready, href, bodyChildren, appShell ? appShell.offsetHeight : 0, appContainer ? appContainer.offsetHeight : 0, activeTab ? activeTab.offsetHeight : 0, footer ? footer.offsetHeight : 0, loadingDisplay, appReady ? '1' : '0', liffUiReady ? '1' : '0', sectionChildren, sectionTextLength, visible ? '1' : '0'].join('|');
            return summary;
        })();
        """
    }

    /// ngrok 無料プランの警告ページ（ERR_NGROK_6024）が WebView に表示された場合、
    /// 「Visit Site」ボタンと同じバイパス Cookie をセットしてリロードする。
    /// WebView のリクエストにヘッダーを付与できない（SkipWeb 未対応）ための回避策。
    static func ngrokInterstitialBypass() -> String {
        """
        (() => {
            const body = document.body;
            if (!body || body.id !== 'ngrok') return;
            const root = document.getElementById('root');
            if (!(root && root.dataset && root.dataset.payload)) return;
            if (sessionStorage.getItem('__reggit_ngrok_bypass__') === '1') return;
            sessionStorage.setItem('__reggit_ngrok_bypass__', '1');
            const expires = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toUTCString();
            document.cookie = 'abuse_interstitial=' + window.location.host + ';expires=' + expires + ';path=/;SameSite=None;Secure';
            window.location.reload();
        })();
        """
    }

    static func forceBootstrap() -> String {
        """
        (() => {
            try {
                if (typeof document !== 'undefined' && typeof document.dispatchEvent === 'function') {
                    document.dispatchEvent(new CustomEvent('liff-ready'));
                }
                return 'liff_ready_dispatched';
            } catch (e) {
                return 'bootstrap_error:' + String(e && e.message ? e.message : e);
            }
        })();
        """
    }
}
