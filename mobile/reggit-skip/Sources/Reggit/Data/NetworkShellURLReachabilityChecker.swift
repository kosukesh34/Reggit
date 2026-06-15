import Foundation

struct NetworkShellURLReachabilityChecker: ShellURLReachabilityChecking {
    func isReachable(_ urlString: String) async -> Bool {
        guard let url = URL(string: urlString) else { return false }
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = WebShellTiming.reachabilityTimeout
        request.setValue(WebShellHTTP.ngrokSkipBrowserWarning, forHTTPHeaderField: "ngrok-skip-browser-warning")
        do {
            let (_, response) = try await URLSession.shared.data(for: request)
            guard let http = response as? HTTPURLResponse else { return false }
            return (200 ... 399).contains(http.statusCode)
        } catch {
            logger.warning("Shell URL reachability check failed: \(error.localizedDescription)")
            return false
        }
    }

    func unreachableMessage(for urlString: String) -> String {
        """
        指定のURLに接続できませんでした。
        ネットワークとサーバー（ngrok 等）の起動を確認してください。

        URL: \(urlString)
        """
    }
}
