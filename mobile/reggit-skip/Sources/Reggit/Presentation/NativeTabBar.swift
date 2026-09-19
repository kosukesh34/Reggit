/// タブアイコン解決。純正 TabView の tabItem で使う。
enum TabIconResolver {
    static func systemName(for tab: AppTabItem) -> String {
        // The default ordering icon must also suit shops, salons and service businesses.
        if tab.key == "products" && ["", "fork.knife", "fork.knife.circle", "cart.fill"].contains(tab.sfSymbol.trimmed) {
            return "bag"
        }
        let symbol = tab.sfSymbol.trimmed
        return symbol.isEmpty ? fallback(for: tab.key) : symbol
    }

    private static func fallback(for key: String) -> String {
        switch key {
        case "home": return "house.fill"
        case "member": return "person.crop.circle.fill"
        case "products": return "bag.fill"
        case "orders": return "list.clipboard.fill"
        case "coupons": return "ticket.fill"
        case "stamps": return "star.circle.fill"
        case "queue": return "number.circle.fill"
        case "reservations": return "calendar"
        case "gallery": return "photo.on.rectangle"
        default: return "circle.fill"
        }
    }
}
