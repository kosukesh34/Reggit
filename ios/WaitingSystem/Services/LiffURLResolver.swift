import Foundation

struct LiffURLResolver: LiffURLResolving {

    func resolve(settings: AppSettings, qrToken: String?, ticketId: Int) -> String {
        let token = (qrToken?.isEmpty == false) ? (qrToken ?? String(ticketId)) : String(ticketId)
        let base = normalizeBaseURL(settings.baseURL)
        let projectKey = settings.projectKey.trimmingCharacters(in: .whitespacesAndNewlines)
        let key = projectKey.isEmpty ? "default" : projectKey
        let encodedKey = key.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? key
        let encodedToken = token.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? token
        return "\(base)/queue/open/\(encodedKey)/\(encodedToken)"
    }

    // MARK: - Internal

    private func normalizeBaseURL(_ value: String) -> String {
        let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
        return trimmed.hasSuffix("/") ? String(trimmed.dropLast()) : trimmed
    }
}
