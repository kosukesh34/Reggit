import Foundation

struct AppTabItem: Identifiable, Equatable {
    let id: String
    let key: String
    let label: String
    let icon: String
    let sfSymbol: String
    let url: String?
    let openMode: String

    init(id: String, key: String, label: String, icon: String, sfSymbol: String, url: String? = nil, openMode: String = "embed") {
        self.id = id
        self.key = key
        self.label = label
        self.icon = icon
        self.sfSymbol = sfSymbol
        self.url = url
        self.openMode = openMode
    }

    static let fallbackTabs: [AppTabItem] = [
        AppTabItem(id: "home", key: "home", label: "ホーム", icon: "fa-home", sfSymbol: "house.fill"),
        AppTabItem(id: "products", key: "products", label: "商品", icon: "fa-utensils", sfSymbol: "fork.knife"),
        AppTabItem(id: "orders", key: "orders", label: "注文", icon: "fa-receipt", sfSymbol: "list.clipboard.fill"),
        AppTabItem(id: "member", key: "member", label: "会員", icon: "fa-user", sfSymbol: "person.crop.circle.fill"),
    ]
}

struct HomeGalleryCard: Identifiable, Equatable {
    let id: Int
    let title: String
    let description: String
    let imageURL: String
    let mode: String
    let url: String
}

/// 認証後の機能案内に使うポイント・ランク設定（app-config の points_guide）
struct PointsGuide: Equatable {
    var usePoints = true
    /// この金額ごとに 1pt 貯まる。使うときは 1pt = 1円
    var earnYenPerPoint = 100
    /// ポイントを使える場所（app / store / both）。管理画面の機能設定で選ぶ
    var usageScope = "app"
    var ranks: [Rank] = []

    struct Rank: Equatable {
        let label: String
        /// 管理画面で設定したランクのカード色（#RRGGBB）。未設定は nil
        let colorHex: String?
    }

    var usageText: String {
        switch usageScope {
        case "store": return "お店のレジで使えます"
        case "both": return "アプリの注文とお店のレジで使えます"
        default: return "アプリからの注文で使えます"
        }
    }

    var usagePlaces: [String] {
        switch usageScope {
        case "store": return ["お店のレジ"]
        case "both": return ["アプリの注文", "お店のレジ"]
        default: return ["アプリの注文"]
        }
    }
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
    let homeLayout: String
    let homeGalleryItems: [HomeGalleryCard]
    let productDisplayLayout: String
    let memberPageStyle: String
    let memberShowRankProgress: Bool
    let memberShowPointHistory: Bool
    let memberShowCouponActivity: Bool
    let realtimeWebSocketURL: String?
    let pointsGuide: PointsGuide

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
        homeLayout: String = "legacy",
        homeGalleryItems: [HomeGalleryCard] = [],
        productDisplayLayout: String = "list_with_image",
        memberPageStyle: String = "classic",
        memberShowRankProgress: Bool = true,
        memberShowPointHistory: Bool = true,
        memberShowCouponActivity: Bool = true,
        realtimeWebSocketURL: String? = nil,
        pointsGuide: PointsGuide = PointsGuide()
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
        self.homeLayout = homeLayout
        self.homeGalleryItems = homeGalleryItems
        self.productDisplayLayout = productDisplayLayout
        self.memberPageStyle = memberPageStyle
        self.memberShowRankProgress = memberShowRankProgress
        self.memberShowPointHistory = memberShowPointHistory
        self.memberShowCouponActivity = memberShowCouponActivity
        self.realtimeWebSocketURL = realtimeWebSocketURL
        self.pointsGuide = pointsGuide
    }
}
