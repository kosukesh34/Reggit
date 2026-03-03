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

    private let buildScriptUseCase: BuildNativeInjectionScriptUseCase
    private let deviceProvider: NativeDeviceContextProvider
    private var monitorTask: Task<Void, Never>?
    private var didAutoReload: Bool = false

    init(
        config: MobileWebShellConfig,
        webConfiguration: WebEngineConfiguration,
        deviceProvider: NativeDeviceContextProvider,
        buildScriptUseCase: BuildNativeInjectionScriptUseCase
    ) {
        self.config = config
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

    func onAppear() {
        isLoading = true
        showWhiteScreenFallback = false
        fallbackMessage = ""
        didAutoReload = false
        startMonitoringWhiteScreen()
        onNavigationFinished()
        observePushTokenUpdate()
    }

    func onDisappear() {
        stopMonitoringWhiteScreen()
        if let observer = pushTokenObserver {
            NotificationCenter.default.removeObserver(observer)
            pushTokenObserver = nil
        }
    }

    /// When native (OneSignal) provides a subscription ID after the page has loaded, inject it into the WebView.
    private func observePushTokenUpdate() {
        pushTokenObserver = NotificationCenter.default.addObserver(
            forName: .oneSignalPushTokenDidUpdate,
            object: nil,
            queue: .main
        ) { [weak self] notification in
            guard let self else { return }
            let token = (notification.userInfo?["token"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines)
            guard let token, !token.isEmpty else { return }
            let escaped = token
                .replacingOccurrences(of: "\\", with: "\\\\")
                .replacingOccurrences(of: "\"", with: "\\\"")
                .replacingOccurrences(of: "\n", with: "\\n")
            let script = "typeof window.__nativeSetPushToken === 'function' && window.__nativeSetPushToken(\"\(escaped)\");"
            Task { @MainActor in
                _ = try? await self.navigator.evaluateJavaScript(script)
            }
        }
    }

    func onNavigationFinished() {
        let script = buildScriptUseCase.execute(config: config, device: deviceProvider)
        Task { @MainActor in
            _ = try? await self.navigator.evaluateJavaScript(script)
        }
    }

    func retryLoad() {
        showWhiteScreenFallback = false
        isLoading = true
        didAutoReload = false
        startMonitoringWhiteScreen()
        Task { @MainActor in
            _ = try? await self.navigator.evaluateJavaScript("window.location.reload();")
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
                    return
                }

                if elapsed >= 7, !self.didAutoReload {
                    self.didAutoReload = true
                    logger.warning("WebView content still not visible after 7s, auto reloading: \(inspection.summary)")
                    _ = try? await self.navigator.evaluateJavaScript("window.location.reload();")
                }

                if elapsed >= 14 {
                    self.isLoading = false
                    self.showWhiteScreenFallback = true
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
        let script = """
        (() => {
            const appContainer = document.querySelector('.app-container');
            const activeTab = document.querySelector('.tab-content.active');
            const footer = document.querySelector('.main-tabs');
            const loading = document.getElementById('liff-loading');
            const body = document.body;
            const visible =
                (appContainer && appContainer.offsetHeight > 120) ||
                (activeTab && activeTab.offsetHeight > 80) ||
                (footer && footer.offsetHeight > 30);
            const loadingDisplay = loading ? getComputedStyle(loading).display : 'none';
            const bodyChildren = body ? body.children.length : 0;
            const href = window.location ? String(window.location.href || '') : '';
            const ready = document.readyState || 'unknown';
            const summary = [ready, href, bodyChildren, appContainer ? appContainer.offsetHeight : 0, activeTab ? activeTab.offsetHeight : 0, footer ? footer.offsetHeight : 0, loadingDisplay, visible ? '1' : '0'].join('|');
            return summary;
        })();
        """

        guard let raw = try? await navigator.evaluateJavaScript(script) else {
            return PageInspection(visible: false, summary: "js_eval_failed")
        }

        let summary = normalizeEvaluateResult(raw)
        let fields = summary.split(separator: "|", omittingEmptySubsequences: false).map(String.init)
        let visible = fields.last == "1"
        return PageInspection(visible: visible, summary: summary)
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
