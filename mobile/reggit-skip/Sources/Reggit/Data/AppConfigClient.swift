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
    static func syncFetchScript(baseUrl: String, projectKey: String) -> String {
        let urlString = "\(baseUrl)/api/v1/app-config?project_key=\(projectKey)"
        return """
        (function() {
            try {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', \(urlString.jsStringLiteral), false);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.send(null);
                if (xhr.status >= 200 && xhr.status < 300) return xhr.responseText || '';
            } catch (e) {}
            return '';
        })();
        """
    }

    static func parseJSONString(_ jsonString: String) -> AppConfig? {
        guard let data = jsonString.data(using: .utf8), !data.isEmpty else { return nil }
        return parse(data)
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
