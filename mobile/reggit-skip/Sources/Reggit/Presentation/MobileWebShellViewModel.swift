import Foundation
import Observation
import SkipWeb

@MainActor
@Observable
final class MobileWebShellViewModel: PushNotificationHandling {
    let config: MobileWebShellConfig
    let webConfiguration: WebEngineConfiguration
    let navigator: WebViewNavigator

    var isLoading = true
    var showWhiteScreenFallback = false
    var fallbackMessage = ""
    var webViewReloadKey = 0
    var activeTab: String
    var tabs: [AppTabItem] = []
    var showNativeTabBar = false
    var isWebContentReady = false

    private let deviceProvider: any NativeDeviceContextProvider
    private let buildScriptUseCase: any NativeInjectionScriptBuilding
    private let scriptExecutor: any WebViewScriptExecuting
    private let appConfigLoader: any AppConfigLoading
    private let whiteScreenMonitor: any WhiteScreenMonitoring
    private let pushNotificationObserver: any PushNotificationObserving
    private let reachabilityChecker: any ShellURLReachabilityChecking
    private var pendingNotificationDeepLink: PendingNotificationDeepLink?

    init(
        config: MobileWebShellConfig,
        webConfiguration: WebEngineConfiguration,
        navigator: WebViewNavigator,
        deviceProvider: any NativeDeviceContextProvider,
        buildScriptUseCase: any NativeInjectionScriptBuilding,
        scriptExecutor: any WebViewScriptExecuting,
        appConfigLoader: any AppConfigLoading,
        whiteScreenMonitor: any WhiteScreenMonitoring,
        pushNotificationObserver: any PushNotificationObserving,
        reachabilityChecker: any ShellURLReachabilityChecking
    ) {
        self.config = config
        self.activeTab = config.fixedTab
        self.webConfiguration = webConfiguration
        self.navigator = navigator
        self.deviceProvider = deviceProvider
        self.buildScriptUseCase = buildScriptUseCase
        self.scriptExecutor = scriptExecutor
        self.appConfigLoader = appConfigLoader
        self.whiteScreenMonitor = whiteScreenMonitor
        self.pushNotificationObserver = pushNotificationObserver
        self.reachabilityChecker = reachabilityChecker
    }

    var liffURL: URL? {
        URL(string: config.liffURLString)
    }

    var urlErrorDescription: String {
        config.liffURLString
    }

    func onAppear() {
        resetShellState()
        Task { @MainActor in
            guard await reachabilityChecker.isReachable(config.liffURLString) else {
                markShellUnreachable()
                return
            }
            beginWhiteScreenMonitoring()
            onNavigationFinished(applyInitialTab: false)
        }
        pushNotificationObserver.startObserving(handler: self)
    }

    func onDisappear() {
        whiteScreenMonitor.stop()
        pushNotificationObserver.stopObserving()
    }

    func onNativeTabBarVisibilityChanged(_ show: Bool) {
        guard show else { return }
        if let pending = appConfigLoader.takePendingConfig() {
            applyAppConfig(pending)
        } else if !appConfigLoader.isLoaded {
            loadAppConfigEarlyIfAvailable()
        }
    }

    func loadAppConfigEarlyIfAvailable() {
        guard showNativeTabBar, !appConfigLoader.isLoaded else { return }
        Task { @MainActor in
            guard let config = await appConfigLoader.fetchEarlyNative() else { return }
            applyAppConfig(config)
        }
    }

    func loadAppConfigFromWebIfNeeded() {
        guard showNativeTabBar, !appConfigLoader.isLoaded else { return }
        Task { @MainActor in
            guard let config = await appConfigLoader.fetchViaWeb() else { return }
            applyAppConfig(config)
        }
    }

    func applyAppConfig(_ config: AppConfig) {
        guard let outcome = appConfigLoader.apply(
            config,
            tabBarVisible: showNativeTabBar,
            activeTab: activeTab,
            fixedTab: self.config.fixedTab
        ) else { return }
        tabs = outcome.tabs
        activeTab = outcome.activeTab
        if outcome.shouldSwitchTab {
            switchToTab(outcome.activeTab)
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
        scriptExecutor.run(script)
    }

    func switchToTab(_ tabKey: String) {
        activeTab = tabKey
        guard showNativeTabBar, isWebContentReady else { return }
        scriptExecutor.run(MobileWebShellScripts.switchTab(tabKey))
    }

    func selectTab(_ tabKey: String) {
        switchToTab(tabKey)
    }

    func retryLoad() {
        resetShellState()
        webViewReloadKey += 1
        Task { @MainActor in
            guard await reachabilityChecker.isReachable(config.liffURLString) else {
                markShellUnreachable()
                return
            }
            beginWhiteScreenMonitoring()
        }
    }

    // MARK: - PushNotificationHandling

    func handleForegroundNotification(notificationType: String?) {
        guard notificationType == "coupon_issued" else { return }
        scriptExecutor.run(MobileWebShellScripts.foregroundCouponNotification())
    }

    func handleNotificationDeepLink(tab: String, notificationType: String?) {
        activeTab = tab
        guard showNativeTabBar, isWebContentReady else {
            pendingNotificationDeepLink = PendingNotificationDeepLink(tab: tab, notificationType: notificationType)
            return
        }
        runNotificationDeepLink(tab: tab, notificationType: notificationType)
    }

    func injectPushToken(_ token: String) async {
        scriptExecutor.run(MobileWebShellScripts.pushTokenInjection(token: token))
    }

    // MARK: - Private

    private func resetShellState() {
        isLoading = true
        showWhiteScreenFallback = false
        showNativeTabBar = false
        isWebContentReady = false
        fallbackMessage = ""
        pendingNotificationDeepLink = nil
        whiteScreenMonitor.resetTracking()
    }

    private func markShellUnreachable() {
        whiteScreenMonitor.stop()
        isLoading = false
        showWhiteScreenFallback = true
        showNativeTabBar = false
        isWebContentReady = false
        pendingNotificationDeepLink = nil
        fallbackMessage = reachabilityChecker.unreachableMessage(for: config.liffURLString)
        logger.error("Web shell unreachable: \(fallbackMessage)")
    }

    private func markWebContentReadyIfNeeded() {
        guard !isWebContentReady else { return }
        isWebContentReady = true
        showNativeTabBar = true
        onNavigationFinished(applyInitialTab: true)
        switchToTab(activeTab)
        flushPendingNotificationDeepLinkIfNeeded()
    }

    private func flushPendingNotificationDeepLinkIfNeeded() {
        guard let pending = pendingNotificationDeepLink else { return }
        pendingNotificationDeepLink = nil
        runNotificationDeepLink(tab: pending.tab, notificationType: pending.notificationType)
    }

    private func runNotificationDeepLink(tab: String, notificationType: String?) {
        let isCoupon = notificationType == "coupon_issued" || tab == "coupons"
        scriptExecutor.run(MobileWebShellScripts.notificationDeepLink(tab: tab, isCoupon: isCoupon))
    }

    private func beginWhiteScreenMonitoring() {
        whiteScreenMonitor.start(
            liffURLString: config.liffURLString,
            isContentReady: { [weak self] in self?.isWebContentReady ?? false },
            inspectPage: { [scriptExecutor] in await scriptExecutor.inspectPageVisibility() },
            forceBootstrap: { [scriptExecutor] in
                let result = await scriptExecutor.forceBootstrap()
                logger.warning("Forced bootstrap result: \(result)")
            },
            onEvent: { [weak self] event in
                self?.handleWhiteScreenEvent(event)
            }
        )
    }

    private func handleWhiteScreenEvent(_ event: WhiteScreenMonitorEvent) {
        switch event {
        case .contentVisible:
            isLoading = false
            showWhiteScreenFallback = false
            fallbackMessage = ""
            markWebContentReadyIfNeeded()
        case .autoReload:
            webViewReloadKey += 1
            beginWhiteScreenMonitoring()
        case .fallback(let message):
            isLoading = false
            showWhiteScreenFallback = true
            showNativeTabBar = false
            isWebContentReady = false
            pendingNotificationDeepLink = nil
            fallbackMessage = message
        }
    }
}

private struct PendingNotificationDeepLink {
    let tab: String
    let notificationType: String?
}
