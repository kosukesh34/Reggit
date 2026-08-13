import XCTest
@testable import Reggit

final class NativeTabHashPathTests: XCTestCase {
    func testPathMatchesLiffHashRouterTabs() {
        XCTAssertEqual(NativeTabHashPath.path(for: "home"), "/home")
        XCTAssertEqual(NativeTabHashPath.path(for: "member"), "/membership")
        XCTAssertEqual(NativeTabHashPath.path(for: "card"), "/membership")
        XCTAssertEqual(NativeTabHashPath.path(for: "coupons"), "/coupon")
        XCTAssertEqual(NativeTabHashPath.path(for: "stamps"), "/stamp")
        XCTAssertEqual(NativeTabHashPath.path(for: "queue"), "/queue")
        XCTAssertEqual(NativeTabHashPath.path(for: "visit"), "/queue")
        XCTAssertEqual(NativeTabHashPath.path(for: "products"), "/menu")
        XCTAssertEqual(NativeTabHashPath.path(for: "orders"), "/orderHistory")
        XCTAssertEqual(NativeTabHashPath.path(for: "announcements"), "/news")
        XCTAssertEqual(NativeTabHashPath.path(for: "gallery"), "/gallery")
        XCTAssertEqual(NativeTabHashPath.path(for: "reservations"), "/reservation")
        XCTAssertEqual(NativeTabHashPath.path(for: "designlab"), "/designlab")
    }
}

final class MobileWebShellScriptsTests: XCTestCase {
    func testSwitchTabSetsHashRouterPath() {
        let script = MobileWebShellScripts.switchTab("queue")

        XCTAssertTrue(script.contains("url.hash = \"/queue\""))
        XCTAssertTrue(script.contains("liff-ui-navigate"))
        XCTAssertTrue(script.contains("__NATIVE_TARGET_TAB__"))
        XCTAssertFalse(script.contains(".tab-content"))
    }

    func testQueueDeepLinkSetsHashAndPersistsToken() {
        let script = MobileWebShellScripts.queueDeepLink(queueToken: "tok-abc")

        XCTAssertTrue(script.contains("url.hash = \"/queue\""))
        XCTAssertTrue(script.contains("queue_token"))
        XCTAssertTrue(script.contains("mobile_order_pending_queue_token"))
        XCTAssertTrue(script.contains("tok-abc"))
        XCTAssertTrue(script.contains("liff-ui-navigate"))
    }

    func testPageVisibilityInspectsAppShell() {
        let script = MobileWebShellScripts.pageVisibilityInspection()

        XCTAssertTrue(script.contains(".app-shell"))
        XCTAssertTrue(script.contains("#app-container"))
        XCTAssertTrue(script.contains("reggit-liff-ui"))
    }

    func testNotificationDeepLinkSetsHashRouterPath() {
        let script = MobileWebShellScripts.notificationDeepLink(tab: "coupons", isCoupon: true)

        XCTAssertTrue(script.contains("url.hash = \"/coupon\""))
        XCTAssertTrue(script.contains("liff-ui-navigate"))
        XCTAssertTrue(script.contains("couponReceived: true"))
    }
}
