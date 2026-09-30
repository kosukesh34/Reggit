import XCTest
@testable import Reggit

final class PushNotificationObserverTests: XCTestCase {
    @MainActor
    func testLegacyQueuePushWithoutTabOpensQueue() {
        let observer = PushNotificationObserver()
        let handler = RecordingPushHandler()
        observer.startObserving(handler: handler)
        NotificationCenter.default.post(name: .pushNotificationOpened, object: nil, userInfo: ["type": "queue_called"])
        XCTAssertEqual(handler.openedTabs, ["queue"])
        XCTAssertEqual(PushNotificationObserver.tab(for: "queue_linked"), "queue")
        XCTAssertEqual(PushNotificationObserver.tab(for: "announcement"), "announcements")
        observer.stopObserving()
    }

    @MainActor
    func testStartingObservationKeepsHandlerAndRestartDoesNotDuplicateDelivery() {
        let observer = PushNotificationObserver()
        let handler = RecordingPushHandler()
        observer.startObserving(handler: handler)
        NotificationCenter.default.post(name: .pushNotificationForeground, object: nil, userInfo: ["type": "order_ready"])
        XCTAssertEqual(handler.foregroundTypes, ["order_ready"])
        observer.startObserving(handler: handler)
        NotificationCenter.default.post(name: .pushNotificationOpened, object: nil, userInfo: ["type": "order_confirmed", "tab": "orders"])
        XCTAssertEqual(handler.openedTabs, ["orders"])
        observer.stopObserving()
        NotificationCenter.default.post(name: .pushNotificationForeground, object: nil, userInfo: ["type": "order_completed"])
        XCTAssertEqual(handler.foregroundTypes, ["order_ready"])
    }
}

@MainActor
private final class RecordingPushHandler: PushNotificationHandling {
    var foregroundTypes: [String] = []
    var openedTabs: [String] = []
    func handleNotificationDeepLink(tab: String, notificationType _: String?) { openedTabs.append(tab) }
    func handleForegroundNotification(notificationType: String?) { foregroundTypes.append(notificationType ?? "") }
    func injectPushToken(_: String) async {}
}
