import Foundation

final class FakeShellURLReachabilityChecker: ShellURLReachabilityChecking {
    var isReachableResult = true
    private(set) var checkedURLs: [String] = []

    func isReachable(_ urlString: String) async -> Bool {
        checkedURLs.append(urlString)
        return isReachableResult
    }

    func unreachableMessage(for urlString: String) -> String {
        "fake unreachable: \(urlString)"
    }
}
