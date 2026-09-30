import Foundation

/// iOS / Android とも、共通の接続設定を使用します。
struct BundleMobileWebConfigRepository: MobileWebConfigRepository {
    var environment: MobileConnectionEnvironment = .current

    func resolveConfig() -> MobileWebShellConfig {
        let selected = MobileSelectedProjectStore.selectedProjectKey(for: environment)
        let allowed = CharacterSet(charactersIn: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-")
        let projectKey = selected.flatMap { key in
            key.rangeOfCharacter(from: allowed.inverted) == nil ? key : nil
        } ?? environment.projectKey
        return MobileWebShellConfig(baseUrl: environment.baseURL, projectKey: projectKey, fixedTab: environment.fixedTab)
    }
}
