import XCTest
@testable import Reggit

@MainActor
final class BuildWebEngineConfigurationUseCaseTests: XCTestCase {
    func testExecuteRegistersNgrokInterstitialBypassScript() {
        let config = BuildWebEngineConfigurationUseCase().execute(platform: .ios)

        XCTAssertEqual(config.userScripts.count, 1)
        XCTAssertTrue(config.userScripts[0].source.contains("abuse_interstitial"))
    }
}
