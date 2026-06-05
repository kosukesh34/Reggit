import Foundation

extension Notification.Name {
    static let oneSignalPushTokenDidUpdate = Notification.Name("OneSignalPushTokenDidUpdate")
    static let oneSignalNotificationOpened = Notification.Name("OneSignalNotificationOpened")
    static let oneSignalNotificationForeground = Notification.Name("OneSignalNotificationForeground")
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
        let tab = fixedTab.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? fixedTab
        return "\(baseUrl)/liff/\(projectKey)?tab=\(tab)"
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
    var pushToken: String? { get }
}
