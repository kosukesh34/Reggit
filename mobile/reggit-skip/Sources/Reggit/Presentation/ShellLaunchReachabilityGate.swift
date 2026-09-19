import Foundation
import Observation
import SkipFuse

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
    private let healthURLString: String
    private let activityMinimum: TimeInterval
    private let reachabilityTimeout: TimeInterval

    init(
        reachabilityChecker: any ShellURLReachabilityChecking = NetworkShellURLReachabilityChecker(),
        healthURLString: String? = nil,
        activityMinimum: TimeInterval = WebShellTiming.splashActivityMinimum,
        reachabilityTimeout: TimeInterval = WebShellTiming.reachabilityTimeout
    ) {
        self.reachabilityChecker = reachabilityChecker
        if let healthURLString {
            self.healthURLString = healthURLString
        } else {
            let config = BundleMobileWebConfigRepository().resolveConfig()
            self.healthURLString = ReggitAPIClient.apiHealthURL(
                baseURL: config.baseUrl,
                projectKey: config.projectKey
            )
        }
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
            alertMessage = reachabilityChecker.unreachableMessage(for: healthURLString)
            showAlert = true
            logger.error("Launch API unreachable url=\(self.healthURLString)")
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
            group.addTask { [reachabilityChecker, healthURLString] in
                await reachabilityChecker.isReachable(healthURLString)
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
