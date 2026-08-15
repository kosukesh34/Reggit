import XCTest
@testable import Reggit

final class BuildNativeInjectionScriptUseCaseTests: XCTestCase {
    func testExecuteBoundsReactTabsRootForInnerScroll() {
        let script = BuildNativeInjectionScriptUseCase().execute(
            config: MobileWebShellConfig(baseUrl: "https://example.com", projectKey: "default", fixedTab: "products"),
            device: FakeNativeDeviceContextProvider(),
            activeTab: "products"
        )

        XCTAssertTrue(script.contains(".native-app-embed #react-frontend-tabs-root"))
        XCTAssertTrue(script.contains(".native-app-embed .tab-content.active"))
        XCTAssertTrue(script.contains(".native-app-embed .app-shell"))
        XCTAssertFalse(script.contains("hideWebTabBar"))
        XCTAssertFalse(script.contains(".native-app-embed .bottom-nav"))
        XCTAssertTrue(script.contains("url.hash = \"/menu\""))
        XCTAssertTrue(script.contains("liff-ui-navigate"))
    }

    func testRedirectGuardDoesNotTreatNgrokHostItselfAsInterception() {
        let script = BuildNativeInjectionScriptUseCase().execute(
            config: MobileWebShellConfig(
                baseUrl: "https://unadministrable-idiocratically-annalee.ngrok-free.dev",
                projectKey: "default",
                fixedTab: "products"
            ),
            device: FakeNativeDeviceContextProvider(),
            activeTab: "products"
        )

        XCTAssertTrue(script.contains("isNgrokInterstitialPage"))
        XCTAssertFalse(script.contains("isNgrokInterception"))
        XCTAssertFalse(script.contains("currentHost.includes('ngrok-free.dev')"))
    }
}
