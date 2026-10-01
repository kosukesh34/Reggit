import SkipFuse
import SwiftUI

struct NativeRootView: View {
    @Environment(\.openURL) var openURL
    @State var viewModel: NativeRootViewModel
    @State var showHamburgerMenu = false
    @State var accountMode: String?
    @State var showAppInfo = false
    @State var appInfoDisplayedAt = Date()
    @State var expandedHomeImageIndex: Int? = nil
    @State var expandedHomePage = 0
    @State var headerNavigation = HeaderNavigationState()
    @State var menuHistory: [String] = []

    init(viewModel: NativeRootViewModel) {
        _viewModel = State(initialValue: viewModel)
    }

    private var themeHex: String {
        AppDesign.usesStoreTheme ? viewModel.primaryColorHex : "#173F35"
    }

    private var accent: Color { themeHex.toNativeColor() }

    var body: some View {
        VStack(spacing: 0) {
            if expandedHomeImageIndex == nil && (viewModel.phase == .ready || viewModel.phase == .needsRegistration) {
                NativeSharedHeader(
                    backgroundColorHex: viewModel.navigationHeaderBackgroundColorHex,
                    colors: viewModel.nativeChromeColors,
                    imageURL: viewModel.navigationHeaderImageURL,
                    title: viewModel.navigationHeaderText ?? viewModel.projectName,
                    baseURL: viewModel.apiClient?.baseURL ?? "",
                    items: viewModel.headerItems,
                    showsHamburger: true,
                    menuOpen: showHamburgerMenu,
                    showsBack: !showHamburgerMenu && (headerNavigation.backAction != nil || !menuHistory.isEmpty || accountMode != nil || showAppInfo || expandedHomeImageIndex != nil || viewModel.lotteryModalPresented),
                    onSelect: { selectMenu($0) },
                    onOpenHamburger: { handleHeaderBack() }
                )
            }
        NavigationStack {
        SkipRefreshing {
            Group {
                switch viewModel.phase {
                case .loading:
                    splash
                case .failed(let message):
                    errorScreen(message)
                case .needsRegistration:
                    MembershipRegistrationGateView(
                        projectName: viewModel.projectName,
                        message: viewModel.membership?.message,
                        canSkip: viewModel.membership?.registrationMode.canSkipRegistration ?? true,
                        isRegistering: viewModel.isRegistering,
                        errorMessage: viewModel.registrationError,
                        fields: viewModel.membership?.editableProfileFields ?? [],
                        initialValues: viewModel.registrationPayload.isEmpty ? (viewModel.membership?.profilePayload ?? [:]) : viewModel.registrationPayload,
                        initialListValues: viewModel.registrationListPayload,
                        serverFieldErrors: viewModel.registrationFieldErrors,
                        onSubmit: { values, listValues in
                            Task { await viewModel.submitRegistration(values: values, listValues: listValues) }
                        },
                        onSkip: { viewModel.skipRegistration() }
                    )
                case .ready:
                    mainChrome
                }
            }
            .environment(\.appButtonColors, viewModel.nativeChromeColors)
            .environment(\.brandAccent, accent)
            .environment(\.brandForeground, BrandPalette(hex: themeHex).usesDarkForeground ? Color.black : Color.white)
            .preferredColorScheme(.light)
            .environment(\.apiBaseURL, viewModel.apiClient?.baseURL ?? "")
            .toolbar(viewModel.phase == .needsRegistration ? .visible : .hidden, for: .navigationBar)
        }
        .onAppear { viewModel.onAppear() }
        .onDisappear { viewModel.onDisappear() }
        .alert("会員登録", isPresented: $viewModel.showRegistrationPopup) {
            Button("会員登録する") { viewModel.openRegistrationFromPopup() }
            Button("あとで", role: .cancel) {}
        } message: {
            Text(viewModel.membership?.message ?? "会員登録でポイントやクーポンが使えます。")
        }
        .navigationDestination(isPresented: Binding(
            get: { accountMode != nil },
            set: { if $0 == false { accountMode = nil } }
        )) {
            if accountMode == "link", let client = viewModel.apiClient {
                ExistingMemberLinkView(
                    apiClient: client,
                    onLinked: {
                        accountMode = nil
                        Task { await viewModel.start() }
                    },
                    onClose: { accountMode = nil }
                )
            } else if let client = viewModel.apiClient {
                AccountActionsView(
                    apiClient: client,
                    isMember: viewModel.membership?.isMember == true,
                    initialMode: accountMode ?? "transfer",
                    onWithdrawn: {
                        accountMode = nil
                        Task { await viewModel.start() }
                    },
                    onTransferred: {
                        accountMode = nil
                        Task { await viewModel.start() }
                    },
                    onClose: { accountMode = nil }
                )
            }
        }
        .navigationDestination(isPresented: $showAppInfo) {
            AppInfoView(
                userId: viewModel.membership?.userId,
                displayedAt: appInfoDisplayedAt,
                onClose: { showAppInfo = false }
            )
        }
        .sheet(isPresented: $viewModel.lotteryModalPresented, onDismiss: {
            viewModel.finishLotteryPresentation()
        }) {
            Group {
                if let game = viewModel.lotteryPreviewGame {
                    LotteryRewardSheet(previewGame: game) { viewModel.lotteryModalPresented = false }
                } else if let client = viewModel.apiClient {
                    LotteryRewardSheet(apiClient: client) { viewModel.lotteryModalPresented = false }
                }
            }
            .presentationDetents([.medium, .large])
            .presentationDragIndicator(.visible)
        }

        .navigationDestination(isPresented: Binding(
            get: { expandedHomeImageIndex != nil },
            set: { if $0 == false { expandedHomeImageIndex = nil } }
        )) {
            if let client = viewModel.apiClient {
                expandedHomeImage(baseURL: client.baseURL)
            }
        }
        }
        .environment(\.headerNavigation, headerNavigation)
        }
    }

    private func openExternalPage(_ key: String) -> Bool {
        if let tab = viewModel.tabs.first(where: { $0.key == key }),
           tab.openMode == "external", let rawURL = tab.url,
           let url = MediaURL.resolve(rawURL, baseURL: viewModel.apiClient?.baseURL ?? "") {
            openURL(url)
            showHamburgerMenu = false
            return true
        }
        return false
    }

    private func selectMenu(_ key: String) {
        if openExternalPage(key) { return }
        if viewModel.openLotteryPreview(menuKey: key) {
            showHamburgerMenu = false
            return
        }
        guard key != viewModel.selectedTab else { showHamburgerMenu = false; return }
        menuHistory.append(viewModel.selectedTab)
        headerNavigation.backAction = nil
        viewModel.selectedTab = key
        showHamburgerMenu = false
    }

    private func handleHeaderBack() {
        if showHamburgerMenu { showHamburgerMenu = false }
        else if let back = headerNavigation.backAction { back() }
        else if accountMode != nil { accountMode = nil }
        else if showAppInfo { showAppInfo = false }
        else if expandedHomeImageIndex != nil { expandedHomeImageIndex = nil }
        else if viewModel.lotteryModalPresented { viewModel.lotteryModalPresented = false }
        else if let previous = menuHistory.popLast() { viewModel.selectedTab = previous }
        else { showHamburgerMenu = true }
    }

    private func expandedHomeImage(baseURL: String) -> some View {
        ZStack(alignment: .topTrailing) {
            Color.black.ignoresSafeArea()
            Group {
                if viewModel.homeImageURLs.count > 1 {
                    TabView(selection: $expandedHomePage) {
                        ForEach(viewModel.homeImageURLs.indices, id: \.self) { index in
                            RemoteImageView(
                                urlString: viewModel.homeImageURLs[index],
                                baseURL: baseURL,
                                contentMode: .fit
                            )
                            .padding(16)
                            .tag(index)
                        }
                    }
                    #if !os(macOS)
                    .tabViewStyle(.page(indexDisplayMode: .automatic))
                    #endif
                } else if let url = viewModel.homeImageURLs.first {
                    RemoteImageView(urlString: url, baseURL: baseURL, contentMode: .fit)
                        .padding(16)
                } else {
                    Image("DemoStoreHero", bundle: .module)
                        .resizable()
                        .aspectRatio(contentMode: .fit)
                        .padding(16)
                }
            }
            .frame(maxWidth: .infinity, maxHeight: .infinity)
            Button { expandedHomeImageIndex = nil } label: {
                FontAwesomeIcon(systemName: "xmark")
                    .foregroundStyle(.white)
                    .frame(width: 44, height: 44)
                    .background(Color.black.opacity(0.6))
                    .clipShape(Circle())
            }
            .accessibilityLabel("閉じる")
            .padding(16)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .navigationBarBackButtonHidden(true)
        .toolbar(.hidden, for: .navigationBar)
    }

    private var splash: some View {
        ZStack(alignment: .center) {
            AppDesign.canvas.ignoresSafeArea()
            ShellSplashView(showsActivityIndicator: true)
        }
    }

    private func errorScreen(_ message: String) -> some View {
        AppEmptyState(
            title: "接続できませんでした",
            subtitle: message,
            systemImage: "wifi.exclamationmark",
            actionTitle: "再試行",
            action: { viewModel.retry() }
        )
        .background(AppDesign.canvas.ignoresSafeArea())
    }

    @ViewBuilder
    private var mainChrome: some View {
        if let client = viewModel.apiClient {
            ZStack(alignment: .leading) {
                VStack(spacing: 0) {
                    ZStack {
                        AppDesign.canvas.ignoresSafeArea()
                        if let selectedTab = viewModel.tabs.first(where: { $0.key == viewModel.selectedTab }) {
                            tabRoot(selectedTab, client: client)
                                .id(selectedTab.key)
                                .transition(.identity)
                        }
                    }
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                    .animation(nil, value: viewModel.selectedTab)
                    if !viewModel.footerItems.isEmpty {
                        NativeTabBar(
                            items: viewModel.footerItems,
                            colors: viewModel.nativeChromeColors,
                            announcementCount: viewModel.announcementCount,
                            selectedTab: Binding(
                                get: { viewModel.selectedTab },
                                set: {
                                    if openExternalPage($0) { return }
                                    menuHistory = []
                                    headerNavigation.backAction = nil
                                    viewModel.selectedTab = $0
                                }
                            )
                        )
                    }
                }

                if showHamburgerMenu {
                    Color.black.opacity(0.35)
                        .ignoresSafeArea()
                        .onTapGesture {
                            withAnimation(.easeOut(duration: 0.22)) { showHamburgerMenu = false }
                        }
                        .transition(.opacity)
                    hamburgerDrawer
                        .transition(.move(edge: .leading))
                        .zIndex(2)
                }
            }
            .animation(.easeOut(duration: 0.24), value: showHamburgerMenu)
            .environment(\.layoutDirection, .leftToRight)
        } else {
            splash
        }
    }

    private var hamburgerDrawer: some View {
        VStack(alignment: .leading, spacing: 0) {
            HStack {
                Text("メニュー")
                    .font(.headline.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                Spacer()
                Button {
                    withAnimation(.easeOut(duration: 0.22)) { showHamburgerMenu = false }
                } label: {
                    FontAwesomeIcon(systemName: "xmark")
                        .frame(width: 36, height: 36)
                        .foregroundStyle(AppDesign.inkSecondary)
                }
                .buttonStyle(.plain)
                .accessibilityLabel(Text("閉じる"))
            }
            .padding(.horizontal, 16)
            .padding(.top, 12)
            .padding(.bottom, 8)

            ScrollView {
                VStack(spacing: 8) {
                    ForEach(viewModel.hamburgerItems) { item in
                        Button {
                            selectMenu(item.key)
                            withAnimation(.easeOut(duration: 0.22)) { showHamburgerMenu = false }
                        } label: {
                            HStack(spacing: 14) {
                                FontAwesomeIcon(systemName: TabIconResolver.systemName(for: item))
                                    .frame(width: 28)
                                    .foregroundStyle(accent)
                                Text(item.label)
                                    .font(.body.weight(.semibold))
                                    .foregroundStyle(AppDesign.ink)
                                Spacer()
                                FontAwesomeIcon(systemName: "chevron.right")
                                    .foregroundStyle(AppDesign.inkSecondary)
                            }
                            .padding(.horizontal, 16)
                            .frame(minHeight: 54)
                            .background(AppDesign.surface)
                            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
                        }
                        .buttonStyle(.plain)
                    }
                    Button {
                        showHamburgerMenu = false
                        accountMode = "link"
                    } label: {
                        drawerRow(title: "既存会員連携", systemImage: "link")
                    }
                    .buttonStyle(.plain)
                    Button {
                        showHamburgerMenu = false
                        accountMode = "transfer"
                    } label: {
                        drawerRow(title: "引き継ぎ", systemImage: "arrow.left.arrow.right")
                    }
                    .buttonStyle(.plain)
                    if viewModel.membership?.isMember == true {
                        Button {
                            showHamburgerMenu = false
                            accountMode = "withdraw"
                        } label: {
                            drawerRow(title: "退会", systemImage: "xmark.circle")
                        }
                        .buttonStyle(.plain)
                    }
                    Button {
                        appInfoDisplayedAt = Date()
                        showHamburgerMenu = false
                        showAppInfo = true
                    } label: {
                        drawerRow(title: "アプリ情報", systemImage: "info.circle")
                    }
                    .buttonStyle(.plain)
                }
                .padding(16)
            }
        }
        .frame(maxWidth: 300, maxHeight: .infinity, alignment: .top)
        .background(AppDesign.canvas.ignoresSafeArea())
        .frame(maxWidth: .infinity, alignment: .leading)
        .shadow(color: Color.black.opacity(0.18), radius: 16, x: 6, y: 0)
    }

    private func drawerRow(title: String, systemImage: String) -> some View {
        HStack(spacing: 14) {
            FontAwesomeIcon(systemName: systemImage)
                .frame(width: 28)
                .foregroundStyle(accent)
            Text(title)
                .font(.body.weight(.semibold))
                .foregroundStyle(AppDesign.ink)
            Spacer()
            FontAwesomeIcon(systemName: "chevron.right")
                .foregroundStyle(AppDesign.inkSecondary)
        }
        .padding(.horizontal, 16)
        .frame(minHeight: 54)
        .background(AppDesign.surface)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
    }

    @ViewBuilder
    private func tabRoot(_ tab: AppTabItem, client: any ReggitAPIServing) -> some View {
        if let url = tab.url {
            LegalWebView(title: tab.label, urlString: url, baseURL: client.baseURL)
        } else { switch tab.key {
        case "home":
            HomeView(
                apiClient: client,
                homeImageURLs: viewModel.homeImageURLs,
                member: viewModel.homeShowMemberCard ? viewModel.membership : nil,
                projectName: viewModel.projectName,
                menuLayout: viewModel.homeMenuLayout,
                tabs: viewModel.homeTabs,
                onSelectTab: { selectMenu($0) },
                onExpandImage: { index in
                    expandedHomePage = index
                    expandedHomeImageIndex = index
                }
            )
        case "products":
            ProductsView(
                apiClient: client,
                displayLayout: viewModel.productDisplayLayout,
                paymentReturnOrderID: viewModel.paymentReturnOrderID,
                paymentReturnSucceeded: viewModel.paymentReturnSucceeded,
                paymentReturnRevision: viewModel.paymentReturnRevision,
                refreshRevision: viewModel.productsRevision,
                onPaymentConfirmed: { await viewModel.paymentConfirmed() }
            )
        case "orders":
            OrdersView(apiClient: client, reloadRevision: viewModel.ordersRevision)
        case "coupons":
            CouponsView(apiClient: client)
                .id(viewModel.screenRevisions["coupons", default: 0])
        case "stamps":
            StampsView(apiClient: client)
                .id(viewModel.screenRevisions["stamps", default: 0])
        case "queue":
            QueueView(
                apiClient: client,
                refreshRevision: viewModel.queueRevision,
                pendingQueueToken: viewModel.pendingQueueToken,
                onConsumePendingToken: { viewModel.pendingQueueToken = nil }
            )
        case "reservations":
            ReservationsView(apiClient: client)
                .id(viewModel.screenRevisions["reservations", default: 0])
        case "gallery":
            GalleryView(apiClient: client)
                .id(viewModel.screenRevisions["gallery", default: 0])
        case "announcements":
            AnnouncementsView(apiClient: client, onLoaded: { viewModel.announcementCount = $0.count })
                .id(viewModel.screenRevisions["announcements", default: 0])
        case "lottery":
            LotteryView(apiClient: client, refreshRevision: viewModel.lotteryRevision)
        case "storeSearch":
            StoreSearchView(baseURL: client.baseURL, currentProjectKey: viewModel.currentProjectKey) {
                Task { await viewModel.start() }
            }
        default:
            MembershipView(
                apiClient: client,
                projectName: viewModel.projectName,
                initialMember: viewModel.membership,
                membershipRevision: viewModel.membershipRevision,
                opensProfileEditor: tab.key == "profileEdit",
                showRankProgress: viewModel.memberShowRankProgress,
                showPointHistory: viewModel.memberShowPointHistory,
                showCouponActivity: viewModel.memberShowCouponActivity,
                onMembershipUpdated: { viewModel.applyMembership($0) }
            )
        } }
    }
}

struct NativeSharedHeader: View {
    let backgroundColorHex: String
    var colors: [String: String] = [:]
    var imageURL: String? = nil
    var title: String = ""
    var baseURL: String = ""
    let items: [AppTabItem]
    let showsHamburger: Bool
    var menuOpen = false
    var showsBack = false
    let onSelect: (String) -> Void
    let onOpenHamburger: () -> Void

    var body: some View {
        let headerColor = backgroundColorHex.toNativeColor(fallback: .white)
        ZStack {
            headerColor
            HStack(spacing: 4) {
                if showsHamburger {
                    Button(action: onOpenHamburger) {
                        FontAwesomeIcon(systemName: showsBack ? "chevron.left" : "line.3.horizontal")
                            .frame(width: 44, height: 44)
                    }
                    .buttonStyle(.plain)
                    .accessibilityLabel(Text(showsBack ? "戻る" : menuOpen ? "メニューを閉じる" : "メニューを開く"))
                }
                Group {
                    if let imageURL = imageURL, !imageURL.isEmpty {
                        RemoteImageView(urlString: imageURL, baseURL: baseURL, contentMode: .fit, cornerRadius: 0)
                            .accessibilityLabel(Text(title))
                    } else {
                        Text(title).font(.headline).lineLimit(1).minimumScaleFactor(0.75)
                    }
                }
                .frame(maxWidth: .infinity)
                .frame(height: 36)
                .padding(.horizontal, 12)
                ForEach(items) { item in
                    Button { onSelect(item.key) } label: {
                        FontAwesomeIcon(systemName: TabIconResolver.systemName(for: item))
                            .frame(width: 44, height: 44)
                    }
                    .buttonStyle(.plain)
                    .accessibilityLabel(Text(item.label))
                }
                if items.isEmpty { Color.clear.frame(width: 44, height: 44) }
            }
            .padding(.horizontal, 8)
            .foregroundStyle((colors["header_icon"] ?? "").toNativeColor(fallback: BrandPalette(hex: backgroundColorHex).usesDarkForeground ? .black : .white))
        }
        .frame(maxWidth: .infinity)
        .frame(height: PlatformRuntime.navigationHeaderHeight, alignment: .center)
        .background(headerColor.ignoresSafeArea(edges: .top))
        .overlay(alignment: .bottom) {
            Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
        }
    }
}
