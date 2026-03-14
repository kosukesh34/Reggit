import Foundation

struct LiffURLResolver: LiffURLResolving {

    func resolve(settings: AppSettings, qrToken: String?, ticketId: Int) -> String {
        let queueParamName = (qrToken?.isEmpty == false) ? "queue_token" : "queue_id"
        let queueParamValue = qrToken ?? String(ticketId)
        let base = resolvePreferredBase(settings: settings)
        let url = base.appendingQueueLinkParams(
            projectKey: settings.projectKey,
            queueParamName: queueParamName,
            queueParamValue: queueParamValue
        )
        return url.absoluteString
    }

    // MARK: - Internal

    private func resolvePreferredBase(settings: AppSettings) -> URL {
        if let liffWeb = liffWebBaseURL(from: settings.liffURL) {
            return liffWeb
        }
        let base = normalizeBaseURL(settings.baseURL)
        let fallback = "\(base)/liff/\(settings.projectKey)"
        return URL(string: fallback) ?? URL(string: settings.baseURL) ?? URL(string: "http://localhost")!
    }

    private func liffWebBaseURL(from liffURL: String) -> URL? {
        guard let url = URL(string: liffURL),
              let components = URLComponents(url: url, resolvingAgainstBaseURL: false) else {
            return nil
        }
        let host = (components.host ?? "").lowercased()
        guard host == "miniapp.line.me" || host == "liff.line.me" else { return nil }

        let liffId = components.path
            .split(separator: "/")
            .map(String.init)
            .last ?? ""
        guard !liffId.isEmpty else { return nil }
        return URL(string: "https://liff.line.me/\(liffId)")
    }

    private func normalizeBaseURL(_ value: String) -> String {
        let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
        return trimmed.hasSuffix("/") ? String(trimmed.dropLast()) : trimmed
    }
}
