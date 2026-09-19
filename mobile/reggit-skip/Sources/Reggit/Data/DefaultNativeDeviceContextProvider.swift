import Foundation

struct DefaultNativeDeviceContextProvider: NativeDeviceContextProvider {
    private let defaults: UserDefaults

    init(defaults: UserDefaults = .standard) {
        self.defaults = defaults
    }

    var pushToken: String? {
        defaults.string(forKey: NativeAppStorage.pushTokenKey)?.trimmedNonEmpty
    }

    var platform: NativePlatform {
        PlatformRuntime.nativePlatform
    }

    var deviceIdentifier: String {
        if let existing = defaults.string(forKey: NativeAppStorage.deviceIdentifierKey)?.trimmedNonEmpty {
            return existing
        }
        let created = UUID().uuidString.lowercased()
        defaults.set(created, forKey: NativeAppStorage.deviceIdentifierKey)
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
