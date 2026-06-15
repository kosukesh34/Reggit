import Foundation

protocol ShellURLReachabilityChecking {
    func isReachable(_ urlString: String) async -> Bool
    func unreachableMessage(for urlString: String) -> String
}
