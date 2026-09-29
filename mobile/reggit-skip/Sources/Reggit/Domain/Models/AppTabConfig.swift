import Foundation

struct AppTabItem: Identifiable, Equatable {
    let id: String
    let key: String
    let label: String
    let icon: String
    let sfSymbol: String
    let url: String?

    init(id: String, key: String, label: String, icon: String, sfSymbol: String, url: String? = nil) {
        self.id = id
        self.key = key
        self.label = label
        self.icon = icon
        self.sfSymbol = sfSymbol
        self.url = url
    }

    static let fallbackTabs: [AppTabItem] = [
        AppTabItem(id: "home", key: "home", label: "ホーム", icon: "fa-home", sfSymbol: "house.fill"),
        AppTabItem(id: "products", key: "products", label: "商品", icon: "fa-utensils", sfSymbol: "fork.knife"),
        AppTabItem(id: "orders", key: "orders", label: "注文", icon: "fa-receipt", sfSymbol: "list.clipboard.fill"),
        AppTabItem(id: "member", key: "member", label: "会員", icon: "fa-user", sfSymbol: "person.crop.circle.fill"),
    ]
}

struct AppConfig {
    let projectName: String
    let tabs: [AppTabItem]
    let headerItems: [AppTabItem]
    let footerItems: [AppTabItem]
    let hamburgerItems: [AppTabItem]
    let hasNavigationConfiguration: Bool
    let primaryColor: String
    let navigationHeaderImageURL: String?
    let navigationHeaderText: String?
    let navigationHeaderBackgroundColor: String
    let homeImageURL: String?
    let homeImageURLs: [String]
    let nativeChromeColors: [String: String]
    let homeShowMemberCard: Bool
    let homeMenuLayout: String
    let productDisplayLayout: String
    let memberShowRankProgress: Bool
    let memberShowPointHistory: Bool
    let memberShowCouponActivity: Bool
    let realtimeWebSocketURL: String?

    init(
        projectName: String,
        tabs: [AppTabItem],
        primaryColor: String,
        headerItems: [AppTabItem] = [],
        footerItems: [AppTabItem] = [],
        hamburgerItems: [AppTabItem] = [],
        hasNavigationConfiguration: Bool = false,
        navigationHeaderImageURL: String? = nil,
        navigationHeaderText: String? = nil,
        navigationHeaderBackgroundColor: String = "#FFFFFF",
        homeImageURL: String? = nil,
        homeImageURLs: [String] = [],
        nativeChromeColors: [String: String] = [:],
        homeShowMemberCard: Bool = true,
        homeMenuLayout: String = "grid_3x2",
        productDisplayLayout: String = "list_with_image",
        memberShowRankProgress: Bool = true,
        memberShowPointHistory: Bool = true,
        memberShowCouponActivity: Bool = true,
        realtimeWebSocketURL: String? = nil
    ) {
        self.projectName = projectName
        self.tabs = tabs
        self.headerItems = headerItems
        self.footerItems = footerItems
        self.hamburgerItems = hamburgerItems
        self.hasNavigationConfiguration = hasNavigationConfiguration
        self.primaryColor = primaryColor
        self.navigationHeaderImageURL = navigationHeaderImageURL
        self.navigationHeaderText = navigationHeaderText
        self.navigationHeaderBackgroundColor = navigationHeaderBackgroundColor
        self.homeImageURL = homeImageURL
        self.homeImageURLs = homeImageURLs.isEmpty ? (homeImageURL.map { [$0] } ?? []) : homeImageURLs
        self.nativeChromeColors = nativeChromeColors
        self.homeShowMemberCard = homeShowMemberCard
        self.homeMenuLayout = homeMenuLayout
        self.productDisplayLayout = productDisplayLayout
        self.memberShowRankProgress = memberShowRankProgress
        self.memberShowPointHistory = memberShowPointHistory
        self.memberShowCouponActivity = memberShowCouponActivity
        self.realtimeWebSocketURL = realtimeWebSocketURL
    }
}
