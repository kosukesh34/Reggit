import Foundation
import UIKit
import UserNotifications
#if canImport(FirebaseCore)
import FirebaseCore
#endif
#if canImport(FirebaseMessaging)
import FirebaseMessaging
#endif

extension Notification.Name {
    static let kitchenOrderPushReceived = Notification.Name("kitchenOrderPushReceived")
}

final class PushRegistrationManager: NSObject, UNUserNotificationCenterDelegate {
    static let shared = PushRegistrationManager()
    private var isConfigured = false

    func configure() {
        guard !isConfigured else { return }
        isConfigured = true
        #if canImport(FirebaseCore)
        if Bundle.main.path(forResource: "GoogleService-Info", ofType: "plist") != nil,
           FirebaseApp.app() == nil {
            FirebaseApp.configure()
        }
        #endif
        #if canImport(FirebaseMessaging)
        Messaging.messaging().delegate = self
        #endif
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
                    self.refreshFcmToken()
                }
                return
            }
            center.requestAuthorization(options: [.alert, .sound, .badge]) { granted, _ in
                guard granted else { return }
                DispatchQueue.main.async {
                    UIApplication.shared.registerForRemoteNotifications()
                }
                self.refreshFcmToken()
            }
        }
    }

    func didRegister(deviceToken: Data) {
        #if canImport(FirebaseMessaging)
        Messaging.messaging().apnsToken = deviceToken
        #endif
        refreshFcmToken()
    }

    private func refreshFcmToken() {
        #if canImport(FirebaseMessaging)
        Messaging.messaging().token { token, error in
            if let error {
                print("[Kitchen FCM] token error: \(error.localizedDescription)")
                return
            }
            guard let token, !token.isEmpty else { return }
            self.saveAndRegister(token: token)
        }
        #endif
    }

    private func saveAndRegister(token: String) {
        let existing = UserDefaults.standard.string(forKey: AppConstants.Storage.pushTokenKey)
        if token == existing { return }
        UserDefaults.standard.set(token, forKey: AppConstants.Storage.pushTokenKey)
        print("[Kitchen FCM] Saved registration token (\(token.count) chars)")
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

#if canImport(FirebaseMessaging)
extension PushRegistrationManager: MessagingDelegate {
    func messaging(_ messaging: Messaging, didReceiveRegistrationToken fcmToken: String?) {
        guard let fcmToken, !fcmToken.isEmpty else { return }
        saveAndRegister(token: fcmToken)
    }
}
#endif
