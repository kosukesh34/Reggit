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
        let productDisplayLayout = ((json["products"] as? [String: Any])?["display_layout"] as? String)?.trimmedNonEmpty ?? "list_with_image"
        let memberScreen = json["member_screen"] as? [String: Any] ?? [:]

        var tabs = parseMenuItems(json["tabs"])

        if tabs.isEmpty {
            tabs = AppTabItem.fallbackTabs
        }
        let navigation = json["navigation"] as? [String: Any]
        let headerItems = parseMenuItems(navigation?["header"])
        let footerItems = parseMenuItems(navigation?["footer"])
        let hamburgerItems = parseMenuItems(navigation?["hamburger"])

        return AppConfig(
            projectName: projectName,
            tabs: tabs,
            primaryColor: primaryColor,
            headerItems: headerItems,
            footerItems: footerItems,
            hamburgerItems: hamburgerItems,
            hasNavigationConfiguration: navigation != nil,
            navigationHeaderImageURL: navigationHeaderImageURL,
            navigationHeaderBackgroundColor: navigationHeaderBackgroundColor,
            homeImageURL: homeImageURL,
            homeMenuLayout: homeMenuLayout,
            productDisplayLayout: productDisplayLayout,
            memberShowRankProgress: memberScreen["show_rank_progress"] as? Bool ?? true,
            memberShowPointHistory: memberScreen["show_point_history"] as? Bool ?? true,
            memberShowCouponActivity: memberScreen["show_coupon_activity"] as? Bool ?? true
        )
    }

    private static func parseMenuItems(_ value: Any?) -> [AppTabItem] {
        guard let rows = value as? [[String: Any]] else { return [] }
        return rows.compactMap { row -> AppTabItem? in
            guard let key = row["key"] as? String else { return nil }
            return AppTabItem(
                id: key,
                key: key,
                label: row["label"] as? String ?? key,
                icon: row["icon"] as? String ?? "fa-circle",
                sfSymbol: row["sf_symbol"] as? String ?? "circle.fill",
                url: (row["url"] as? String)?.trimmedNonEmpty
            )
        }
    }
}
