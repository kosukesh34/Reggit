import XCTest
@testable import Reggit

@MainActor
final class ShellLaunchReachabilityGateTests: XCTestCase {
    func testUnreachableKeepsPhaseUnreachableAndShowsAlert() async {
        let checker = FakeShellURLReachabilityChecker(isReachableResult: false)
        let gate = ShellLaunchReachabilityGate(
            reachabilityChecker: checker,
            liffURLString: "https://example.com/liff",
            activityMinimum: 0.05,
            reachabilityTimeout: 1
        )

        await gate.start()

        XCTAssertEqual(gate.phase, .unreachable)
        XCTAssertTrue(gate.showAlert)
        XCTAssertEqual(gate.alertTitle, ShellUserFacingMessages.connectionTitle)
        XCTAssertTrue(gate.alertMessage.contains("fake unreachable"))
        XCTAssertEqual(checker.checkCount, 1)
    }

    func testReachableAdvancesPhase() async {
        let checker = FakeShellURLReachabilityChecker(isReachableResult: true)
        let gate = ShellLaunchReachabilityGate(
            reachabilityChecker: checker,
            liffURLString: "https://example.com/liff",
            activityMinimum: 0.05,
            reachabilityTimeout: 1
        )

        await gate.start()

        XCTAssertEqual(gate.phase, .reachable)
        XCTAssertFalse(gate.showAlert)
        XCTAssertEqual(checker.checkCount, 1)
    }

    func testRetryRunsCheckAgain() async {
        let checker = FakeShellURLReachabilityChecker(isReachableResult: false)
        let gate = ShellLaunchReachabilityGate(
            reachabilityChecker: checker,
            liffURLString: "https://example.com/liff",
            activityMinimum: 0.05,
            reachabilityTimeout: 1
        )

        await gate.start()
        XCTAssertEqual(gate.phase, .unreachable)

        checker.isReachableResult = true
        await gate.retry()

        XCTAssertEqual(gate.phase, .reachable)
        XCTAssertFalse(gate.showAlert)
        XCTAssertEqual(checker.checkCount, 2)
    }

    func testActivityMinimumElapsedBeforeResult() async {
        let checker = FakeShellURLReachabilityChecker(isReachableResult: true)
        let gate = ShellLaunchReachabilityGate(
            reachabilityChecker: checker,
            liffURLString: "https://example.com/liff",
            activityMinimum: 0.2,
            reachabilityTimeout: 1
        )

        let started = Date()
        await gate.start()
        let elapsed = Date().timeIntervalSince(started)

        XCTAssertEqual(gate.phase, .reachable)
        XCTAssertGreaterThanOrEqual(elapsed, 0.18)
    }
}
