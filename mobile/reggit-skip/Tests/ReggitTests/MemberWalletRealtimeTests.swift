import XCTest
@testable import Reggit

final class MemberWalletRealtimeTests: XCTestCase {
    func testConnectionAndWalletEvents() {
        XCTAssertEqual(MemberWalletRealtime.parse(#"{"event":"pusher:ping","data":{}}"#), .ping)
        XCTAssertEqual(
            MemberWalletRealtime.parse(#"{"event":"pusher:connection_established","data":"{\"socket_id\":\"123.456\"}"}"#),
            .connected("123.456")
        )
        XCTAssertEqual(
            MemberWalletRealtime.parse(#"{"event":"wallet.updated","data":"{\"pending_lottery_count\":2}"}"#),
            .walletUpdated(points: nil, stampsTotal: nil, pendingCount: 2)
        )
        XCTAssertEqual(
            MemberWalletRealtime.parse(#"{"event":"wallet.updated","data":{"points":75,"stamps_total":3,"pending_lottery_count":1}}"#),
            .walletUpdated(points: 75, stampsTotal: 3, pendingCount: 1)
        )
        XCTAssertEqual(
            MemberWalletRealtime.parse(#"{"event":"data.updated","data":{"topic":"queue"}}"#),
            .projectUpdated("queue")
        )
        XCTAssertEqual(
            MemberWalletRealtime.parse(#"{"event":"pusher_internal:subscription_succeeded","channel":"project.default.data","data":"{}"}"#),
            .subscribed("project.default.data")
        )
        XCTAssertNil(MemberWalletRealtime.parse(#"{"event":"project.updated","data":"{}"}"#))
    }

    @MainActor
    func testOrderEventsRefreshCheckoutAndHistoryRegardlessOfSelectedTab() {
        let model = NativeRootViewModel()
        model.selectedTab = "home"
        SecondaryScreenCache.orders = []
        model.handleProjectUpdate(topic: "orders")
        XCTAssertEqual(model.productsRevision, 1)
        XCTAssertEqual(model.ordersRevision, 1)
        XCTAssertEqual(model.lotteryRevision, 1)
        XCTAssertNil(SecondaryScreenCache.orders)
        model.handleForegroundNotification(notificationType: "order_completed")
        XCTAssertEqual(model.ordersRevision, 2)
        SecondaryScreenCache.reset()
    }

    @MainActor
    func testQueueAndCouponNotificationsInvalidateTheirScreens() {
        let model = NativeRootViewModel()
        SecondaryScreenCache.queue = nil
        SecondaryScreenCache.coupons = []
        model.handleForegroundNotification(notificationType: "queue_called")
        XCTAssertEqual(model.queueRevision, 1)
        model.handleForegroundNotification(notificationType: "coupon_issued")
        XCTAssertNil(SecondaryScreenCache.coupons)
        XCTAssertEqual(model.screenRevisions["coupons"], 1)
        SecondaryScreenCache.reset()
    }

    @MainActor
    func testRelatedPublicUpdatesEvictTheirScreenCachesOnly() {
        SecondaryScreenCache.orders = []
        SecondaryScreenCache.pointHistory = []
        SecondaryScreenCache.products = []
        SecondaryScreenCache.invalidate(topic: "orders")
        SecondaryScreenCache.invalidate(topic: "points")
        SecondaryScreenCache.invalidate(topic: "products")
        XCTAssertNil(SecondaryScreenCache.orders)
        XCTAssertNil(SecondaryScreenCache.pointHistory)
        XCTAssertNil(SecondaryScreenCache.products)
        SecondaryScreenCache.reset()
    }
}
