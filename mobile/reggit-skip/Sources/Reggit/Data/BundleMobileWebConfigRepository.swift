import Foundation

/// 開発/本番の接続先をコード1箇所で切り替えます。
/// - `useDevelopmentServer == true` → 開発用（ngrok）に接続。ビルドし直さずに切り替え可能なのは Info.plist 由来のみのため、ここで上書きします。
/// - `false` → Skip.env / Info.plist の LIFF_BASE_URL（本番: reggit.shop）を使用。
private let useDevelopmentServer = true
private let developmentBaseUrl = "https://unadministrable-idiocratically-annalee.ngrok-free.dev"

struct BundleMobileWebConfigRepository: MobileWebConfigRepository {
    init() {}

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
        let platformProjectKey = readPlatformMetaString(
            iosKeys: ["LiffProjectKeyIos", "LIFF_PROJECT_KEY_IOS"],
            androidKeys: ["LiffProjectKeyAndroid", "LIFF_PROJECT_KEY_ANDROID"]
        )

        let sharedProjectKey = readMetaString(keys: ["LiffProjectKey", "LIFF_PROJECT_KEY"])
        let normalized = normalizeValue(platformProjectKey) ?? normalizeValue(sharedProjectKey) ?? "default"
        let allowed = CharacterSet(charactersIn: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-")
        if normalized.rangeOfCharacter(from: allowed.inverted) != nil {
            return "default"
        }
        return normalized
    }

    private func resolveFixedTab() -> String {
        let platformTab = readPlatformMetaString(
            iosKeys: ["LiffFixedTabIos", "LIFF_FIXED_TAB_IOS"],
            androidKeys: ["LiffFixedTabAndroid", "LIFF_FIXED_TAB_ANDROID"]
        )

        let sharedTab = readMetaString(keys: ["LiffFixedTab", "LIFF_FIXED_TAB"])
        let normalized = normalizeValue(platformTab) ?? normalizeValue(sharedTab) ?? "products"
        let allowedTabs: Set<String> = ["home", "products", "orders", "stamps", "coupons", "queue", "member", "reservations", "gallery"]
        return allowedTabs.contains(normalized) ? normalized : "products"
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
        let androidValue = normalizeValue(readMetaString(keys: androidKeys))
        let iosValue = normalizeValue(readMetaString(keys: iosKeys))

        if isAndroidRuntime() {
            return androidValue ?? iosValue
        }
        if isIOSRuntime() {
            return iosValue ?? androidValue
        }
        return iosValue ?? androidValue
    }

    private func isAndroidRuntime() -> Bool {
        ProcessInfo.processInfo.operatingSystemVersionString.lowercased().contains("android")
    }

    private func isIOSRuntime() -> Bool {
        ProcessInfo.processInfo.operatingSystemVersionString.lowercased().contains("ios")
    }

    private func normalizeValue(_ value: String?) -> String? {
        guard let value else { return nil }
        let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
        if trimmed.isEmpty || trimmed == "undefined" || trimmed == "null" {
            return nil
        }
        return trimmed
    }

    private static let fallbackBaseUrlIOS = "https://reggit.shop"

    private func defaultBaseUrl() -> String {
        if isAndroidRuntime() {
            return "http://10.0.2.2:8080"
        }
        if isIOSRuntime() {
            return Self.fallbackBaseUrlIOS
        }
        return Self.fallbackBaseUrlIOS
    }
}

struct DefaultNativeDeviceContextProvider: NativeDeviceContextProvider {
    private let defaults: UserDefaults
    private static let deviceIdentifierKey = "mobile_order_device_identifier"
    private static let pushTokenKey = "reggit_push_token"

    init(defaults: UserDefaults) {
        self.defaults = defaults
    }

    init() {
        self.init(defaults: .standard)
    }

    var pushToken: String? {
        guard let value = defaults.string(forKey: Self.pushTokenKey), !value.isEmpty else { return nil }
        return value
    }

    var platform: NativePlatform {
        #if os(iOS)
        return .ios
        #elseif os(Android)
        return .android
        #else
        return .unknown
        #endif
    }

    var deviceIdentifier: String {
        if let existing = defaults.string(forKey: Self.deviceIdentifierKey), !existing.isEmpty {
            return existing
        }
        let created = UUID().uuidString.lowercased()
        defaults.set(created, forKey: Self.deviceIdentifierKey)
        return created
    }

    var osVersion: String {
        ProcessInfo.processInfo.operatingSystemVersionString
    }

    var appVersion: String {
        let short = Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String
        let build = Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String
        let shortValue = short.flatMap { $0.isEmpty ? nil : $0 } ?? "1.0.0"
        if let build, !build.isEmpty {
            return "\(shortValue)(\(build))"
        }
        return shortValue
    }
}
