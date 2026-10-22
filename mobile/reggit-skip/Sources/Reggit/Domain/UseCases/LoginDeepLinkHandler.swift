import Foundation

/// メールのログインリンク（Laravel `/app-login` → reggit://login?project_key=&member_id=&code=）。
@MainActor
public enum LoginDeepLinkHandler {
    @discardableResult
    public static func handle(_ url: URL) -> Bool {
        guard url.scheme == NativeAppIdentity.urlScheme, url.host == "login" else { return false }
        let items = URLComponents(url: url, resolvingAgainstBaseURL: false)?.queryItems ?? []
        let projectKey = items.first(where: { $0.name == "project_key" })?.value ?? ""
        let memberID = Int(items.first(where: { $0.name == "member_id" })?.value ?? "") ?? 0
        let code = items.first(where: { $0.name == "code" })?.value ?? ""
        guard !projectKey.isEmpty, memberID > 0, !code.isEmpty else { return false }
        NativeAppSession.rootViewModel().handleLoginLink(projectKey: projectKey, memberId: memberID, code: code)
        return true
    }

    /// Android の Intent から文字列で受け取る入口
    @discardableResult
    public static func handle(urlString: String) -> Bool {
        guard let url = URL(string: urlString) else { return false }
        return handle(url)
    }
}
