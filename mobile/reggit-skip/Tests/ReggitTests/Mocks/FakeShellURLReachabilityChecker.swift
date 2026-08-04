import Foundation
@testable import Reggit

final class FakeShellURLReachabilityChecker: ShellURLReachabilityChecking, @unchecked Sendable {
    var isReachableResult = true
    var delayNanoseconds: UInt64 = 0
    private(set) var checkedURLs: [String] = []
    private(set) var checkCount = 0

    init(isReachableResult: Bool = true, delayNanoseconds: UInt64 = 0) {
        self.isReachableResult = isReachableResult
        self.delayNanoseconds = delayNanoseconds
    }

    func isReachable(_ urlString: String) async -> Bool {
        checkCount += 1
        checkedURLs.append(urlString)
        if delayNanoseconds > 0 {
            try? await Task.sleep(nanoseconds: delayNanoseconds)
        }
        return isReachableResult
    }

    func unreachableMessage(for urlString: String) -> String {
        "fake unreachable: \(urlString)"
    }
}
