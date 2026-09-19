import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

struct AppConfigClient {
    let baseUrl: String
    let projectKey: String

    func fetchConfig() async -> AppConfig? {
        let urlString = "\(baseUrl)/api/v1/app-config?project_key=\(projectKey)"
        guard let url = URL(string: urlString) else { return nil }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue(WebShellHTTP.ngrokSkipBrowserWarning, forHTTPHeaderField: "ngrok-skip-browser-warning")
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
    }

    static func parse(_ data: Data) -> AppConfig? {
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else { return nil }

        let projectName = json["project_name"] as? String ?? "Reggit"
        let primaryColor = (json["theme"] as? [String: Any])?["primary_color"] as? String ?? "#173F35"
        let navigationHeaderImageURL = ((json["header"] as? [String: Any])?["image_url"] as? String)?.trimmedNonEmpty
        let navigationHeaderBackgroundColor = ((json["header"] as? [String: Any])?["background_color"] as? String)?.trimmedNonEmpty ?? "#FFFFFF"
        let homeImageURL = ((json["home"] as? [String: Any])?["image_url"] as? String)?.trimmedNonEmpty
        let homeMenuLayout = ((json["home"] as? [String: Any])?["menu_layout"] as? String)?.trimmedNonEmpty ?? "grid_3x2"
        let memberScreen = json["member_screen"] as? [String: Any] ?? [:]

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

        return AppConfig(
            projectName: projectName,
            tabs: tabs,
            primaryColor: primaryColor,
            navigationHeaderImageURL: navigationHeaderImageURL,
            navigationHeaderBackgroundColor: navigationHeaderBackgroundColor,
            homeImageURL: homeImageURL,
            homeMenuLayout: homeMenuLayout,
            memberShowRankProgress: memberScreen["show_rank_progress"] as? Bool ?? true,
            memberShowPointHistory: memberScreen["show_point_history"] as? Bool ?? true,
            memberShowCouponActivity: memberScreen["show_coupon_activity"] as? Bool ?? true
        )
    }
}
