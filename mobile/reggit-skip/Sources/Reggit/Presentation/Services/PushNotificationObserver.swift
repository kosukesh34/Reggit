import Foundation

@MainActor
final class PushNotificationObserver: PushNotificationObserving {
    private weak var handler: PushNotificationHandling?
    private var observers: [any NSObjectProtocol] = []

    func startObserving(handler: PushNotificationHandling) {
        self.handler = handler
        stopObserving()
        observe(.pushNotificationOpened) { [weak self] notification in
            guard let tab = (notification.userInfo?["tab"] as? String)?.trimmedNonEmpty else { return }
            let type = (notification.userInfo?["type"] as? String)?.trimmedNonEmpty
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
