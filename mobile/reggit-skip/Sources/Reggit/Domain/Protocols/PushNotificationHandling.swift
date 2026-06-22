import Foundation

@MainActor
protocol PushNotificationHandling: AnyObject {
    func handleNotificationDeepLink(tab: String, notificationType: String?)
    func handleForegroundNotification(notificationType: String?)
    func injectPushToken(_ token: String) async
}
