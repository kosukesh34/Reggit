import SkipWeb
import XCTest
@testable import Reggit

@MainActor
final class MobileWebShellViewModelTests: XCTestCase {
    func testNotificationDeepLinkDefersUntilWebReady() async {
        let scriptExecutor = FakeWebViewScriptExecutor()
        let whiteScreenMonitor = FakeWhiteScreenMonitor()
        let reachability = FakeShellURLReachabilityChecker(isReachableResult: true)
        let viewModel = makeViewModel(
            scriptExecutor: scriptExecutor,
            whiteScreenMonitor: whiteScreenMonitor,
            reachabilityChecker: reachability
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

    func testUnreachableURLShowsFallback() async {
        let reachability = FakeShellURLReachabilityChecker(isReachableResult: false)
        let viewModel = makeViewModel(reachabilityChecker: reachability)

        viewModel.onAppear()
        try? await Task.sleep(nanoseconds: 50_000_000)

        XCTAssertFalse(viewModel.isLoading)
        XCTAssertTrue(viewModel.showWhiteScreenFallback)
        XCTAssertFalse(viewModel.showNativeTabBar)
        XCTAssertTrue(viewModel.fallbackMessage.contains("fake unreachable"))
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
        scriptExecutor.executedScripts.removeAll()

        viewModel.selectTab("orders")

        XCTAssertEqual(viewModel.activeTab, "orders")
        XCTAssertTrue(scriptExecutor.executedScripts.contains { $0.contains("orders") })
    }

    private func makeViewModel(
        scriptExecutor: FakeWebViewScriptExecutor = FakeWebViewScriptExecutor(),
        whiteScreenMonitor: FakeWhiteScreenMonitor = FakeWhiteScreenMonitor(),
        reachabilityChecker: FakeShellURLReachabilityChecker = FakeShellURLReachabilityChecker()
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
            pushNotificationObserver: FakePushNotificationObserver(),
            reachabilityChecker: reachabilityChecker
        )
    }
}
