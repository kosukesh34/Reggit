import SkipWeb
import XCTest
@testable import Reggit

@MainActor
final class MobileWebShellViewModelTests: XCTestCase {
    func testNotificationDeepLinkDefersUntilWebReady() async {
        let scriptExecutor = FakeWebViewScriptExecutor()
        let whiteScreenMonitor = FakeWhiteScreenMonitor()
        let viewModel = makeViewModel(
            scriptExecutor: scriptExecutor,
            whiteScreenMonitor: whiteScreenMonitor
        )

        viewModel.onAppear()
        try? await Task.sleep(nanoseconds: 50_000_000)

        viewModel.handleNotificationDeepLink(tab: "coupons", notificationType: "coupon_issued")
        XCTAssertEqual(viewModel.activeTab, "coupons")
        XCTAssertTrue(scriptExecutor.executedScripts.filter { $0.contains("__nativeOpenTab__") }.isEmpty)

        whiteScreenMonitor.simulateContentVisible()
        XCTAssertTrue(
            scriptExecutor.executedScripts.contains { $0.contains("__nativeOpenTab__") && $0.contains("couponReceived: true") }
        )
    }

    func testOnAppearStartsWhiteScreenMonitor() async {
        let whiteScreenMonitor = FakeWhiteScreenMonitor()
        let viewModel = makeViewModel(whiteScreenMonitor: whiteScreenMonitor)

        viewModel.onAppear()
        try? await Task.sleep(nanoseconds: 20_000_000)

        XCTAssertTrue(whiteScreenMonitor.startCount >= 1)
        XCTAssertTrue(viewModel.isLoading)
    }

    func testSelectTabRunsSwitchScriptWhenReady() {
        let scriptExecutor = FakeWebViewScriptExecutor()
        let whiteScreenMonitor = FakeWhiteScreenMonitor()
        let viewModel = makeViewModel(
            scriptExecutor: scriptExecutor,
            whiteScreenMonitor: whiteScreenMonitor
        )

        viewModel.onAppear()
        whiteScreenMonitor.simulateContentVisible()
        XCTAssertFalse(viewModel.showNativeTabBar)
        scriptExecutor.resetExecutedScripts()

        viewModel.selectTab("orders")

        XCTAssertEqual(viewModel.activeTab, "orders")
        XCTAssertTrue(scriptExecutor.executedScripts.contains { $0.contains("orders") })
        XCTAssertTrue(scriptExecutor.executedScripts.contains { $0.contains("url.hash = \"/orderHistory\"") })
    }

    func testQueueDeepLinkSetsHashRouterPathWhenReady() {
        let scriptExecutor = FakeWebViewScriptExecutor()
        let whiteScreenMonitor = FakeWhiteScreenMonitor()
        let viewModel = makeViewModel(
            scriptExecutor: scriptExecutor,
            whiteScreenMonitor: whiteScreenMonitor
        )

        viewModel.onAppear()
        whiteScreenMonitor.simulateContentVisible()
        scriptExecutor.resetExecutedScripts()

        viewModel.handleQueueDeepLink(queueToken: "tok-queue-1")

        XCTAssertEqual(viewModel.activeTab, "queue")
        XCTAssertTrue(scriptExecutor.executedScripts.contains { $0.contains("url.hash = \"/queue\"") })
        XCTAssertTrue(scriptExecutor.executedScripts.contains { $0.contains("mobile_order_pending_queue_token") })
        XCTAssertTrue(scriptExecutor.executedScripts.contains { $0.contains("tok-queue-1") })
    }

    private func makeViewModel(
        scriptExecutor: FakeWebViewScriptExecutor = FakeWebViewScriptExecutor(),
        whiteScreenMonitor: FakeWhiteScreenMonitor = FakeWhiteScreenMonitor()
    ) -> MobileWebShellViewModel {
        let config = MobileWebShellConfig(
            baseUrl: "https://example.com",
            projectKey: "default",
            fixedTab: "products"
        )
        let navigator = WebViewNavigator()
        return MobileWebShellViewModel(
            config: config,
            webConfiguration: WebEngineConfiguration(),
            navigator: navigator,
            deviceProvider: FakeNativeDeviceContextProvider(),
            buildScriptUseCase: BuildNativeInjectionScriptUseCase(),
            scriptExecutor: scriptExecutor,
            appConfigLoader: FakeAppConfigLoader(),
            whiteScreenMonitor: whiteScreenMonitor,
            pushNotificationObserver: FakePushNotificationObserver()
        )
    }
}
