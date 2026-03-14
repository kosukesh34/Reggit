import Foundation

struct AppTabItem: Identifiable, Equatable {
    let id: String
    let key: String
    let label: String
    let icon: String
    let sfSymbol: String

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
    let primaryColor: String
}
