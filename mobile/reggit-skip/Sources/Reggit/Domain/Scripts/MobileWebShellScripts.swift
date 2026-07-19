import Foundation

enum MobileWebShellScripts {
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
            if (typeof window.__nativeOpenTab__ === 'function') {
                return window.__nativeOpenTab__(\(tab.jsStringLiteral), { couponReceived: \(isCoupon ? "true" : "false") }) ? 'opened' : 'pending';
            }
            window.__NATIVE_PENDING_TAB__ = \(tab.jsStringLiteral);
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
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}
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
            window.__NATIVE_TARGET_TAB__ = \(tabKey.jsStringLiteral);
            if (window.app && typeof window.app.switchTab === 'function') {
                window.app.switchTab(\(tabKey.jsStringLiteral));
                return 'switched';
            }
            const tabId = \(tabKey.jsStringLiteral) + 'Tab';
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
                if (el.id === tabId) el.classList.add('active');
            });
            document.querySelectorAll('.main-tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-tab') === \(tabKey.jsStringLiteral)) btn.classList.add('active');
            });
            return 'dom_switched';
        })();
        """
    }

    static func pageVisibilityInspection() -> String {
        """
        (() => {
            const appContainer = document.querySelector('.app-container');
            const activeTab = document.querySelector('.tab-content.active');
            const activeTabSection = document.querySelector('.tab-content.active .tab-section');
            const footer = document.querySelector('.main-tabs');
            const loading = document.getElementById('liff-loading');
            const body = document.body;
            const appReady = !!(window.app && typeof window.app.switchTab === 'function');
            const sectionChildren = activeTabSection ? activeTabSection.children.length : 0;
            const sectionTextLength = activeTabSection && activeTabSection.textContent ? activeTabSection.textContent.trim().length : 0;
            const layoutVisible =
                (appContainer && appContainer.offsetHeight > 120) ||
                (activeTab && activeTab.offsetHeight > 80) ||
                (footer && footer.offsetHeight > 30);
            const visible = appReady || !!layoutVisible;
            const loadingDisplay = loading ? getComputedStyle(loading).display : 'none';
            const bodyChildren = body ? body.children.length : 0;
            const href = window.location ? String(window.location.href || '') : '';
            const ready = document.readyState || 'unknown';
            const summary = [ready, href, bodyChildren, appContainer ? appContainer.offsetHeight : 0, activeTab ? activeTab.offsetHeight : 0, activeTabSection ? activeTabSection.offsetHeight : 0, footer ? footer.offsetHeight : 0, loadingDisplay, appReady ? '1' : '0', sectionChildren, sectionTextLength, visible ? '1' : '0'].join('|');
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
