import SwiftUI

struct NativeTabBar: View {
    let items: [AppTabItem]
    var colors: [String: String] = [:]
    var announcementCount = 0
    @Binding var selectedTab: String
    @Environment(\.brandAccent) var accent

    var body: some View {
        HStack(spacing: 0) {
            ForEach(items) { item in
                Button { selectedTab = item.key } label: {
                    VStack(spacing: 3) {
                        FontAwesomeIcon(systemName: TabIconResolver.systemName(for: item))
                            .overlay(alignment: .topTrailing) {
                                if item.key == "announcements" && announcementCount > 0 {
                                    Text(String(announcementCount))
                                        .font(.system(size: 10, weight: .bold))
                                        .foregroundStyle(Color.white)
                                        .padding(.horizontal, 5)
                                        .frame(minWidth: 18, minHeight: 18)
                                        .background(Color.red)
                                        .clipShape(Capsule())
                                        .offset(x: 12, y: -8)
                                }
                            }
                        Text(item.label)
                            .font(.system(size: 10, weight: .medium))
                            .lineLimit(1)
                            .minimumScaleFactor(0.6)
                    }
                    .frame(maxWidth: .infinity, minHeight: 54)
                    .foregroundStyle((colors[selectedTab == item.key ? "footer_active_icon" : "footer_icon"] ?? "").toNativeColor(fallback: selectedTab == item.key ? accent : AppDesign.inkSecondary))
                    .background(selectedTab == item.key ? (colors["footer_active_background"] ?? "").toNativeColor(fallback: .clear) : Color.clear)
                    .clipShape(RoundedRectangle(cornerRadius: 12))
                }
                .buttonStyle(.plain)
                .accessibilityLabel(Text(item.key == "announcements" && announcementCount > 0 ? "\(item.label)、\(announcementCount)件" : item.label))
            }
        }
        .padding(.horizontal, 8)
        .padding(.vertical, 6)
        .background((colors["footer_background"] ?? "").toNativeColor(fallback: AppDesign.surface).ignoresSafeArea(edges: .bottom))
        .overlay(alignment: .top) {
            Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
        }
    }
}

/// タブアイコン解決。純正 TabView の tabItem で使う。
enum TabIconResolver {
    static func systemName(for tab: AppTabItem) -> String {
        let icon = tab.icon.trimmingCharacters(in: .whitespacesAndNewlines)
        if tab.key != "home" && ["fa-home", "fa-house"].contains(icon) {
            return fallback(for: tab.key)
        }
        if icon.hasPrefix("fa-") {
            return icon
        }
        if tab.key == "products" && ["", "fork.knife", "fork.knife.circle", "cart.fill"].contains(tab.sfSymbol.trimmingCharacters(in: .whitespacesAndNewlines)) {
            return "bag"
        }
        let symbol = tab.sfSymbol.trimmingCharacters(in: .whitespacesAndNewlines)
        return symbol.isEmpty ? fallback(for: tab.key) : symbol
    }

    private static func fallback(for key: String) -> String {
        switch key {
        case "home": return "house.fill"
        case "member": return "fa-id-card"
        case "profile", "profileEdit": return "fa-user-pen"
        case "products": return "bag.fill"
        case "orders": return "list.clipboard.fill"
        case "coupons": return "ticket.fill"
        case "stamps": return "star.circle.fill"
        case "designlab", "lottery": return "dice.fill"
        case "queue": return "number.circle.fill"
        case "reservations": return "calendar"
        case "gallery": return "photo.on.rectangle"
        case "announcements": return "fa-bullhorn"
        case "storeSearch": return "location.fill"
        case "terms": return "doc.text.fill"
        case "privacy": return "hand.raised.fill"
        default: return "circle.fill"
        }
    }
}
