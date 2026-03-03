import Foundation

/// Posted by native (iOS/Android) when OneSignal subscription ID is available. userInfo["token"] = subscriptionId.
/// ViewModel observes this to inject the token into the WebView via __nativeSetPushToken.
extension Notification.Name {
    static let oneSignalPushTokenDidUpdate = Notification.Name("OneSignalPushTokenDidUpdate")
}

enum NativePlatform: String {
    case ios
    case android
    case unknown
}

struct MobileWebShellConfig {
    let baseUrl: String
    let projectKey: String
    let fixedTab: String

    var liffURLString: String {
        "\(baseUrl)/liff/\(projectKey)"
    }
}

protocol MobileWebConfigRepository {
    func resolveConfig() -> MobileWebShellConfig
}

protocol NativeDeviceContextProvider {
    var platform: NativePlatform { get }
    var deviceIdentifier: String { get }
    var osVersion: String { get }
    var appVersion: String { get }
    /// OneSignal subscription ID (or FCM token). Stored in UserDefaults/SharedPreferences by native layer.
    var pushToken: String? { get }
}
