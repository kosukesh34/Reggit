import Foundation

@MainActor
final class FakePushNotificationObserver: PushNotificationObserving {
    private(set) var isObserving = false

    func startObserving(handler: PushNotificationHandling) {
        isObserving = true
    }

    func stopObserving() {
        isObserving = false
    }
}
