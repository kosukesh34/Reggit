import SwiftUI

struct NativeTabBar: View {
    let tabs: [AppTabItem]
    @Binding var selectedTab: String

    var body: some View {
        HStack(spacing: 0) {
            ForEach(Array(tabs.enumerated()), id: \.offset) { _, tab in
                Button {
                    selectedTab = tab.key
                } label: {
                    VStack(spacing: 2) {
                        Image(systemName: TabIconResolver.systemName(for: tab))
                            .font(.system(size: 18))
                        Text(tab.label)
                            .font(.system(size: 9, weight: .medium))
                            .lineLimit(1)
                            .minimumScaleFactor(0.5)
                    }
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                    .foregroundColor(selectedTab == tab.key ? Color.blue : Color.primary)
                    .padding(.top, 4)
                    .padding(.bottom, 2)
                }
                .buttonStyle(.plain)
                #if !os(Android)
                .accessibilityLabel(tab.label)
                #endif
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(tabBarBackground)
    }

    @ViewBuilder
    private var tabBarBackground: some View {
        #if os(Android)
        Color(white: 0.97)
        #else
        Rectangle()
            .fill(.ultraThinMaterial)
            .shadow(color: .black.opacity(0.08), radius: 8, y: -2)
        #endif
    }
}

/// iOS は AppConfig の SF Symbol を使い、Android は Skip のフォールバック／Material Icons に解決する。
enum TabIconResolver {
    static func systemName(for tab: AppTabItem) -> String {
        #if os(Android)
        return androidSystemName(for: tab)
        #else
        let symbol = tab.sfSymbol.trimmingCharacters(in: .whitespacesAndNewlines)
        return symbol.isEmpty ? "circle.fill" : symbol
        #endif
    }

    #if os(Android)
    private static func androidSystemName(for tab: AppTabItem) -> String {
        // Skip の内蔵フォールバック（material-icons コア）に載る名前だけを使う。
        // Icons.Filled.Restaurant など extended は未同梱で警告アイコンになる。
        switch tab.key {
        case "home":
            return "house.fill"
        case "products":
            return "cart.fill"
        case "orders":
            return "list.bullet"
        case "stamps":
            return "star.fill"
        case "coupons":
            return "heart.fill"
        case "queue":
            return "person.fill"
        case "member":
            return "person.crop.circle.fill"
        case "reservations":
            return "calendar"
        case "gallery":
            return "person.crop.square.fill"
        case "announcements":
            return "bell.fill"
        default:
            return androidSystemName(forSFSymbol: tab.sfSymbol, icon: tab.icon)
        }
    }

    private static func androidSystemName(forSFSymbol sfSymbol: String, icon: String) -> String {
        switch sfSymbol {
        case "house", "house.fill":
            return "house.fill"
        case "star", "star.fill":
            return "star.fill"
        case "calendar":
            return "calendar"
        case "bell", "bell.fill", "megaphone.fill":
            return "bell.fill"
        case "person.crop.circle", "person.crop.circle.fill":
            return "person.crop.circle.fill"
        case "person", "person.fill", "person.3.fill":
            return "person.fill"
        case "list.bullet", "list.clipboard.fill":
            return "list.bullet"
        case "cart", "cart.fill", "fork.knife":
            return "cart.fill"
        case "ticket.fill", "heart", "heart.fill":
            return "heart.fill"
        case "photo.on.rectangle.angled", "photo.on.rectangle", "photo", "person.crop.square", "person.crop.square.fill":
            return "person.crop.square.fill"
        default:
            return androidSystemName(forFontAwesome: icon)
        }
    }

    private static func androidSystemName(forFontAwesome icon: String) -> String {
        switch icon {
        case "fa-home", "fa-house":
            return "house.fill"
        case "fa-utensils", "fa-box":
            return "cart.fill"
        case "fa-receipt":
            return "list.bullet"
        case "fa-stamp":
            return "star.fill"
        case "fa-ticket-alt", "fa-ticket":
            return "heart.fill"
        case "fa-list-ol", "fa-users":
            return "person.fill"
        case "fa-calendar-check", "fa-calendar":
            return "calendar"
        case "fa-id-card", "fa-user":
            return "person.crop.circle.fill"
        case "fa-images", "fa-image":
            return "person.crop.square.fill"
        case "fa-bullhorn":
            return "bell.fill"
        default:
            return "info.circle"
        }
    }
    #endif
}
