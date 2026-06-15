import SkipWeb

@MainActor
struct WebViewScriptExecutor: WebViewScriptExecuting {
    let navigator: WebViewNavigator

    func run(_ script: String) {
        Task { @MainActor in
            _ = try? await navigator.evaluateJavaScript(script)
        }
    }

    func runAndNormalize(_ script: String) async -> String? {
        guard let raw = try? await navigator.evaluateJavaScript(script) else { return nil }
        let normalized = JavaScriptEvaluateResult.normalize(raw)
        return normalized.isEmpty ? nil : normalized
    }

    func inspectPageVisibility() async -> PageInspection {
        guard let raw = try? await navigator.evaluateJavaScript(MobileWebShellScripts.pageVisibilityInspection()) else {
            return PageInspection(visible: false, summary: "js_eval_failed")
        }
        return JavaScriptEvaluateResult.parsePageVisibility(from: raw)
    }

    func forceBootstrap() async -> String {
        guard let raw = try? await navigator.evaluateJavaScript(MobileWebShellScripts.forceBootstrap()) else {
            return "js_eval_failed"
        }
        return JavaScriptEvaluateResult.normalize(raw)
    }
}
