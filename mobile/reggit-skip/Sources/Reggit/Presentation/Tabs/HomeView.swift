import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class HomeViewModel {
    let apiClient: any ReggitAPIServing
    var news: [AnnouncementItem] = []
    var newsLoading = false
    var newsError: String?

    func loadNews() async {
        newsLoading = true
        newsError = nil
        defer { newsLoading = false }
        do { news = try await apiClient.fetchPublishedAnnouncements() }
        catch { newsError = error.localizedDescription }
    }

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var mediaBaseURL: String { apiClient.baseURL }
}

struct HomeView: View {
    @State var viewModel: HomeViewModel
    @State var selectedHeroIndex = 0
    @State var loopIndex = 1
    @State var heroDragOffset: CGFloat = 0
    let homeImageURLs: [String]
    var member: MemberProfileSnapshot? = nil
    var projectName: String = ""
    @State var showMemberCode = false
    let menuLayout: String
    let homeLayout: String
    let galleryItems: [HomeGalleryCard]
    @State var selectedGallery: HomeGalleryCard?
    @State var selectedWebPage: HomeGalleryCard?
    @State var selectedNews: AnnouncementItem?
    @Environment(\.openURL) var openURL
    let tabs: [AppTabItem]
    let onSelectTab: (String) -> Void
    let onExpandImage: (Int) -> Void

    init(
        apiClient: any ReggitAPIServing,
        homeImageURLs: [String] = [],
        member: MemberProfileSnapshot? = nil,
        projectName: String = "",
        menuLayout: String = "grid_3x2",
        homeLayout: String = "legacy",
        galleryItems: [HomeGalleryCard] = [],
        tabs: [AppTabItem],
        onSelectTab: @escaping (String) -> Void,
        onExpandImage: @escaping (Int) -> Void
    ) {
        _viewModel = State(initialValue: HomeViewModel(apiClient: apiClient))
        self.member = member
        self.projectName = projectName
        self.homeImageURLs = homeImageURLs
        self.menuLayout = menuLayout
        self.homeLayout = homeLayout
        self.galleryItems = galleryItems
        self.tabs = tabs
        self.onSelectTab = onSelectTab
        self.onExpandImage = onExpandImage
    }

    init(
        apiClient: any ReggitAPIServing,
        homeImageURLs: [String],
        menuLayout: String,
        tabs: [AppTabItem],
        onSelectTab: @escaping (String) -> Void
    ) {
        self.init(
            apiClient: apiClient,
            homeImageURLs: homeImageURLs,
            menuLayout: menuLayout,
            tabs: tabs,
            onSelectTab: onSelectTab,
            onExpandImage: { _ in }
        )
    }

    var body: some View {
        SkipRefreshing { homeContent }
            .sheet(item: $selectedGallery) { item in
                NavigationStack {
                    ScrollView {
                        VStack(alignment: .leading, spacing: 16) {
                            RemoteImageView(urlString: item.imageURL, baseURL: viewModel.mediaBaseURL, contentMode: .fit)
                                .frame(minHeight: 280)
                            Text(item.title).font(.title2.weight(.bold))
                            Text(item.description).foregroundStyle(.secondary)
                        }.padding(20)
                    }
                    .toolbar { ToolbarItem(placement: .confirmationAction) { Button("閉じる") { selectedGallery = nil } } }
                }
            }
            .sheet(item: $selectedWebPage) { item in
                NavigationStack {
                    LegalWebView(title: item.title, urlString: item.url, baseURL: viewModel.mediaBaseURL)
                        .toolbar { ToolbarItem(placement: .confirmationAction) { Button("閉じる") { selectedWebPage = nil } } }
                }
            }
            .sheet(item: $selectedNews) { item in
                NavigationStack {
                    ScrollView {
                        AnnouncementCardView(item: item, apiClient: viewModel.apiClient, imageHeight: 240).padding(16)
                    }
                    .toolbar { ToolbarItem(placement: .confirmationAction) { Button("閉じる") { selectedNews = nil } } }
                }
            }
            .task(id: homeLayout) { if homeLayout == "member_news" { await viewModel.loadNews() } }
            .sheet(isPresented: $showMemberCode) {
                if let member = member {
                    MemberCodeSheet(member: member, onClose: { showMemberCode = false })
                        .presentationDetents(PlatformRuntime.supportsMultipleModalDetents ? [.medium, .large] : [.large])
                        .presentationDragIndicator(.visible)
                }
            }
    }

    private var homeContent: some View {
        GeometryReader { geometry in
            ScrollView {
                VStack(spacing: 16) {
                    if homeLayout == "legacy" {
                        heroImage(width: max(geometry.size.width - PlatformRuntime.homeHorizontalPadding * 2, 0))
                    }
                    if let member = member, member.isMember, !member.memberNumber.isEmpty {
                        Button { showMemberCode = true } label: {
                            VStack(spacing: 0) {
                                if let headerImage = homeImageURLs.first {
                                    RemoteImageView(urlString: headerImage, baseURL: viewModel.mediaBaseURL, contentMode: .fill, usesDemoFallback: false)
                                        .frame(maxWidth: .infinity).frame(height: max(geometry.size.width - PlatformRuntime.homeHorizontalPadding * 2, 0) / 3).clipped()
                                }
                                VStack(spacing: 8) {
                                    MemberCodeView(value: member.memberNumber, codeType: member.cardCodeType, height: 56)
                                    Text(member.memberNumber)
                                        .font(.system(size: 13, weight: .medium, design: .monospaced))
                                        .foregroundStyle(Color.black)
                                    Text("タップして拡大")
                                        .font(.caption)
                                        .foregroundStyle(Color.gray)
                                }
                                .padding(16)
                            }
                            .frame(maxWidth: .infinity)
                            .background(Color.white)
                            .clipShape(RoundedRectangle(cornerRadius: 16))
                        }
                        .buttonStyle(.plain)
                        .accessibilityLabel("会員コードを拡大して表示")
                    }
                    if homeLayout == "buttons_gallery" {
                        HomeMenuGrid(tabs: Array(menuTabs.prefix(2)), columns: 2, onSelect: onSelectTab)
                    } else if homeLayout != "gallery" {
                        homeMenu
                    }
                    if homeLayout == "gallery" || homeLayout == "buttons_gallery" {
                        galleryStack
                    }
                    if homeLayout == "member_news" {
                        newsSection
                    }
                }
                .padding(.horizontal, PlatformRuntime.homeHorizontalPadding)
                .padding(.top, 12)
                .padding(.bottom, 10)
                .frame(maxWidth: .infinity)
            }
        }
        .background(AppDesign.canvas.ignoresSafeArea())
        .task(id: homeImageURLs.count) {
            selectedHeroIndex = 0
            loopIndex = 1
            guard homeLayout == "legacy", homeImageURLs.count > 1 else { return }
            while !Task.isCancelled {
                try? await Task.sleep(nanoseconds: 5_000_000_000)
                if !Task.isCancelled {
                    advanceHero(1)
                }
            }
        }
    }

    private var galleryStack: some View {
        VStack(spacing: 16) {
            if galleryItems.isEmpty { Text("ギャラリー画像はありません").foregroundStyle(.secondary) }
            ForEach(galleryItems) { item in
                Button {
                    if item.mode == "external", let url = URL(string: item.url), url.scheme?.lowercased() == "https" {
                        openURL(url)
                    } else if item.mode == "webview", let url = URL(string: item.url), url.scheme?.lowercased() == "https" {
                        selectedWebPage = item
                    } else {
                        selectedGallery = item
                    }
                } label: {
                    VStack(alignment: .leading, spacing: 0) {
                        RemoteImageView(urlString: item.imageURL, baseURL: viewModel.mediaBaseURL, contentMode: .fit)
                            .frame(maxWidth: .infinity)
                        if !item.title.isEmpty { Text(item.title).font(.headline).foregroundStyle(AppDesign.ink).padding(16) }
                    }
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
                }.buttonStyle(.plain)
            }
        }
    }

    private var newsSection: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("ニュース").font(.title3.weight(.bold))
            if viewModel.newsLoading { ProgressView() }
            else if let error = viewModel.newsError {
                Text(error).foregroundStyle(.secondary)
                Button("再読み込み") { Task { await viewModel.loadNews() } }
            } else if viewModel.news.isEmpty { Text("お知らせはありません").foregroundStyle(.secondary) }
            ForEach(Array(viewModel.news.prefix(3))) { item in
                Button { selectedNews = item } label: {
                    Text(item.title).foregroundStyle(AppDesign.ink).frame(maxWidth: .infinity, alignment: .leading).padding(16)
                        .background(AppDesign.surface).clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
                }.buttonStyle(.plain)
            }
        }
    }

    private var menuTabs: [AppTabItem] {
        tabs.filter { $0.key != "home" && $0.key != "terms" && $0.key != "privacy" }
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

    // Measure the viewport outside the scroll view: Compose gives a
    // GeometryReader inside scrolling content an unconstrained height.
    private func heroImage(width: CGFloat) -> some View {
        ZStack(alignment: .bottom) {
            if homeImageURLs.count > 1 {
                heroSlider(width: width)
            } else {
                heroImageButton(at: 0)
                    .frame(width: width, height: width)
            }
            if homeImageURLs.count > 1 {
                heroPageDots
                    .padding(.bottom, 14)
                    .zIndex(1)
            }
        }
        .frame(width: width, height: width)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
    }

    private func heroImageButton(at index: Int) -> some View {
        Button {
            selectedHeroIndex = index
            onExpandImage(index)
        } label: {
            heroImageContent(at: index, contentMode: .fill)
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .clipped()
        }
        .buttonStyle(.plain)
        .accessibilityLabel("画像を全画面で表示")
    }

    private var loopPages: [Int] {
        let count = homeImageURLs.count
        guard count > 1 else { return Array(homeImageURLs.indices) }
        return [count - 1] + Array(homeImageURLs.indices) + [0]
    }

    private var visibleHeroIndex: Int {
        let count = homeImageURLs.count
        guard count > 1 else { return 0 }
        if loopIndex <= 0 { return count - 1 }
        if loopIndex > count { return 0 }
        return loopIndex - 1
    }

    private func heroSlider(width: CGFloat) -> some View {
        HStack(spacing: 0) {
            ForEach(Array(loopPages.enumerated()), id: \.offset) { _, imageIndex in
                heroImageContent(at: imageIndex, contentMode: .fill)
                    .frame(width: width, height: width)
                    .clipped()
            }
        }
        .offset(x: -CGFloat(loopIndex) * width + heroDragOffset)
        .animation(.easeInOut(duration: 0.45), value: loopIndex)
        .animation(.easeInOut(duration: 0.45), value: heroDragOffset)
        .frame(width: width, height: width, alignment: .leading)
        .clipped()
        #if !os(Android)
        .contentShape(Rectangle())
        .highPriorityGesture(heroDragGesture)
        .accessibilityAddTraits(.isButton)
        .accessibilityLabel("画像を全画面で表示")
        .accessibilityAction { onExpandImage(visibleHeroIndex) }
        #endif
    }

    private func advanceHero(_ step: Int) {
        guard homeImageURLs.count > 1 else { return }
        loopIndex += step
        let count = homeImageURLs.count
        let edge = loopIndex
        guard edge == 0 || edge == count + 1 else { return }
        Task { @MainActor in
            try? await Task.sleep(nanoseconds: 460_000_000)
            #if !os(Android)
            var transaction = Transaction()
            transaction.disablesAnimations = true
            withTransaction(transaction) {
                loopIndex = edge == 0 ? count : 1
            }
            #else
            loopIndex = edge == 0 ? count : 1
            #endif
        }
    }

    private var heroDragGesture: some Gesture {
        DragGesture(minimumDistance: 0)
            .onChanged { value in
                #if !os(Android)
                var transaction = Transaction()
                transaction.disablesAnimations = true
                withTransaction(transaction) {
                    heroDragOffset = value.translation.width
                }
                #else
                heroDragOffset = value.translation.width
                #endif
            }
            .onEnded { value in
                let travel = value.translation.width
                let lift = value.translation.height
                if abs(travel) < 12, abs(lift) < 12 {
                    heroDragOffset = 0
                    onExpandImage(visibleHeroIndex)
                    return
                }
                withAnimation(.easeInOut(duration: 0.45)) {
                    heroDragOffset = 0
                }
                if travel < -36 {
                    advanceHero(1)
                } else if travel > 36 {
                    advanceHero(-1)
                }
            }
    }

    private var heroPageDots: some View {
        HStack(spacing: 8) {
            ForEach(homeImageURLs.indices, id: \.self) { index in
                Button {
                    loopIndex = index + 1
                } label: {
                    Circle()
                        .fill(Color.white)
                        .frame(width: 8, height: 8)
                        .opacity(index == visibleHeroIndex ? 1 : 0.4)
                }
                .buttonStyle(.plain)
                .accessibilityLabel("\(index + 1)枚目の画像")
            }
        }
        .padding(.horizontal, 12)
        .padding(.vertical, 8)
        .background(Capsule().fill(Color.black.opacity(0.45)))
        #if !os(Android)
        .accessibilityElement(children: .contain)
        .accessibilityLabel("ホーム画像")
        #endif
    }

    @ViewBuilder
    private func heroImageContent(at index: Int, contentMode: ContentMode) -> some View {
        if homeImageURLs.indices.contains(index) {
            RemoteImageView(
                urlString: homeImageURLs[index],
                baseURL: viewModel.mediaBaseURL,
                contentMode: contentMode
            )
        } else {
            Image("DemoStoreHero", bundle: .module)
                .resizable()
                .aspectRatio(contentMode: contentMode)
        }
    }
}

struct HomeMenuGrid: View {
    let tabs: [AppTabItem]
    let columns: Int
    let onSelect: (String) -> Void
    @Environment(\.brandAccent) var accent

    var body: some View {
        // Skip promotes a nested LazyVGrid to its own scrolling container.
        // Eager rows keep the hero, member card and menu in the home's ScrollView.
        VStack(spacing: 0) {
            ForEach(0..<((tabs.count + columns - 1) / columns), id: \.self) { row in
                HStack(spacing: 0) {
                    ForEach(0..<columns, id: \.self) { column in
                        gridCell(at: row * columns + column)
                    }
                }
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
                    FontAwesomeIcon(systemName: TabIconResolver.systemName(for: tab))
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
                if index < tabs.count - columns {
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
                    if index < tabs.count - columns {
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
                        FontAwesomeIcon(systemName: TabIconResolver.systemName(for: tab))
                            .foregroundStyle(accent)
                            .frame(width: 30)
                        VStack(alignment: .leading, spacing: 3) {
                            Text(tab.label)
                                .font(.system(size: 15, weight: .semibold))
                                .foregroundStyle(AppDesign.ink)

                        }
                        Spacer()
                        FontAwesomeIcon(systemName: "chevron.right")
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
