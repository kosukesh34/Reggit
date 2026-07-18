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
    }
}
