import SkipFuse
import SwiftUI

struct NativeRootView: View {
    @State var viewModel: NativeRootViewModel

    init(viewModel: NativeRootViewModel) {
        _viewModel = State(initialValue: viewModel)
    }

    private var themeHex: String {
        AppDesign.usesStoreTheme ? viewModel.primaryColorHex : "#173F35"
    }

    private var accent: Color { themeHex.toNativeColor() }

    var body: some View {
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
        }
        .onAppear { viewModel.onAppear() }
        .onDisappear { viewModel.onDisappear() }
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
            VStack(spacing: 0) {
                NativeSharedHeader(
                    projectName: viewModel.projectName,
                    imageURL: viewModel.navigationHeaderImageURL,
                    backgroundColorHex: viewModel.navigationHeaderBackgroundColorHex,
                    baseURL: client.baseURL
                )
                TabView(selection: Binding(
                    get: { overflowTabs.contains(where: { $0.key == viewModel.selectedTab }) ? "more" : viewModel.selectedTab },
                    set: { viewModel.selectedTab = $0 }
                )) {
                    ForEach(primaryTabs) { tab in
                        tabRoot(tab, client: client)
                            .tabItem {
                                Label(tab.label, systemImage: TabIconResolver.systemName(for: tab))
                            }
                            .tag(tab.key)
                    }
                    if !overflowTabs.isEmpty {
                        moreContent(client: client)
                            .tabItem { Label("メニュー", systemImage: "square.grid.2x2") }
                            .tag("more")
                    }
                }
                .tint(accent)
                .background(AppDesign.canvas.ignoresSafeArea())
            }
        } else {
            splash
        }
    }

    private var primaryTabs: [AppTabItem] {
        if viewModel.tabs.count <= 5 { return viewModel.tabs }
        // Preserve store ordering, keeping membership accessible even in a large feature set.
        var result = Array(viewModel.tabs.prefix(4))
        if let member = viewModel.tabs.first(where: { $0.key == "member" }), !result.contains(where: { $0.key == "member" }) {
            result[3] = member
        }
        return result
    }

    private var overflowTabs: [AppTabItem] {
        viewModel.tabs.filter { tab in !primaryTabs.contains(where: { $0.key == tab.key }) }
    }

    @ViewBuilder
    private func moreContent(client: any ReggitAPIServing) -> some View {
        if let selected = overflowTabs.first(where: { $0.key == viewModel.selectedTab }) {
            VStack(spacing: 0) {
                HStack {
                    Button { viewModel.selectedTab = "more" } label: {
                        Label("すべてのメニュー", systemImage: "chevron.left")
                            .font(.subheadline.weight(.semibold)).frame(minHeight: 44)
                    }
                    Spacer()
                }
                .padding(.horizontal, 20)
                .background(AppDesign.canvas)
                tabRoot(selected, client: client)
            }
        } else {
            NavigationStack {
                ScrollView {
                    QuickActionGrid(tabs: overflowTabs, onSelect: { viewModel.selectedTab = $0 })
                        .padding(20)
                }
                .background(AppDesign.canvas)
                .navigationTitle("メニュー")
            }
        }
    }

    @ViewBuilder
    private func tabRoot(_ tab: AppTabItem, client: any ReggitAPIServing) -> some View {
        switch tab.key {
        case "home":
            HomeView(
                apiClient: client,
                homeImageURL: viewModel.homeImageURL,
                menuLayout: viewModel.homeMenuLayout,
                tabs: viewModel.tabs,
                onSelectTab: { viewModel.selectedTab = $0 }
            )
        case "products":
            ProductsView(
                apiClient: client,
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
        }
    }
}

struct NativeSharedHeader: View {
    let projectName: String
    let imageURL: String?
    let backgroundColorHex: String
    let baseURL: String

    var body: some View {
        ZStack {
            backgroundColorHex.toNativeColor()
            if imageURL != nil {
                RemoteImageView(
                    urlString: imageURL,
                    baseURL: baseURL,
                    contentMode: .fit,
                    usesDemoFallback: false
                )
                .frame(width: PlatformRuntime.navigationHeaderImageWidth, height: 34, alignment: .center)
                .offset(y: PlatformRuntime.navigationHeaderImageVerticalOffset)
                .accessibilityLabel(Text(projectName))
            } else {
                Text(projectName)
                    .font(.headline.weight(.bold))
                    .foregroundStyle(BrandPalette(hex: backgroundColorHex).usesDarkForeground ? Color.black : Color.white)
                    .lineLimit(1)
            }
        }
        .frame(maxWidth: .infinity)
        .frame(height: PlatformRuntime.navigationHeaderHeight, alignment: .center)
        .overlay(alignment: .bottom) {
            Rectangle().fill(AppDesign.hairline).frame(height: 0.5)
        }
    }
}
