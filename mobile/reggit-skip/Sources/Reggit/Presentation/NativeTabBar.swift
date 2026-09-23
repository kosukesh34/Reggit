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
                        Image(systemName: TabIconResolver.systemName(for: item))
                            .font(.system(size: 18, weight: .semibold))
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
        case "designlab": return "dice.fill"
        case "queue": return "number.circle.fill"
        case "reservations": return "calendar"
        case "gallery": return "photo.on.rectangle"
        case "terms": return "doc.text.fill"
        case "privacy": return "hand.raised.fill"
        default: return "circle.fill"
        }
    }
}
