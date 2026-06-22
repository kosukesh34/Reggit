import Foundation

@MainActor
protocol WebViewScriptExecuting {
    func run(_ script: String)
    func runAndNormalize(_ script: String) async -> String?
    func inspectPageVisibility() async -> PageInspection
    func forceBootstrap() async -> String
}
