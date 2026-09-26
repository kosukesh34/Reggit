import SwiftUI

struct NativeTabBar: View {
    let items: [AppTabItem]
    @Binding var selectedTab: String
    @Environment(\.brandAccent) var accent

    var body: some View {
        HStack(spacing: 0) {
            ForEach(items) { item in
                Button { selectedTab = item.key } label: {
                    VStack(spacing: 3) {
                        FontAwesomeIcon(systemName: TabIconResolver.systemName(for: item))
                        Text(item.label)
                            .font(.system(size: 10, weight: .medium))
                            .lineLimit(1)
                            .minimumScaleFactor(0.6)
                    }
                    .frame(maxWidth: .infinity, minHeight: 54)
                    .foregroundStyle(selectedTab == item.key ? accent : AppDesign.inkSecondary)
                }
                .buttonStyle(.plain)
                .accessibilityLabel(Text(item.label))
            }
        }
        .padding(.bottom, 2)
        .background(AppDesign.surface.ignoresSafeArea(edges: .bottom))
        .overlay(alignment: .top) {
            Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
        }
    }
}

/// タブアイコン解決。純正 TabView の tabItem で使う。
enum TabIconResolver {
    static func systemName(for tab: AppTabItem) -> String {
        let icon = tab.icon.trimmingCharacters(in: .whitespacesAndNewlines)
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
        case "member": return "person.crop.circle.fill"
        case "products": return "bag.fill"
        case "orders": return "list.clipboard.fill"
        case "coupons": return "ticket.fill"
        case "stamps": return "star.circle.fill"
        case "designlab": return "dice.fill"
        case "queue": return "number.circle.fill"
        case "reservations": return "calendar"
        case "gallery": return "photo.on.rectangle"
        case "storeSearch": return "location.fill"
        case "terms": return "doc.text.fill"
        case "privacy": return "hand.raised.fill"
        default: return "circle.fill"
        }
    }
}
