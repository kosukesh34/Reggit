import Foundation
@testable import Reggit

@MainActor
final class FakeWebViewScriptExecutor: WebViewScriptExecuting {
    private(set) var executedScripts: [String] = []
    var normalizedResult: String?
    var pageInspection = PageInspection(visible: true, summary: "fake|visible|1")
    var bootstrapResult = "fake_bootstrap"

    func run(_ script: String) {
        executedScripts.append(script)
    }

    func runAndNormalize(_ script: String) async -> String? {
        executedScripts.append(script)
        return normalizedResult
    }

    func inspectPageVisibility() async -> PageInspection {
        pageInspection
    }

    func forceBootstrap() async -> String {
        bootstrapResult
    }

    func resetExecutedScripts() {
        executedScripts.removeAll()
    }
}
