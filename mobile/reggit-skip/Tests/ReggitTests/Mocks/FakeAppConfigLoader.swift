import Foundation

@MainActor
final class FakeAppConfigLoader: AppConfigLoading {
    var isLoaded = false
    var pendingConfig: AppConfig?
    var earlyNativeResult: AppConfig?
    var webFetchResult: AppConfig?
    private(set) var applyCallCount = 0

    func takePendingConfig() -> AppConfig? {
        defer { pendingConfig = nil }
        return pendingConfig
    }

    func apply(
        _ config: AppConfig,
        tabBarVisible: Bool,
        activeTab: String,
        fixedTab: String
    ) -> AppConfigApplyOutcome? {
        applyCallCount += 1
        guard tabBarVisible else {
            pendingConfig = config
            return nil
        }
        isLoaded = true
        return AppConfigApplyOutcome(
            tabs: config.tabs,
            activeTab: activeTab,
            shouldSwitchTab: applyCallCount == 1
        )
    }

    func fetchEarlyNative() async -> AppConfig? {
        earlyNativeResult
    }

    func fetchViaWeb() async -> AppConfig? {
        webFetchResult
    }
}
