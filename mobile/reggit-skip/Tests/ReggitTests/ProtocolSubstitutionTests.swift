import XCTest
@testable import Reggit

final class ProtocolSubstitutionTests: XCTestCase {
    func testFakeReachabilityCheckerIsSubstitutable() async {
        var checker: any ShellURLReachabilityChecking = FakeShellURLReachabilityChecker(isReachableResult: true)
        XCTAssertTrue(await checker.isReachable("https://example.com"))

        checker = FakeShellURLReachabilityChecker(isReachableResult: false)
        XCTAssertFalse(await checker.isReachable("https://example.com"))
    }

    func testFakeDeviceProviderIsSubstitutable() {
        let fake: any NativeDeviceContextProvider = FakeNativeDeviceContextProvider()
        let production: any NativeDeviceContextProvider = DefaultNativeDeviceContextProvider()

        XCTAssertEqual(fake.deviceIdentifier, "test-device-id")
        XCTAssertEqual(fake.pushToken, "fake-push-token")
        XCTAssertFalse(production.deviceIdentifier.isEmpty)
    }

    @MainActor
    func testFakeScriptExecutorIsSubstitutable() async {
        let fake = FakeWebViewScriptExecutor()
        let executor: any WebViewScriptExecuting = fake
        executor.run("window.test = 1")
        fake.normalizedResult = "{}"
        XCTAssertEqual(await executor.runAndNormalize("return '{}'"), "{}")
        XCTAssertEqual(fake.executedScripts.count, 2)
    }

    @MainActor
    func testFakeAppConfigLoaderIsSubstitutable() async {
        let loader: any AppConfigLoading = FakeAppConfigLoader()
        let fake = loader as! FakeAppConfigLoader
        fake.webFetchResult = AppConfig(
            projectName: "Fake",
            tabs: AppTabItem.fallbackTabs,
            primaryColor: "#ffffff"
        )

        let config = await loader.fetchViaWeb()
        XCTAssertEqual(config?.projectName, "Fake")
    }

    func testBuildNativeInjectionScriptUseCaseIsSubstitutable() {
        let useCase: any NativeInjectionScriptBuilding = BuildNativeInjectionScriptUseCase()
        let config = MobileWebShellConfig(baseUrl: "https://example.com", projectKey: "default", fixedTab: "products")
        let device = FakeNativeDeviceContextProvider()

        let script = useCase.execute(config: config, device: device, activeTab: "products", applyInitialTab: true)

        XCTAssertTrue(script.contains("native-app-embed"))
        XCTAssertTrue(script.contains("test-device-id"))
    }
}
