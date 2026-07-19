import Foundation

/// `reggit://queue?project_key=...&queue_token=...` を解釈して WebView 側へ渡す。
@MainActor
public enum QueueDeepLinkHandler {
    @discardableResult
    public static func handle(_ url: URL) -> Bool {
        guard url.scheme == NativeAppIdentity.urlScheme else { return false }
        guard url.host == "queue" else { return false }

        let components = URLComponents(url: url, resolvingAgainstBaseURL: false)
        let items = components?.queryItems ?? []
        let queueToken = items.first(where: { $0.name == "queue_token" })?.value?
            .trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        guard !queueToken.isEmpty else { return false }

        MobileWebShellSession.viewModel().handleQueueDeepLink(queueToken: queueToken)
        return true
    }
}
