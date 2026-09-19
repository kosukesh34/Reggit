import Foundation

enum WalletPassURLBuilder {
    /// issue URL（pass_token 付き）から .pkpass 直ダウンロード URL を組み立てる。
    static func downloadURL(fromIssueURL issueURL: String, baseURL: String, projectKey: String) throws -> String {
        guard let components = URLComponents(string: issueURL),
              let passToken = components.queryItems?.first(where: { $0.name == "pass_token" })?.value,
              !passToken.isEmpty else {
            throw ReggitAPIError.server("Wallet Pass の URL を取得できませんでした")
        }
        var download = URLComponents(string: "\(baseURL)/api/member-wallet/pass")
        download?.queryItems = [
            URLQueryItem(name: "token", value: passToken),
            URLQueryItem(name: "project_key", value: projectKey),
        ]
        guard let absolute = download?.url?.absoluteString else {
            throw ReggitAPIError.invalidURL
        }
        return absolute
    }
}
