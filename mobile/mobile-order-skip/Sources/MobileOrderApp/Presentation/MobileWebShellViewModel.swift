import Foundation
import Observation
import SkipFuse
import SkipWeb

@MainActor
@Observable
final class MobileWebShellViewModel {
    let config: MobileWebShellConfig
    let webConfiguration: WebEngineConfiguration
    let navigator = WebViewNavigator()
    var isLoading: Bool = true
    var showWhiteScreenFallback: Bool = false
    var fallbackMessage: String = ""
    var webViewReloadKey: Int = 0
    var activeTab: String
    /// WebView で指定 URL のコンテンツが読み込めたときのみ true（タブバー表示・タブ切替の前提）
    var showNativeTabBar: Bool = false
    var isWebContentReady: Bool = false

    private let buildScriptUseCase: BuildNativeInjectionScriptUseCase
    private let deviceProvider: NativeDeviceContextProvider
    private var monitorTask: Task<Void, Never>?
    private var didAutoReload: Bool = false
    private var didForceBootstrap: Bool = false

    init(
        config: MobileWebShellConfig,
        webConfiguration: WebEngineConfiguration,
        deviceProvider: NativeDeviceContextProvider,
        buildScriptUseCase: BuildNativeInjectionScriptUseCase
    ) {
        self.config = config
        self.activeTab = config.fixedTab
        self.webConfiguration = webConfiguration
        self.deviceProvider = deviceProvider
        self.buildScriptUseCase = buildScriptUseCase
    }

    static var `default`: MobileWebShellViewModel {
        let repository = BundleMobileWebConfigRepository()
        let config = repository.resolveConfig()
        let device = DefaultNativeDeviceContextProvider()
        let configuration = BuildWebEngineConfigurationUseCase().execute(
            liffURLString: config.liffURLString,
            platform: device.platform
        )
        return MobileWebShellViewModel(
            config: config,
            webConfiguration: configuration,
            deviceProvider: device,
            buildScriptUseCase: BuildNativeInjectionScriptUseCase()
        )
    }

    var liffURL: URL? {
        URL(string: config.liffURLString)
    }

    var urlErrorDescription: String {
        config.liffURLString
    }

    private var pushTokenObserver: (any NSObjectProtocol)?
    private var notificationOpenObserver: (any NSObjectProtocol)?
    private var notificationForegroundObserver: (any NSObjectProtocol)?

    func onAppear() {
        isLoading = true
        showWhiteScreenFallback = false
        showNativeTabBar = false
        isWebContentReady = false
        fallbackMessage = ""
        didAutoReload = false
        didForceBootstrap = false
        Task { @MainActor in
            let reachable = await Self.checkShellURLReachable(self.config.liffURLString)
            if !reachable {
                self.markShellUnreachable(
                    message: """
                    指定のURLに接続できませんでした。
                    ネットワークとサーバー（ngrok 等）の起動を確認してください。

                    URL: \(self.config.liffURLString)
                    """
                )
                return
            }
            self.startMonitoringWhiteScreen()
            self.onNavigationFinished(applyInitialTab: false)
        }
        observePushTokenUpdate()
        observeNotificationOpen()
        observeNotificationForeground()
    }

    private func markShellUnreachable(message: String) {
        stopMonitoringWhiteScreen()
        isLoading = false
        showWhiteScreenFallback = true
        showNativeTabBar = false
        isWebContentReady = false
        fallbackMessage = message
        logger.error("Web shell unreachable: \(message)")
    }

    private func markWebContentReadyIfNeeded() {
        guard !isWebContentReady else { return }
        isWebContentReady = true
        showNativeTabBar = true
        onNavigationFinished(applyInitialTab: true)
        switchToTab(activeTab)
    }

    private static func checkShellURLReachable(_ urlString: String) async -> Bool {
        guard let url = URL(string: urlString) else { return false }
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = 10
        request.setValue("true", forHTTPHeaderField: "ngrok-skip-browser-warning")
        do {
            let (_, response) = try await URLSession.shared.data(for: request)
            guard let http = response as? HTTPURLResponse else { return false }
            return (200 ... 399).contains(http.statusCode)
        } catch {
            logger.warning("Shell URL reachability check failed: \(error.localizedDescription)")
            return false
        }
    }

    func onDisappear() {
        stopMonitoringWhiteScreen()
        if let observer = pushTokenObserver {
            NotificationCenter.default.removeObserver(observer)
            pushTokenObserver = nil
        }
        if let observer = notificationOpenObserver {
            NotificationCenter.default.removeObserver(observer)
            notificationOpenObserver = nil
        }
        if let observer = notificationForegroundObserver {
            NotificationCenter.default.removeObserver(observer)
            notificationForegroundObserver = nil
        }
    }

    private func observeNotificationOpen() {
        notificationOpenObserver = NotificationCenter.default.addObserver(
            forName: .oneSignalNotificationOpened,
            object: nil,
            queue: .main
        ) { notification in
            let tab = (notification.userInfo?["tab"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines)
            guard let tab, !tab.isEmpty else { return }
            let notificationType = (notification.userInfo?["type"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines)
            Self.scheduleNotificationDeepLink(tab: tab, notificationType: notificationType)
        }
    }

    private func observeNotificationForeground() {
        notificationForegroundObserver = NotificationCenter.default.addObserver(
            forName: .oneSignalNotificationForeground,
            object: nil,
            queue: .main
        ) { notification in
            let notificationType = (notification.userInfo?["type"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines)
            Self.scheduleForegroundNotification(notificationType: notificationType)
        }
    }

    nonisolated private static func scheduleNotificationDeepLink(tab: String, notificationType: String?) {
        Task { @MainActor in
            MobileWebShellViewModel.default.handleNotificationDeepLink(tab: tab, notificationType: notificationType)
        }
    }

    nonisolated private static func scheduleForegroundNotification(notificationType: String?) {
        Task { @MainActor in
            MobileWebShellViewModel.default.handleForegroundNotification(notificationType: notificationType)
        }
    }

    nonisolated private static func schedulePushTokenInjection(token: String) {
        Task { @MainActor in
            let script = "typeof window.__nativeSetPushToken === 'function' && window.__nativeSetPushToken(\(token.jsStringLiteral));"
            _ = try? await MobileWebShellViewModel.default.navigator.evaluateJavaScript(script)
        }
    }

    func handleForegroundNotification(notificationType: String?) {
        guard notificationType == "coupon_issued" else { return }
        let script = """
        (() => {
            try {
                window.dispatchEvent(new CustomEvent('mobile-coupon-received', { detail: { tab: 'coupons', foreground: true } }));
            } catch (e) {}
            return 'dispatched';
        })();
        """
        Task { @MainActor in
            _ = try? await self.navigator.evaluateJavaScript(script)
        }
    }

    func handleNotificationDeepLink(tab: String, notificationType: String? = nil) {
        activeTab = tab
        guard showNativeTabBar, isWebContentReady else { return }
        let isCoupon = notificationType == "coupon_issued" || tab == "coupons"
        let script = """
        (() => {
            if (typeof window.__nativeOpenTab__ === 'function') {
                return window.__nativeOpenTab__(\(tab.jsStringLiteral), { couponReceived: \(isCoupon ? "true" : "false") }) ? 'opened' : 'pending';
            }
            window.__NATIVE_PENDING_TAB__ = \(tab.jsStringLiteral);
            return 'queued';
        })();
        """
        Task { @MainActor in
            _ = try? await self.navigator.evaluateJavaScript(script)
        }
    }

    private func observePushTokenUpdate() {
        pushTokenObserver = NotificationCenter.default.addObserver(
            forName: .oneSignalPushTokenDidUpdate,
            object: nil,
            queue: .main
        ) { notification in
            let token = (notification.userInfo?["token"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines)
            guard let token, !token.isEmpty else { return }
            Self.schedulePushTokenInjection(token: token)
        }
    }

    func onNavigationFinished(applyInitialTab: Bool? = nil) {
        let shouldApplyTab = applyInitialTab ?? isWebContentReady
        let script = buildScriptUseCase.execute(
            config: config,
            device: deviceProvider,
            activeTab: activeTab,
            applyInitialTab: shouldApplyTab
        )
        Task { @MainActor in
            _ = try? await self.navigator.evaluateJavaScript(script)
        }
    }

    func fetchAppConfigViaWeb() async -> AppConfig? {
        let script = AppConfigClient.syncFetchScript(baseUrl: config.baseUrl, projectKey: config.projectKey)
        guard let raw = try? await navigator.evaluateJavaScript(script) else { return nil }
        let jsonString = normalizeEvaluateResult(raw)
        guard !jsonString.isEmpty else { return nil }
        return AppConfigClient.parseJSONString(jsonString)
    }

    func switchToTab(_ tabKey: String) {
        guard showNativeTabBar, isWebContentReady else {
            activeTab = tabKey
            return
        }
        activeTab = tabKey
        let script = """
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
        Task { @MainActor in
            _ = try? await self.navigator.evaluateJavaScript(script)
        }
    }

    func retryLoad() {
        showWhiteScreenFallback = false
        showNativeTabBar = false
        isWebContentReady = false
        isLoading = true
        didAutoReload = false
        didForceBootstrap = false
        webViewReloadKey += 1
        Task { @MainActor in
            let reachable = await Self.checkShellURLReachable(self.config.liffURLString)
            if !reachable {
                self.markShellUnreachable(
                    message: """
                    指定のURLに接続できませんでした。
                    ネットワークとサーバー（ngrok 等）の起動を確認してください。

                    URL: \(self.config.liffURLString)
                    """
                )
                return
            }
            self.startMonitoringWhiteScreen()
        }
    }

    private func stopMonitoringWhiteScreen() {
        monitorTask?.cancel()
        monitorTask = nil
    }

    private func startMonitoringWhiteScreen() {
        stopMonitoringWhiteScreen()
        monitorTask = Task { @MainActor [weak self] in
            guard let self else { return }
            let startedAt = Date()

            while !Task.isCancelled {
                let elapsed = Date().timeIntervalSince(startedAt)
                let inspection = await self.inspectPage()

                if inspection.visible {
                    self.isLoading = false
                    self.showWhiteScreenFallback = false
                    self.fallbackMessage = ""
                    self.markWebContentReadyIfNeeded()
                    return
                }

                if elapsed >= 4, !self.didForceBootstrap {
                    self.didForceBootstrap = true
                    await self.forceBootstrapIfNeeded()
                }

                if elapsed >= 9, !self.didAutoReload {
                    self.didAutoReload = true
                    logger.warning("WebView content still not visible after 9s, auto reloading: \(inspection.summary)")
                    self.webViewReloadKey += 1
                    self.startMonitoringWhiteScreen()
                }

                if elapsed >= 16 {
                    self.isLoading = false
                    self.showWhiteScreenFallback = true
                    self.showNativeTabBar = false
                    self.isWebContentReady = false
                    self.fallbackMessage = """
                    画面の読み込みが完了しませんでした。
                    URL: \(self.config.liffURLString)
                    状態: \(inspection.summary)
                    """
                    logger.error("White screen fallback shown: \(inspection.summary)")
                    return
                }

                try? await Task.sleep(nanoseconds: 1_000_000_000)
            }
        }
    }

    private func inspectPage() async -> PageInspection {
        guard let raw = try? await navigator.evaluateJavaScript(pageVisibilityInspectionScript()) else {
            return PageInspection(visible: false, summary: "js_eval_failed")
        }

        let summary = normalizeEvaluateResult(raw)
        let fields = summary.split(separator: "|", omittingEmptySubsequences: false).map(String.init)
        let visible = fields.last == "1"
        return PageInspection(visible: visible, summary: summary)
    }

    private func forceBootstrapIfNeeded() async {
        guard isWebContentReady else { return }
        let result: String
        if let value = try? await navigator.evaluateJavaScript(forceBootstrapScript()) {
            result = normalizeEvaluateResult(value)
        } else {
            result = "js_eval_failed"
        }
        logger.warning("Forced Android bootstrap result: \(result)")
    }

    private func pageVisibilityInspectionScript() -> String {
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

    private func forceBootstrapScript() -> String {
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

    private func normalizeEvaluateResult(_ value: Any) -> String {
        if let stringValue = value as? String {
            return trimOuterQuotes(stringValue)
        }
        return trimOuterQuotes(String(describing: value))
    }

    private func trimOuterQuotes(_ s: String) -> String {
        guard s.count >= 2 else { return s }
        if s.hasPrefix("\""), s.hasSuffix("\"") {
            return String(s.dropFirst().dropLast())
        }
        return s
    }
}

private struct PageInspection {
    let visible: Bool
    let summary: String
}
