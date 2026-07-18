import XCTest
@testable import Reggit

@MainActor
final class AppConfigLoaderTests: XCTestCase {
    private let shellConfig = MobileWebShellConfig(
        baseUrl: "https://example.com",
        projectKey: "default",
        fixedTab: "products"
    )

    func testApplyDefersWhenTabBarHidden() {
        let loader = AppConfigLoader(
            shellConfig: shellConfig,
            scriptExecutor: FakeWebViewScriptExecutor()
        )
        let config = sampleConfig()

        XCTAssertNil(loader.apply(config, tabBarVisible: false, activeTab: "home", fixedTab: "products"))
        XCTAssertEqual(loader.takePendingConfig()?.projectName, "Test")
        XCTAssertFalse(loader.isLoaded)
    }

    func testApplyReturnsTabsFromConfigWithoutStoringState() {
        let loader = AppConfigLoader(
            shellConfig: shellConfig,
            scriptExecutor: FakeWebViewScriptExecutor()
        )
        let config = sampleConfig()

        let outcome = loader.apply(config, tabBarVisible: true, activeTab: "home", fixedTab: "products")

        XCTAssertEqual(outcome?.tabs.map(\.key), ["home", "products"])
        XCTAssertEqual(outcome?.activeTab, "home")
        XCTAssertTrue(outcome?.shouldSwitchTab ?? false)
        XCTAssertTrue(loader.isLoaded)
    }

    func testApplyFallsBackToFirstTabWhenActiveTabMissing() {
        let loader = AppConfigLoader(
            shellConfig: shellConfig,
            scriptExecutor: FakeWebViewScriptExecutor()
        )
        let config = sampleConfig()

        let outcome = loader.apply(config, tabBarVisible: true, activeTab: "missing", fixedTab: "products")

        XCTAssertEqual(outcome?.activeTab, "home")
    }

    func testFetchViaWebUsesScriptExecutor() async {
        let executor = FakeWebViewScriptExecutor()
        executor.normalizedResult = "Test\u{001e}#111111\u{001e}home\tHome\tfa-home\thouse.fill\nproducts\tProducts\tfa-box\tshippingbox.fill"
        let loader = AppConfigLoader(shellConfig: shellConfig, scriptExecutor: executor)

        let config = await loader.fetchViaWeb()

        XCTAssertEqual(config?.projectName, "Test")
        XCTAssertEqual(config?.tabs.map(\.key), ["home", "products"])
        XCTAssertFalse(executor.executedScripts.isEmpty)
    }

    func testParseDelimitedPayload() {
        let payload = "店舗\u{001e}#007aff\u{001e}home\tホーム\tfa-house\thouse.fill\nproducts\t商品\tfa-utensils\tfork.knife"
        let config = AppConfigClient.parseDelimitedPayload(payload)

        XCTAssertEqual(config?.projectName, "店舗")
        XCTAssertEqual(config?.primaryColor, "#007aff")
        XCTAssertEqual(config?.tabs.map(\.key), ["home", "products"])
        XCTAssertEqual(config?.tabs.map(\.label), ["ホーム", "商品"])
    }

    private func sampleConfig() -> AppConfig {
        AppConfig(
            projectName: "Test",
            tabs: [
                AppTabItem(id: "home", key: "home", label: "Home", icon: "fa-home", sfSymbol: "house.fill"),
                AppTabItem(id: "products", key: "products", label: "Products", icon: "fa-box", sfSymbol: "shippingbox.fill"),
            ],
            primaryColor: "#000000"
        )
    }
}
