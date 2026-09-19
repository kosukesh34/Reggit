import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class HomeViewModel {
    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var mediaBaseURL: String { apiClient.baseURL }
}

struct HomeView: View {
    @State var viewModel: HomeViewModel
    let homeImageURL: String?
    let menuLayout: String
    let tabs: [AppTabItem]
    let onSelectTab: (String) -> Void

    init(
        apiClient: any ReggitAPIServing,
        homeImageURL: String? = nil,
        menuLayout: String = "grid_3x2",
        tabs: [AppTabItem],
        onSelectTab: @escaping (String) -> Void
    ) {
        _viewModel = State(initialValue: HomeViewModel(apiClient: apiClient))
        self.homeImageURL = homeImageURL
        self.menuLayout = menuLayout
        self.tabs = tabs
        self.onSelectTab = onSelectTab
    }

    var body: some View {
        SkipRefreshing {
            VStack(spacing: 16) {
                heroImage
                Spacer(minLength: 12)
                homeMenu
            }
            .padding(.horizontal, PlatformRuntime.homeHorizontalPadding)
            .padding(.top, 12)
            .padding(.bottom, 10)
            .frame(maxWidth: .infinity, maxHeight: .infinity)
            .background(AppDesign.canvas.ignoresSafeArea())
        }
    }

    private var menuTabs: [AppTabItem] {
        Array(tabs.filter { $0.key != "home" }.prefix(6))
    }

    @ViewBuilder
    private var homeMenu: some View {
        switch menuLayout {
        case "grid_2_columns":
            HomeMenuGrid(tabs: menuTabs, columns: 2, onSelect: onSelectTab)
        case "list":
            HomeMenuList(tabs: menuTabs, onSelect: onSelectTab)
        default:
            HomeMenuGrid(tabs: menuTabs, columns: 3, onSelect: onSelectTab)
        }
    }

    @ViewBuilder
    private var heroImage: some View {
        Group {
            if homeImageURL != nil {
                RemoteImageView(
                    urlString: homeImageURL,
                    baseURL: viewModel.mediaBaseURL,
                    contentMode: .fill,
                    cornerRadius: AppDesign.radiusL
                )
                .frame(maxWidth: .infinity)
                .frame(height: PlatformRuntime.homeHeroHeight, alignment: .center)
                .clipped()
            } else {
                Image("DemoStoreHero", bundle: .module)
                    .resizable()
                    .aspectRatio(contentMode: .fill)
                    .frame(maxWidth: .infinity)
                    .frame(height: PlatformRuntime.homeHeroHeight, alignment: .center)
                    .clipped()
                    .accessibilityHidden(true)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
            }
        }
        .overlay {
            RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous)
                .stroke(Color.white.opacity(0.18), lineWidth: 1)
        }
    }
}

struct HomeMenuGrid: View {
    let tabs: [AppTabItem]
    let columns: Int
    let onSelect: (String) -> Void
    @Environment(\.brandAccent) var accent

    var body: some View {
        LazyVGrid(
            columns: Array(repeating: GridItem(.flexible(), spacing: 0), count: columns),
            spacing: 0
        ) {
            ForEach(0..<6, id: \.self) { index in
                gridCell(at: index)
            }
        }
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous)
                .stroke(AppDesign.hairline, lineWidth: 0.5)
        }
    }

    @ViewBuilder
    private func gridCell(at index: Int) -> some View {
        if index < tabs.count {
            let tab = tabs[index]
            Button { onSelect(tab.key) } label: {
                VStack(spacing: 10) {
                    Image(systemName: TabIconResolver.systemName(for: tab))
                        .font(.system(size: 24, weight: .medium))
                        .foregroundStyle(accent)
                    Text(tab.label)
                        .font(.system(size: 12, weight: .semibold))
                        .foregroundStyle(AppDesign.ink)
                        .lineLimit(2)
                        .multilineTextAlignment(.center)
                }
                .frame(maxWidth: .infinity)
                .frame(height: 108)
                .background(AppDesign.surface)
            }
            .buttonStyle(.plain)
            .overlay(alignment: .trailing) {
                if index % columns != columns - 1 {
                    Rectangle().fill(AppDesign.hairline).frame(width: 0.5)
                }
            }
            .overlay(alignment: .bottom) {
                if index < columns {
                    Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
                }
            }
        } else {
            Color.clear
                .frame(maxWidth: .infinity)
                .frame(height: 108)
                .background(AppDesign.surface)
                .overlay(alignment: .trailing) {
                    if index % columns != columns - 1 {
                        Rectangle().fill(AppDesign.hairline).frame(width: 0.5)
                    }
                }
                .overlay(alignment: .bottom) {
                    if index < columns {
                        Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
                    }
                }
        }
    }
}

struct HomeMenuList: View {
    let tabs: [AppTabItem]
    let onSelect: (String) -> Void
    @Environment(\.brandAccent) var accent

    var body: some View {
        VStack(spacing: 0) {
            ForEach(Array(tabs.enumerated()), id: \.element.id) { index, tab in
                Button { onSelect(tab.key) } label: {
                    HStack(spacing: 14) {
                        Image(systemName: TabIconResolver.systemName(for: tab))
                            .font(.system(size: 20, weight: .medium))
                            .foregroundStyle(accent)
                            .frame(width: 30)
                        VStack(alignment: .leading, spacing: 3) {
                            Text(tab.label)
                                .font(.system(size: 15, weight: .semibold))
                                .foregroundStyle(AppDesign.ink)
                            Text(tab.key)
                                .font(.system(size: 11))
                                .foregroundStyle(AppDesign.inkSecondary)
                        }
                        Spacer()
                        Image(systemName: "chevron.right")
                            .font(.caption.weight(.semibold))
                            .foregroundStyle(AppDesign.inkSecondary)
                    }
                    .padding(.horizontal, 16)
                    .frame(maxWidth: .infinity, minHeight: 64)
                    .background(AppDesign.surface)
                }
                .buttonStyle(.plain)
                if index < tabs.count - 1 {
                    Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
                }
            }
        }
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous)
                .stroke(AppDesign.hairline, lineWidth: 0.5)
        }
    }
}
