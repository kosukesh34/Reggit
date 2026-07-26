import Foundation
import UIKit
import UserNotifications

extension Notification.Name {
    static let kitchenOrderPushReceived = Notification.Name("kitchenOrderPushReceived")
}

final class PushRegistrationManager: NSObject, UNUserNotificationCenterDelegate {
    static let shared = PushRegistrationManager()
    private var isConfigured = false

    func configure() {
        guard !isConfigured else { return }
        isConfigured = true
        let center = UNUserNotificationCenter.current()
        center.delegate = self
    }

    /// スプラッシュ表示後に呼ぶ（起動直後の許可ダイアログでスプラッシュを隠さない）
    func requestAuthorizationIfNeeded() {
        let center = UNUserNotificationCenter.current()
        center.getNotificationSettings { settings in
            guard settings.authorizationStatus == .notDetermined else {
                if settings.authorizationStatus == .authorized {
                    DispatchQueue.main.async {
                        UIApplication.shared.registerForRemoteNotifications()
                    }
                }
                return
            }
            center.requestAuthorization(options: [.alert, .sound, .badge]) { granted, _ in
                guard granted else { return }
                DispatchQueue.main.async {
                    UIApplication.shared.registerForRemoteNotifications()
                }
            }
        }
    }

    func didRegister(deviceToken: Data) {
        let token = deviceToken.map { String(format: "%02x", $0) }.joined()
        UserDefaults.standard.set(token, forKey: AppConstants.Storage.pushTokenKey)
        Task { @MainActor in
            let store = KitchenSettingsStore()
            let settings = store.load()
            try? await KitchenAPIClient().registerDevice(pushToken: token, deviceName: settings.deviceName)
        }
    }

    func userNotificationCenter(
        _ center: UNUserNotificationCenter,
        willPresent notification: UNNotification,
        withCompletionHandler completionHandler: @escaping (UNNotificationPresentationOptions) -> Void
    ) {
        NotificationCenter.default.post(name: .kitchenOrderPushReceived, object: nil)
        completionHandler([.banner, .sound])
    }

    func userNotificationCenter(
        _ center: UNUserNotificationCenter,
        didReceive response: UNNotificationResponse,
        withCompletionHandler completionHandler: @escaping () -> Void
    ) {
        NotificationCenter.default.post(name: .kitchenOrderPushReceived, object: nil)
        completionHandler()
    }
}
