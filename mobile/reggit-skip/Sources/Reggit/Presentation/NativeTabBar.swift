import SwiftUI

struct NativeTabBar: View {
    let tabs: [AppTabItem]
    @Binding var selectedTab: String

    var body: some View {
        HStack(spacing: 0) {
            ForEach(tabs) { tab in
                Button {
                    selectedTab = tab.key
                } label: {
                    VStack(spacing: 3) {
                        Image(systemName: tab.sfSymbol)
                            .font(.system(size: 20))
                        Text(tab.label)
                            .font(.system(size: 10, weight: .medium))
                    }
                    .frame(maxWidth: .infinity)
                    .foregroundColor(selectedTab == tab.key ? .accentColor : .gray)
                    .padding(.top, 8)
                    .padding(.bottom, 4)
                }
                .buttonStyle(.plain)
                #if !os(Android)
                .accessibilityLabel(tab.label)
                #endif
            }
        }
        .background(
            Rectangle()
                .fill(.ultraThinMaterial)
                .shadow(color: .black.opacity(0.08), radius: 8, y: -2)
        )
    }
}
