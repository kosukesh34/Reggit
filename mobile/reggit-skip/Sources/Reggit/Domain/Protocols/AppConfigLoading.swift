import Foundation

@MainActor
protocol AppConfigLoading {
    var isLoaded: Bool { get }

    func takePendingConfig() -> AppConfig?
    func apply(
        _ config: AppConfig,
        tabBarVisible: Bool,
        activeTab: String,
        fixedTab: String
    ) -> AppConfigApplyOutcome?
    func fetchEarlyNative() async -> AppConfig?
    func fetchViaWeb() async -> AppConfig?
}
