import Foundation

@MainActor
final class PushNotificationObserver: PushNotificationObserving {
    private weak var handler: PushNotificationHandling?
    private var observers: [any NSObjectProtocol] = []

    func startObserving(handler: PushNotificationHandling) {
        stopObserving()
        self.handler = handler
        observe(.pushNotificationOpened) { [weak self] notification in
            let type = (notification.userInfo?["type"] as? String)?.trimmedNonEmpty
            let tab = (notification.userInfo?["tab"] as? String)?.trimmedNonEmpty ?? Self.tab(for: type)
            guard let tab else { return }
            self?.handler?.handleNotificationDeepLink(tab: tab, notificationType: type)
        }
        observe(.pushNotificationForeground) { [weak self] notification in
            let type = (notification.userInfo?["type"] as? String)?.trimmedNonEmpty
            self?.handler?.handleForegroundNotification(notificationType: type)
        }
        observe(.pushTokenDidUpdate) { [weak self] notification in
            guard let token = (notification.userInfo?["token"] as? String)?.trimmedNonEmpty else { return }
            Task { @MainActor in
                await self?.handler?.injectPushToken(token)
            }
        }
    }

    static func tab(for type: String?) -> String? {
        switch type {
        case "queue_called", "queue_linked": return "queue"
        case "order_confirmed", "order_ready", "order_completed": return "orders"
        case "coupon_issued": return "coupons"
        case "announcement": return "announcements"
        default: return nil
        }
    }

    func stopObserving() {
        observers.forEach { NotificationCenter.default.removeObserver($0) }
        observers.removeAll()
        handler = nil
    }

    private func observe(_ name: Notification.Name, handler: @escaping @MainActor (Notification) -> Void) {
        let observer = NotificationCenter.default.addObserver(
            forName: name,
            object: nil,
            queue: .main
        ) { notification in
            nonisolated(unsafe) let notification = notification
            MainActor.assumeIsolated {
                handler(notification)
            }
        }
        observers.append(observer)
    }
}
