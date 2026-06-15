import Foundation

@MainActor
protocol PushNotificationObserving: AnyObject {
    func startObserving(handler: PushNotificationHandling)
    func stopObserving()
}
