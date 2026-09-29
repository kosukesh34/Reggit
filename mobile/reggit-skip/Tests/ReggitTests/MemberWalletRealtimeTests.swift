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
        XCTAssertNil(MemberWalletRealtime.parse(#"{"event":"project.updated","data":"{}"}"#))
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
        XCTAssertNotNil(SecondaryScreenCache.pointHistory)
        XCTAssertNotNil(SecondaryScreenCache.products)
        SecondaryScreenCache.reset()
    }
}
