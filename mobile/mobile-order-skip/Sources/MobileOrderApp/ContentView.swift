import Foundation
import SwiftUI
import SkipWeb

struct ContentView: View {
    var body: some View {
        MobileWebShellView()
    }
}

enum MobileWebConfig {
    // Replace with your reachable production/staging URL.
    static let liffURLString = "https://sarah-weatherly-hokily.ngrok-free.dev/liff/default"
}

enum NativeDeviceContext {
    private static let deviceIdentifierKey = "mobile_order_device_identifier"

    static var platform: String {
        #if os(iOS)
        return "ios"
        #elseif os(Android)
        return "android"
        #else
        return "unknown"
        #endif
    }

    static var deviceIdentifier: String {
        let defaults = UserDefaults.standard
        if let existing = defaults.string(forKey: deviceIdentifierKey), !existing.isEmpty {
            return existing
        }
        let created = UUID().uuidString.lowercased()
        defaults.set(created, forKey: deviceIdentifierKey)
        return created
    }

    static var osVersion: String {
        ProcessInfo.processInfo.operatingSystemVersionString
    }

    static var appVersion: String {
        let short = Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String
        let build = Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String
        let shortValue = (short?.isEmpty == false) ? short! : "1.0.0"
        if let build, !build.isEmpty {
            return "\(shortValue)(\(build))"
        }
        return shortValue
    }
}

enum MobileTab: String, CaseIterable, Hashable, Identifiable {
    case home
    case products
    case orders
    case member

    var id: String { rawValue }

    var title: String {
        switch self {
        case .home:
            return "ホーム"
        case .products:
            return "注文"
        case .orders:
            return "履歴"
        case .member:
            return "会員証"
        }
    }

    var iconName: String {
        switch self {
        case .home:
            return "house.fill"
        case .products:
            return "cart.fill"
        case .orders:
            return "clock.fill"
        case .member:
            return "person.crop.rectangle.fill"
        }
    }
}

struct MobileWebShellView: View {
    @State var selectedTab: MobileTab = .products
    @State var configuration = WebEngineConfiguration()
    @State var navigator = WebViewNavigator()
    @State var webState = WebViewState()

    var liffURL: URL? {
        URL(string: MobileWebConfig.liffURLString)
    }

    var body: some View {
        VStack(spacing: 0) {
            browserControlBar

            ZStack {
                if let liffURL {
                    WebView(
                        configuration: configuration,
                        navigator: navigator,
                        url: liffURL,
                        state: $webState,
                        onNavigationFinished: {
                            syncWebTab()
                        }
                    )
                } else {
                    VStack(spacing: 8) {
                        Text("URL設定が不正です")
                            .font(.headline)
                        Text(MobileWebConfig.liffURLString)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                            .multilineTextAlignment(.center)
                    }
                    .padding()
                }
            }
            .frame(maxWidth: .infinity, maxHeight: .infinity)

            nativeTabBar
        }
        .onAppear {
            configuration.javaScriptEnabled = true
            configuration.allowsPullToRefresh = false
            syncWebTab()
        }
        .onChange(of: selectedTab) { _, _ in
            syncWebTab()
        }
    }

    var browserControlBar: some View {
        HStack(spacing: 10) {
            Button {
                navigator.goBack()
            } label: {
                Image(systemName: "chevron.left")
                    .font(.system(size: 14, weight: .semibold))
            }
            .buttonStyle(.plain)
            .disabled(!webState.canGoBack)
            .accessibilityLabel(Text("戻る"))

            Spacer(minLength: 8)

            Text(pageStatusLabel)
                .font(.caption2)
                .foregroundStyle(.secondary)
                .lineLimit(1)

            Spacer(minLength: 8)
        }
        .padding(.horizontal, 12)
        .padding(.vertical, 8)
        .background(Color.white.opacity(0.98))
        .overlay(alignment: .bottom) {
            Divider()
        }
    }

    var nativeTabBar: some View {
        HStack(spacing: 0) {
            ForEach(MobileTab.allCases) { tab in
                Button {
                    selectedTab = tab
                } label: {
                    VStack(spacing: 4) {
                        Image(systemName: tab.iconName)
                            .font(.system(size: 16))
                        Text(tab.title)
                            .font(.caption2)
                    }
                    .foregroundStyle(selectedTab == tab ? Color.accentColor : Color.secondary)
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 10)
                }
                .buttonStyle(.plain)
            }
        }
        .padding(.horizontal, 8)
        .background(Color.white.opacity(0.98))
        .overlay(alignment: .top) {
            Divider()
        }
    }

    var pageStatusLabel: String {
        if webState.isLoading {
            return "読み込み中..."
        }
        if let title = webState.pageTitle?.trimmingCharacters(in: CharacterSet.whitespacesAndNewlines), !title.isEmpty {
            return title
        }
        if let host = webState.url?.host, !host.isEmpty {
            return host
        }
        return "WebView"
    }

    func syncWebTab() {
        let script = """
        (() => {
            const targetTab = \(selectedTab.rawValue.jsStringLiteral);
            const nativeDeviceInfo = {
                platform: \(NativeDeviceContext.platform.jsStringLiteral),
                device_identifier: \(NativeDeviceContext.deviceIdentifier.jsStringLiteral),
                os_version: \(NativeDeviceContext.osVersion.jsStringLiteral),
                app_version: \(NativeDeviceContext.appVersion.jsStringLiteral),
                push_token: window.__MOBILE_PENDING_PUSH_TOKEN__ || null
            };

            window.__MOBILE_APP_MODE__ = true;
            window.__MOBILE_DEVICE_INFO__ = Object.assign({}, window.__MOBILE_DEVICE_INFO__ || {}, nativeDeviceInfo);
            if (window.app && window.app.deviceManager && typeof window.app.deviceManager.registerDevice === 'function') {
                window.app.deviceManager.registerDevice();
            }
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

            window.__NATIVE_TARGET_TAB__ = targetTab;

            const enforceNoZoom = () => {
                const viewportContent = "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover";
                let viewport = document.querySelector('meta[name="viewport"]');
                if (!viewport) {
                    viewport = document.createElement('meta');
                    viewport.setAttribute('name', 'viewport');
                    if (document.head) {
                        document.head.appendChild(viewport);
                    }
                }
                if (viewport) {
                    viewport.setAttribute('content', viewportContent);
                }
                if (document.documentElement) {
                    document.documentElement.style.touchAction = 'pan-x pan-y';
                }
                if (document.body) {
                    document.body.style.touchAction = 'pan-x pan-y';
                }

                if (!window.__NATIVE_ZOOM_LOCK_INSTALLED__) {
                    const cancelIfMultiTouch = (event) => {
                        if (event && event.touches && event.touches.length > 1) {
                            event.preventDefault();
                        }
                    };
                    document.addEventListener('touchstart', cancelIfMultiTouch, { passive: false });
                    document.addEventListener('touchmove', cancelIfMultiTouch, { passive: false });
                    document.addEventListener('gesturestart', (event) => event.preventDefault(), { passive: false });
                    document.addEventListener('gesturechange', (event) => event.preventDefault(), { passive: false });
                    document.addEventListener('gestureend', (event) => event.preventDefault(), { passive: false });
                    document.addEventListener('dblclick', (event) => event.preventDefault(), { passive: false });
                    window.addEventListener('wheel', (event) => {
                        if (event.ctrlKey) {
                            event.preventDefault();
                        }
                    }, { passive: false });
                    window.__NATIVE_ZOOM_LOCK_INSTALLED__ = true;
                }
            };

            const hideWebTabBar = () => {
                const footer = document.querySelector('.main-tabs');
                if (footer) {
                    footer.style.display = 'none';
                }
            };

            const applyTab = () => {
                enforceNoZoom();
                hideWebTabBar();

                const tab = window.__NATIVE_TARGET_TAB__;
                if (!tab) return false;

                if (window.app && typeof window.app.switchTab === 'function') {
                    window.app.switchTab(tab);
                    return true;
                }

                const btn = document.querySelector(`.main-tab-btn[data-tab="${tab}"]`);
                if (btn && typeof btn.click === 'function') {
                    btn.click();
                    return true;
                }

                return false;
            };

            if (!window.__NATIVE_TAB_SYNC_TIMER__) {
                window.__NATIVE_TAB_SYNC_TIMER__ = setInterval(() => {
                    if (applyTab()) {
                        clearInterval(window.__NATIVE_TAB_SYNC_TIMER__);
                        window.__NATIVE_TAB_SYNC_TIMER__ = null;
                    }
                }, 180);
            }

            if (applyTab()) {
                clearInterval(window.__NATIVE_TAB_SYNC_TIMER__);
                window.__NATIVE_TAB_SYNC_TIMER__ = null;
                return "switched";
            }

            return "pending";
        })();
        """

        Task {
            _ = try? await navigator.evaluateJavaScript(script)
        }
    }
}

private extension String {
    var jsStringLiteral: String {
        let escaped = self
            .replacingOccurrences(of: "\\", with: "\\\\")
            .replacingOccurrences(of: "\"", with: "\\\"")
            .replacingOccurrences(of: "\n", with: "\\n")
            .replacingOccurrences(of: "\r", with: "\\r")
            .replacingOccurrences(of: "\t", with: "\\t")
        return "\"\(escaped)\""
    }
}
