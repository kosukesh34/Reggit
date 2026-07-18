import Foundation

protocol ShellURLReachabilityChecking: Sendable {
    func isReachable(_ urlString: String) async -> Bool
    func unreachableMessage(for urlString: String) -> String
}
