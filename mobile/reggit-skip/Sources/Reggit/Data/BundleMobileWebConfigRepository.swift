import Foundation

/// 開発/本番の接続先をコード1箇所で切り替えます。
/// - Debug ビルド: 開発用（ngrok）に接続
/// - Release ビルド: Skip.env / Info.plist の LIFF_BASE_URL（本番: reggit.jp）を使用
#if DEBUG
private let useDevelopmentServer = true
#else
private let useDevelopmentServer = false
#endif
private let developmentBaseUrl = "https://unadministrable-idiocratically-annalee.ngrok-free.dev"

struct BundleMobileWebConfigRepository: MobileWebConfigRepository {
    func resolveConfig() -> MobileWebShellConfig {
        let baseUrl = resolveBaseUrl()
        let projectKey = resolveProjectKey()
        let fixedTab = resolveFixedTab()
        logger.info("MobileWeb config resolved: os=\(ProcessInfo.processInfo.operatingSystemVersionString), baseUrl=\(baseUrl), projectKey=\(projectKey), fixedTab=\(fixedTab)")
        return MobileWebShellConfig(baseUrl: baseUrl, projectKey: projectKey, fixedTab: fixedTab)
    }

    private func resolveBaseUrl() -> String {
        if useDevelopmentServer, !developmentBaseUrl.isEmpty {
            return developmentBaseUrl.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        }
        let platformBaseUrl = readPlatformMetaString(
            iosKeys: ["LiffBaseUrlIos", "LIFF_BASE_URL_IOS"],
            androidKeys: ["LiffBaseUrlAndroid", "LIFF_BASE_URL_ANDROID"]
        )
        let sharedBaseUrl = readMetaString(keys: ["LiffBaseUrl", "LIFF_BASE_URL"])
        let raw = normalizeValue(platformBaseUrl) ?? normalizeValue(sharedBaseUrl)
        let trimmed = raw?.trimmingCharacters(in: .whitespacesAndNewlines)
        guard let s = trimmed, !s.isEmpty else {
            return defaultBaseUrl()
        }
        return s.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
    }

    private func resolveProjectKey() -> String {
        if let selected = MobileSelectedProjectStore.selectedProjectKey {
            let allowed = CharacterSet(charactersIn: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-")
            if selected.rangeOfCharacter(from: allowed.inverted) == nil {
                return selected
            }
        }
        let platformProjectKey = readPlatformMetaString(
            iosKeys: ["LiffProjectKeyIos", "LIFF_PROJECT_KEY_IOS"],
            androidKeys: ["LiffProjectKeyAndroid", "LIFF_PROJECT_KEY_ANDROID"]
        )
        let sharedProjectKey = readMetaString(keys: ["LiffProjectKey", "LIFF_PROJECT_KEY"])
        let normalized = normalizeValue(platformProjectKey) ?? normalizeValue(sharedProjectKey) ?? ""
        let allowed = CharacterSet(charactersIn: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-")
        if normalized.isEmpty || normalized.rangeOfCharacter(from: allowed.inverted) != nil {
            // Android の Bundle.infoDictionary はマニフェスト meta-data を返さないことがある。
            return Self.fallbackProjectKey
        }
        return normalized
    }

    private func resolveFixedTab() -> String {
        let platformTab = readPlatformMetaString(
            iosKeys: ["LiffFixedTabIos", "LIFF_FIXED_TAB_IOS"],
            androidKeys: ["LiffFixedTabAndroid", "LIFF_FIXED_TAB_ANDROID"]
        )
        let sharedTab = readMetaString(keys: ["LiffFixedTab", "LIFF_FIXED_TAB"])
        let normalized = normalizeValue(platformTab) ?? normalizeValue(sharedTab) ?? NativeAppTabs.defaultFixedTab
        return NativeAppTabs.allowedFixedTabs.contains(normalized) ? normalized : NativeAppTabs.defaultFixedTab
    }

    private func readMetaString(keys: [String]) -> String? {
        for key in keys {
            if let value = Bundle.main.infoDictionary?[key] as? String {
                return value
            }
            if let value = Bundle.main.object(forInfoDictionaryKey: key) as? String {
                return value
            }
        }
        return nil
    }

    private func readPlatformMetaString(iosKeys: [String], androidKeys: [String]) -> String? {
        normalizeValue(readMetaString(keys: iosKeys + androidKeys))
    }

    private func normalizeValue(_ value: String?) -> String? {
        guard let value else { return nil }
        let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
        if trimmed.isEmpty || trimmed == "undefined" || trimmed == "null" {
            return nil
        }
        return trimmed
    }

    private static let fallbackBaseUrlIOS = "https://reggit.jp"
    private static let fallbackProjectKey = "sb_skp835t6"

    private func defaultBaseUrl() -> String {
        Self.fallbackBaseUrlIOS
    }
}
