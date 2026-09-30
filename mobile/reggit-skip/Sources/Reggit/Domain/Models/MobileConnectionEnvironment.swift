import Foundation

/// 接続先の定義はここだけで管理します。Debug は開発、Release は本番です。
enum MobileConnectionEnvironment {
    case development
    case production

    static let buildEnvironmentKey = "reggit.development_build"

    static var current: MobileConnectionEnvironment {
        #if os(Android)
        return UserDefaults.standard.bool(forKey: buildEnvironmentKey) ? .development : .production
        #elseif DEBUG
        return .development
        #else
        return .production
        #endif
    }

    var baseURL: String {
        switch self {
        case .development: return "https://unadministrable-idiocratically-annalee.ngrok-free.dev"
        case .production: return "https://reggit.jp"
        }
    }

    var projectKey: String {
        switch self {
        case .development: return "sb_skp835t6"
        case .production: return "sb_skt733n4"
        }
    }

    var storageKey: String {
        switch self {
        case .development: return "development"
        case .production: return "production"
        }
    }

    var fixedTab: String { "home" }
}
