import SkipFuse
import SwiftUI

struct NativeRootView: View {
    @State var viewModel: NativeRootViewModel
    @State var showHamburgerMenu = false
    @State var accountMode: String?
    @State var showAppInfo = false
    @State var appInfoDisplayedAt = Date()
    @State var expandedHomeImageIndex: Int? = nil
    @State var expandedHomePage = 0
    @State var isHeaderShown = false

    init(viewModel: NativeRootViewModel) {
        _viewModel = State(initialValue: viewModel)
    }

    private var themeHex: String {
        AppDesign.usesStoreTheme ? viewModel.primaryColorHex : "#173F35"
    }

    private var accent: Color { themeHex.toNativeColor() }

    var body: some View {
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
                        name: Binding(
                            get: { viewModel.registrationName },
                            set: { viewModel.registrationName = $0 }
                        ),
                        phone: Binding(
                            get: { viewModel.registrationPhone },
                            set: { viewModel.registrationPhone = $0 }
                        ),
                        onSubmit: { Task { await viewModel.submitRegistration() } },
                        onSkip: { viewModel.skipRegistration() }
                    )
                case .ready:
                    mainChrome
                }
            }
            .environment(\.brandAccent, accent)
            .environment(\.brandForeground, BrandPalette(hex: themeHex).usesDarkForeground ? Color.black : Color.white)
            .preferredColorScheme(.light)
            .environment(\.apiBaseURL, viewModel.apiClient?.baseURL ?? "")
            .toolbar(.hidden, for: .navigationBar)
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
        .navigationDestination(isPresented: $viewModel.lotteryModalPresented) {
            if let client = viewModel.apiClient {
                LotteryView(apiClient: client, gachaOnly: true)
            }
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
                    NativeSharedHeader(
                        backgroundColorHex: viewModel.navigationHeaderBackgroundColorHex,
                        items: viewModel.headerItems,
                        showsHamburger: true,
                        onSelect: { viewModel.selectedTab = $0 },
                        onOpenHamburger: {
                            withAnimation(.easeOut(duration: 0.24)) { showHamburgerMenu = true }
                        }
                    )
                    .offset(y: isHeaderShown ? 0 : -PlatformRuntime.navigationHeaderHeight)
                    .onAppear {
                        guard !isHeaderShown else { return }
                        withAnimation(.easeOut(duration: 0.28)) { isHeaderShown = true }
                    }
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
                            selectedTab: Binding(
                                get: { viewModel.selectedTab },
                                set: { viewModel.selectedTab = $0 }
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
                            viewModel.selectedTab = item.key
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
                menuLayout: viewModel.homeMenuLayout,
                tabs: viewModel.tabs,
                onSelectTab: { viewModel.selectedTab = $0 },
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
                paymentReturnRevision: viewModel.paymentReturnRevision
            )
        case "orders":
            OrdersView(apiClient: client)
        case "coupons":
            CouponsView(apiClient: client)
        case "stamps":
            StampsView(apiClient: client)
        case "queue":
            QueueView(
                apiClient: client,
                pendingQueueToken: viewModel.pendingQueueToken,
                onConsumePendingToken: { viewModel.pendingQueueToken = nil }
            )
        case "reservations":
            ReservationsView(apiClient: client)
        case "gallery":
            GalleryView(apiClient: client)
        case "announcements":
            AnnouncementsView(apiClient: client)
        case "lottery":
            LotteryView(apiClient: client)
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
    let items: [AppTabItem]
    let showsHamburger: Bool
    let onSelect: (String) -> Void
    let onOpenHamburger: () -> Void

    var body: some View {
        let headerColor = backgroundColorHex.toNativeColor(fallback: .white)
        ZStack {
            headerColor
            HStack(spacing: 4) {
                if showsHamburger {
                    Button(action: onOpenHamburger) {
                        FontAwesomeIcon(systemName: "line.3.horizontal")
                            .frame(width: 44, height: 44)
                    }
                    .buttonStyle(.plain)
                    .accessibilityLabel(Text("メニューを開く"))
                }
                Spacer()
                ForEach(items) { item in
                    Button { onSelect(item.key) } label: {
                        FontAwesomeIcon(systemName: TabIconResolver.systemName(for: item))
                            .frame(width: 44, height: 44)
                    }
                    .buttonStyle(.plain)
                    .accessibilityLabel(Text(item.label))
                }
            }
            .padding(.horizontal, 8)
            .foregroundStyle(BrandPalette(hex: backgroundColorHex).usesDarkForeground ? Color.black : Color.white)
        }
        .frame(maxWidth: .infinity)
        .frame(height: PlatformRuntime.navigationHeaderHeight, alignment: .center)
        .background(headerColor.ignoresSafeArea(edges: .top))
        .overlay(alignment: .bottom) {
            Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
        }
    }
}
