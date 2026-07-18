import Foundation

@MainActor
final class AppConfigLoader: AppConfigLoading {
    private(set) var isLoaded = false
    private var pending: AppConfig?

    private let shellConfig: MobileWebShellConfig
    private let scriptExecutor: any WebViewScriptExecuting

    init(shellConfig: MobileWebShellConfig, scriptExecutor: any WebViewScriptExecuting) {
        self.shellConfig = shellConfig
        self.scriptExecutor = scriptExecutor
    }

    func takePendingConfig() -> AppConfig? {
        defer { pending = nil }
        return pending
    }

    func apply(
        _ config: AppConfig,
        tabBarVisible: Bool,
        activeTab: String,
        fixedTab: String
    ) -> AppConfigApplyOutcome? {
        guard tabBarVisible else {
            pending = config
            return nil
        }
        let isFirstLoad = !isLoaded
        isLoaded = true
        var resolvedTab = activeTab
        if !config.tabs.contains(where: { $0.key == activeTab }) {
            resolvedTab = config.tabs.first?.key ?? fixedTab
        }
        return AppConfigApplyOutcome(
            tabs: config.tabs,
            activeTab: resolvedTab,
            shouldSwitchTab: isFirstLoad
        )
    }

    func fetchEarlyNative() async -> AppConfig? {
        #if os(Android)
        return nil
        #else
        let client = AppConfigClient(baseUrl: shellConfig.baseUrl, projectKey: shellConfig.projectKey)
        return await client.fetchConfig()
        #endif
    }

    func fetchViaWeb() async -> AppConfig? {
        let script = AppConfigClient.syncFetchScript(
            baseUrl: shellConfig.baseUrl,
            projectKey: shellConfig.projectKey
        )
        guard let jsonString = await scriptExecutor.runAndNormalize(script) else {
            logger.warning("AppConfig fetchViaWeb returned empty")
            return nil
        }
        guard let config = AppConfigClient.parseJSONString(jsonString) else {
            let prefix = String(jsonString.prefix(80))
            let suffix = String(jsonString.suffix(40))
            logger.warning("AppConfig parse failed length=\(jsonString.count) prefix=\(prefix) suffix=\(suffix)")
            return nil
        }
        return config
    }
}
