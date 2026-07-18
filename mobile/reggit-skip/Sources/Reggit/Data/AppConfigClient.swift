import Foundation

struct AppConfigClient {
    let baseUrl: String
    let projectKey: String

    /// iOS 向け: ネイティブ URLSession で取得（WebView 起動前にタブを表示したい場合）
    func fetchConfig() async -> AppConfig? {
        #if os(Android)
        return nil
        #else
        let urlString = "\(baseUrl)/api/v1/app-config?project_key=\(projectKey)"
        guard let url = URL(string: urlString) else { return nil }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.timeoutInterval = 8

        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            guard let httpResponse = response as? HTTPURLResponse, httpResponse.statusCode == 200 else {
                return nil
            }
            return Self.parse(data)
        } catch {
            logger.warning("AppConfig fetch failed: \(error.localizedDescription)")
            return nil
        }
        #endif
    }

    /// WebView 内同期 XHR で app-config を取得するスクリプト（iOS/Android 共通）
    /// evaluateJavascript の JSON エスケープ問題を避けるため、区切り文字形式で返す。
    static func syncFetchScript(baseUrl: String, projectKey: String) -> String {
        let urlString = "\(baseUrl)/api/v1/app-config?project_key=\(projectKey)"
        return """
        (function() {
            try {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', \(urlString.jsStringLiteral), false);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.send(null);
                if (xhr.status < 200 || xhr.status >= 300) return '';
                var full = JSON.parse(xhr.responseText || '{}');
                var theme = full.theme || {};
                var projectName = String(full.project_name || '');
                var primaryColor = String(theme.primary_color || '#000000');
                var rs = String.fromCharCode(30);
                var tab = String.fromCharCode(9);
                var nl = String.fromCharCode(10);
                var tabLines = (full.tabs || []).map(function(tabItem) {
                    return [
                        String(tabItem.key || ''),
                        String(tabItem.label || ''),
                        String(tabItem.icon || ''),
                        String(tabItem.sf_symbol || '')
                    ].join(tab);
                }).join(nl);
                return [projectName, primaryColor, tabLines].join(rs);
            } catch (e) {}
            return '';
        })();
        """
    }

    static func parseJSONString(_ jsonString: String) -> AppConfig? {
        let trimmed = jsonString.trimmingCharacters(in: .whitespacesAndNewlines)
        if let config = parseDelimitedPayload(trimmed) {
            return config
        }
        if let data = trimmed.data(using: .utf8), !data.isEmpty {
            if let config = parse(data) {
                return config
            }
            if let decoded = try? JSONSerialization.jsonObject(with: data) as? String,
               let nestedData = decoded.data(using: .utf8) {
                return parse(nestedData)
            }
        }
        return nil
    }

    /// `projectName RS primaryColor RS tabLines`（tab は `key\\tlabel\\ticon\\tsf` を LF 区切り）
    static func parseDelimitedPayload(_ payload: String) -> AppConfig? {
        let normalized = payload
            .replacingOccurrences(of: "\\u001e", with: "\u{001e}")
            .replacingOccurrences(of: "\\u001E", with: "\u{001e}")
            .replacingOccurrences(of: "\\t", with: "\t")
            .replacingOccurrences(of: "\\n", with: "\n")
        let separator = "\u{001e}"
        guard normalized.contains(separator) else { return nil }
        let parts = normalized.components(separatedBy: separator)
        guard parts.count >= 3 else { return nil }

        let projectName = parts[0].isEmpty ? "Reggit" : parts[0]
        let primaryColor = parts[1].isEmpty ? "#000000" : parts[1]
        var tabs: [AppTabItem] = []
        for line in parts[2].split(separator: "\n", omittingEmptySubsequences: true) {
            let cols = line.split(separator: "\t", maxSplits: 3, omittingEmptySubsequences: false).map(String.init)
            guard let key = cols.first, !key.isEmpty else { continue }
            let label = cols.count > 1 && !cols[1].isEmpty ? cols[1] : key
            let icon = cols.count > 2 && !cols[2].isEmpty ? cols[2] : "fa-circle"
            let sfSymbol = cols.count > 3 && !cols[3].isEmpty ? cols[3] : "circle.fill"
            tabs.append(AppTabItem(id: key, key: key, label: label, icon: icon, sfSymbol: sfSymbol))
        }
        if tabs.isEmpty {
            tabs = AppTabItem.fallbackTabs
        }
        return AppConfig(projectName: projectName, tabs: tabs, primaryColor: primaryColor)
    }

    static func parse(_ data: Data) -> AppConfig? {
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else { return nil }

        let projectName = json["project_name"] as? String ?? "Reggit"
        let primaryColor = (json["theme"] as? [String: Any])?["primary_color"] as? String ?? "#000000"

        var tabs: [AppTabItem] = []
        if let rawTabs = json["tabs"] as? [[String: Any]] {
            for rawTab in rawTabs {
                guard let key = rawTab["key"] as? String else { continue }
                let label = rawTab["label"] as? String ?? key
                let icon = rawTab["icon"] as? String ?? "fa-circle"
                let sfSymbol = rawTab["sf_symbol"] as? String ?? "circle.fill"
                tabs.append(AppTabItem(id: key, key: key, label: label, icon: icon, sfSymbol: sfSymbol))
            }
        }

        if tabs.isEmpty {
            tabs = AppTabItem.fallbackTabs
        }

        return AppConfig(projectName: projectName, tabs: tabs, primaryColor: primaryColor)
    }
}
