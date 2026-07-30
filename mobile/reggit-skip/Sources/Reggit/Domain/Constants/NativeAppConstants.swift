import Foundation

public enum NativeAppStorage {
    public static let pushTokenKey = "reggit_push_token"
    public static let oneSignalAppIdKey = "reggit_onesignal_app_id"
    public static let deviceIdentifierKey = "mobile_order_device_identifier"
}

public extension Notification.Name {
    static let oneSignalPushTokenDidUpdate = Notification.Name("OneSignalPushTokenDidUpdate")
    static let oneSignalNotificationOpened = Notification.Name("OneSignalNotificationOpened")
    static let oneSignalNotificationForeground = Notification.Name("OneSignalNotificationForeground")
    static let showNotificationDeniedAlert = Notification.Name("ShowNotificationDeniedAlert")
}

enum NativeAppTabs {
    static let allowedFixedTabs: Set<String> = [
        "home", "products", "orders", "stamps", "coupons",
        "queue", "member", "reservations", "gallery",
    ]
    static let defaultFixedTab = "products"
}

enum WebShellTiming {
    static let reachabilityTimeout: TimeInterval = 10
    /// Splash 到達性確認中に activity を最低この時間見せてから結果を反映する。
    static let splashActivityMinimum: TimeInterval = 2
    static let bootstrapDelay: TimeInterval = 4
    static let autoReloadDelay: TimeInterval = 9
    static let fallbackDelay: TimeInterval = 16
    static let pollIntervalNanoseconds: UInt64 = 1_000_000_000
}

public enum WebShellHTTP {
    public static let ngrokSkipBrowserWarning = "true"
}
