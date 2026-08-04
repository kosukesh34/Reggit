import Foundation
import Observation

@MainActor
@Observable
final class ShellLaunchReachabilityGate {
    enum Phase: Equatable {
        case idle
        case checking
        case reachable
        case unreachable
    }

    private(set) var phase: Phase = .idle
    private(set) var alertTitle = ShellUserFacingMessages.connectionTitle
    private(set) var alertMessage = ""
    var showAlert = false

    private let reachabilityChecker: any ShellURLReachabilityChecking
    private let liffURLString: String
    private let activityMinimum: TimeInterval
    private let reachabilityTimeout: TimeInterval

    init(
        reachabilityChecker: any ShellURLReachabilityChecking = NetworkShellURLReachabilityChecker(),
        liffURLString: String? = nil,
        activityMinimum: TimeInterval = WebShellTiming.splashActivityMinimum,
        reachabilityTimeout: TimeInterval = WebShellTiming.reachabilityTimeout
    ) {
        self.reachabilityChecker = reachabilityChecker
        self.liffURLString = liffURLString
            ?? BundleMobileWebConfigRepository().resolveConfig().liffURLString
        self.activityMinimum = activityMinimum
        self.reachabilityTimeout = reachabilityTimeout
    }

    func start() async {
        phase = .checking
        showAlert = false
        alertTitle = ShellUserFacingMessages.connectionTitle
        alertMessage = ""

        async let reachable = checkReachabilityWithTimeout()
        let minimumNanos = UInt64(activityMinimum * 1_000_000_000)
        try? await Task.sleep(nanoseconds: minimumNanos)
        let result = await reachable

        if result == false {
            phase = .unreachable
            alertMessage = reachabilityChecker.unreachableMessage(for: liffURLString)
            showAlert = true
            logger.error("Launch shell unreachable url=\(self.liffURLString)")
            return
        }
        phase = .reachable
    }

    func retry() async {
        await start()
    }

    /// `nil` = タイムアウト（到達不能とはみなさず起動を進める）
    private func checkReachabilityWithTimeout() async -> Bool? {
        await withTaskGroup(of: Bool?.self) { group in
            group.addTask { [reachabilityChecker, liffURLString] in
                await reachabilityChecker.isReachable(liffURLString)
            }
            group.addTask { [reachabilityTimeout] in
                let nanos = UInt64(reachabilityTimeout * 1_000_000_000)
                try? await Task.sleep(nanoseconds: nanos)
                return nil
            }
            let first = await group.next() ?? nil
            group.cancelAll()
            return first ?? nil
        }
    }
}
