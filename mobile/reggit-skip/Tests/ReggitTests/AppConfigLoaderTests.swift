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
        executor.normalizedResult = """
        {"project_name":"Test","tabs":[],"theme":{"primary_color":"#111111"}}
        """
        let loader = AppConfigLoader(shellConfig: shellConfig, scriptExecutor: executor)

        let config = await loader.fetchViaWeb()

        XCTAssertEqual(config?.projectName, "Test")
        XCTAssertFalse(executor.executedScripts.isEmpty)
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
