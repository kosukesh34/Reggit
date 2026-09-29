import Foundation
import Observation
import SkipFuse

@MainActor
@Observable
final class NativeRootViewModel: PushNotificationHandling {
    enum Phase: Equatable {
        case loading
        case needsRegistration
        case ready
        case failed(String)
    }

    var phase: Phase = .loading
    var tabs: [AppTabItem] = []
    var homeTabs: [AppTabItem] = []
    var headerItems: [AppTabItem] = []
    var footerItems: [AppTabItem] = []
    var hamburgerItems: [AppTabItem] = []
    var selectedTab: String = "home"
    var currentProjectKey = ""
    var projectName: String = "Reggit"
    var primaryColorHex: String = "#173F35"
    var navigationHeaderImageURL: String?
    var navigationHeaderText: String?
    var navigationHeaderBackgroundColorHex = "#FFFFFF"
    var homeImageURLs: [String] = []
    var nativeChromeColors: [String: String] = [:]
    var homeShowMemberCard = true
    var homeMenuLayout = "grid_3x2"
    var productDisplayLayout = "list_with_image"
    var lotteryModalPresented = false
    var pendingLotteryCount = 0
    private var lastStampsTotal: Int?
    private var realtimeWebSocketURL: String?
    var memberShowRankProgress = true
    var memberShowPointHistory = true
    var memberShowCouponActivity = true
    var pendingQueueToken: String?
    var membership: MemberProfileSnapshot?
    /// プロフィール更新などで会員タブを再読込させるための世代カウンタ
    var membershipRevision = 0
    var queueRevision = 0
    var ordersRevision = 0
    var lotteryRevision = 0
    var paymentReturnOrderID: Int?
    var paymentReturnSucceeded = false
    var paymentReturnRevision = 0
    var registrationName = ""
    var registrationPhone = ""
    var registrationError: String?
    var isRegistering = false
    var showRegistrationPopup = false

    private(set) var apiClient: (any ReggitAPIServing)?
    private let bootstrap: DeviceBootstrapService
    private let pushObserver: any PushNotificationObserving
    private let configRepository: any MobileWebConfigRepository
    private let walletRealtime = MemberWalletRealtime()
    private var walletRefreshTask: Task<Void, Never>?
    private var queueRefreshTask: Task<Void, Never>?
    private var hasStarted = false

    init(
        bootstrap: DeviceBootstrapService = DeviceBootstrapService(),
        pushObserver: any PushNotificationObserving = PushNotificationObserver(),
        configRepository: any MobileWebConfigRepository = BundleMobileWebConfigRepository()
    ) {
        self.bootstrap = bootstrap
        self.pushObserver = pushObserver
        self.configRepository = configRepository
    }

    func onAppear() {
        pushObserver.startObserving(handler: self)
        if hasStarted {
            if !walletRealtime.isRunning { connectRealtimeIfPossible() }
            return
        }
        hasStarted = true
        Task { await start() }
    }

    func onDisappear() {
        pushObserver.stopObserving()
    }

    func shutdown() {
        pushObserver.stopObserving()
        walletRealtime.stop()
        walletRefreshTask?.cancel()
        queueRefreshTask?.cancel()
    }

    func retry() {
        Task { await start() }
    }

    func start() async {
        walletRealtime.stop()
        let hadReadyScreen = phase == .ready
        if !hadReadyScreen { phase = .loading }
        registrationError = nil
        showRegistrationPopup = false
        do {
            let previousProjectKey = currentProjectKey
            currentProjectKey = configRepository.resolveConfig().projectKey
            if previousProjectKey != currentProjectKey {
                pendingLotteryCount = 0
                lastStampsTotal = nil
            }
            let client = try await bootstrap.registerDevice()
            apiClient = client
            let config = await loadAppConfig(client: client)
            let filtered = Self.filterNativeTabs(
                Self.uniqueItems(config.tabs + config.headerItems + config.footerItems + config.hamburgerItems)
            )
            tabs = filtered.isEmpty ? Self.defaultTabs : filtered
            homeTabs = Self.filterNativeTabs(config.tabs)
            headerItems = Array(Self.items(config.headerItems, availableIn: tabs).prefix(1))
            footerItems = Array(Self.items(config.footerItems, availableIn: tabs).prefix(5))
            if !config.hasNavigationConfiguration {
                footerItems = Array(tabs.filter { $0.url == nil }.prefix(5))
            }
            let menu = config.hasNavigationConfiguration
                ? Self.items(config.hamburgerItems, availableIn: tabs)
                : Self.testingMenu
            for item in menu where !tabs.contains(where: { $0.key == item.key }) {
                tabs.append(item)
            }
            hamburgerItems = menu
            projectName = config.projectName
            let hex = config.primaryColor.trimmingCharacters(in: .whitespacesAndNewlines)
            primaryColorHex = hex.isEmpty ? "#173F35" : hex
            navigationHeaderImageURL = config.navigationHeaderImageURL
            navigationHeaderText = config.navigationHeaderText
            navigationHeaderBackgroundColorHex = config.navigationHeaderBackgroundColor
            homeImageURLs = config.homeImageURLs
            nativeChromeColors = config.nativeChromeColors
            homeShowMemberCard = config.homeShowMemberCard
            homeMenuLayout = config.homeMenuLayout
            productDisplayLayout = config.productDisplayLayout
            realtimeWebSocketURL = config.realtimeWebSocketURL
            memberShowRankProgress = config.memberShowRankProgress
            memberShowPointHistory = config.memberShowPointHistory
            memberShowCouponActivity = config.memberShowCouponActivity
            let preferred = configRepository.resolveConfig().fixedTab
            if previousProjectKey != currentProjectKey || !tabs.contains(where: { $0.key == selectedTab }) {
                if tabs.contains(where: { $0.key == preferred }) {
                    selectedTab = preferred
                } else if let first = tabs.first {
                    selectedTab = first.key
                }
            }

            let member = try await loadMembership(using: client)
            membership = member
            if !member.isMember, member.registrationMode.requiresRegistrationGate {
                phase = .needsRegistration
            } else {
                phase = .ready
                showRegistrationPopup = !member.isMember && member.registrationMode == .popup
            }

            connectRealtimeIfPossible()

            if let token = pendingQueueToken, phase == .ready {
                pendingQueueToken = nil
                handleQueueDeepLink(queueToken: token)
            }
        } catch {
            if !hadReadyScreen { phase = .failed(error.localizedDescription) }
            logger.error("Native bootstrap failed: \(error.localizedDescription)")
        }
    }

    func submitRegistration() async {
        guard let apiClient else { return }
        let name = registrationName.trimmingCharacters(in: .whitespacesAndNewlines)
        let phone = registrationPhone.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !name.isEmpty, !phone.isEmpty else {
            registrationError = "氏名と電話番号を入力してください"
            return
        }
        isRegistering = true
        registrationError = nil
        defer { isRegistering = false }
        do {
            var member = try await apiClient.registerMember(name: name, phone: phone)
            if member.isMember, member.memberNumber.isEmpty,
               let card = try? await apiClient.fetchPointCard() {
                member = member.withCard(card)
            }
            membership = member
            connectRealtimeIfPossible()
            phase = .ready
            membershipRevision &+= 1
            if tabs.contains(where: { $0.key == "member" }) {
                selectedTab = "member"
            }
        } catch {
            registrationError = error.localizedDescription
        }
    }

    func applyMembership(_ member: MemberProfileSnapshot) {
        membership = member
        connectRealtimeIfPossible()
    }

    private func connectRealtimeIfPossible() {
        guard let websocketURL = realtimeWebSocketURL,
              !websocketURL.isEmpty,
              !currentProjectKey.isEmpty else { return }
        let memberID = membership?.isMember == true ? membership?.memberId : nil
        let nativeClient = apiClient as? ReggitAPIClient
        walletRealtime.start(
            websocketURL: websocketURL,
            client: nativeClient,
            memberID: memberID,
            projectKey: currentProjectKey,
            onWalletUpdated: { [weak self] points, stampsTotal, count in
            guard let self else { return }
            let pointsChanged = points != nil && points != self.membership?.points
            let stampsChanged = stampsTotal != nil && stampsTotal != self.lastStampsTotal
            let lotteryChanged = count != self.pendingLotteryCount
            guard pointsChanged || stampsChanged || lotteryChanged else { return }
            if pointsChanged, let points, let member = self.membership {
                self.membership = member.withWalletPoints(points)
                SecondaryScreenCache.pointHistory = nil
                SecondaryScreenCache.memberProfileNeedsRefresh = true
                if self.selectedTab == "member" {
                    self.walletRefreshTask?.cancel()
                    self.walletRefreshTask = Task {
                        try? await Task.sleep(nanoseconds: 350_000_000)
                        if !Task.isCancelled { self.membershipRevision &+= 1 }
                    }
                }
            }
            if stampsChanged {
                SecondaryScreenCache.stamps = nil
                self.lastStampsTotal = stampsTotal
            }
            if lotteryChanged {
                SecondaryScreenCache.lottery = nil
                if self.selectedTab == "lottery" { self.lotteryRevision &+= 1 }
            }
            if count > self.pendingLotteryCount {
                Task {
                    guard let nativeClient else { return }
                    guard let state = try? await nativeClient.fetchLotteryState(),
                          state.gachaEnabled,
                          state.pending(for: "gacha") != nil else { return }
                    self.lotteryModalPresented = true
                }
            }
            self.pendingLotteryCount = count
        }, onProjectUpdated: { [weak self] topic in
            guard let self else { return }
            SecondaryScreenCache.invalidate(topic: topic)
            if topic == "orders", self.selectedTab == "orders" {
                self.queueRefreshTask?.cancel()
                self.queueRefreshTask = Task {
                    try? await Task.sleep(nanoseconds: 350_000_000)
                    if !Task.isCancelled { self.ordersRevision &+= 1 }
                }
            }
            if topic == "orders", self.selectedTab == "lottery" {
                self.lotteryRevision &+= 1
            }
            if topic == "orders", self.selectedTab == "products" {
                SecondaryScreenCache.orders = nil
                SecondaryScreenCache.lottery = nil
                self.lotteryRevision &+= 1
            }
            if topic == "queue", self.selectedTab == "queue" {
                self.queueRefreshTask?.cancel()
                self.queueRefreshTask = Task {
                    try? await Task.sleep(nanoseconds: 350_000_000)
                    if !Task.isCancelled, SecondaryScreenCache.queue == nil {
                        self.queueRevision &+= 1
                    }
                }
            }
        })
    }

    func skipRegistration() {
        guard membership?.registrationMode.canSkipRegistration != false else { return }
        phase = .ready
    }

    func openRegistrationFromPopup() {
        guard phase == .ready, membership?.isMember == false else { return }
        showRegistrationPopup = false
        phase = .needsRegistration
    }

    func handleQueueDeepLink(queueToken: String) {
        let token = queueToken.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !token.isEmpty else { return }
        if phase == .ready {
            selectedTab = "queue"
            pendingQueueToken = token
        } else {
            pendingQueueToken = token
        }
    }

    func handlePaymentReturn(orderID: Int, succeeded: Bool) {
        paymentReturnOrderID = orderID
        paymentReturnSucceeded = succeeded
        paymentReturnRevision &+= 1
        selectedTab = "products"
        if succeeded {
            SecondaryScreenCache.memberProfileNeedsRefresh = true
            SecondaryScreenCache.pointHistory = nil
            membershipRevision &+= 1
        }
    }

    func paymentConfirmed() async {
        guard let apiClient else { return }
        SecondaryScreenCache.orders = nil
        SecondaryScreenCache.lottery = nil
        ordersRevision &+= 1
        lotteryRevision &+= 1
        if let state = try? await apiClient.fetchLotteryState() {
            pendingLotteryCount = state.entitlements.count
            if !state.entitlements.isEmpty {
                lotteryModalPresented = true
            }
        }
    }

    // MARK: - PushNotificationHandling

    func handleNotificationDeepLink(tab: String, notificationType: String?) {
        let mapped = Self.mapTabKey(tab)
        if tabs.contains(where: { $0.key == mapped }) {
            selectedTab = mapped
        } else if notificationType == "coupon_issued" {
            selectedTab = "coupons"
        }
    }

    func handleForegroundNotification(notificationType: String?) {
        if notificationType == "coupon_issued" {
            selectedTab = "coupons"
        }
    }

    func injectPushToken(_ token: String) async {
        UserDefaults.standard.set(token, forKey: NativeAppStorage.pushTokenKey)
        guard let apiClient else { return }
        do {
            _ = try await bootstrap.registerDevice(using: apiClient)
        } catch {
            logger.warning("Push token re-register failed: \(error.localizedDescription)")
        }
    }

    // MARK: - Tabs

    static let supportedTabKeys: Set<String> = [
        "home", "member", "profile", "profileEdit", "products", "orders", "coupons", "stamps", "queue", "reservations", "gallery", "announcements", "lottery", "storeSearch", "terms", "privacy",
    ]

    /// テスト用。サーバのメニュー設定に関係なく、実装済み機能を全部出す。
    static let testingMenu: [AppTabItem] = [
        AppTabItem(id: "home", key: "home", label: "ホーム", icon: "fa-home", sfSymbol: "house.fill"),
        AppTabItem(id: "member", key: "member", label: "会員", icon: "fa-user", sfSymbol: "person.crop.circle.fill"),
        AppTabItem(id: "products", key: "products", label: "注文", icon: "fa-utensils", sfSymbol: "cart.fill"),
        AppTabItem(id: "orders", key: "orders", label: "注文履歴", icon: "fa-receipt", sfSymbol: "list.clipboard.fill"),
        AppTabItem(id: "coupons", key: "coupons", label: "クーポン", icon: "fa-ticket", sfSymbol: "ticket.fill"),
        AppTabItem(id: "stamps", key: "stamps", label: "スタンプ", icon: "fa-stamp", sfSymbol: "star.fill"),
        AppTabItem(id: "queue", key: "queue", label: "整理券", icon: "fa-hashtag", sfSymbol: "number.circle.fill"),
        AppTabItem(id: "reservations", key: "reservations", label: "予約", icon: "fa-calendar", sfSymbol: "calendar"),
        AppTabItem(id: "gallery", key: "gallery", label: "ギャラリー", icon: "fa-image", sfSymbol: "photo.on.rectangle"),
        AppTabItem(id: "announcements", key: "announcements", label: "お知らせ", icon: "fa-bell", sfSymbol: "bell.fill"),
        AppTabItem(id: "lottery", key: "lottery", label: "抽選", icon: "fa-dice", sfSymbol: "dice.fill"),
        AppTabItem(id: "storeSearch", key: "storeSearch", label: "店舗を探す", icon: "fa-location-dot", sfSymbol: "location.fill"),
    ]

    static let defaultTabs: [AppTabItem] = [
        AppTabItem(id: "home", key: "home", label: "ホーム", icon: "fa-home", sfSymbol: "house.fill"),
        AppTabItem(id: "member", key: "member", label: "会員", icon: "fa-user", sfSymbol: "person.crop.circle.fill"),
        AppTabItem(id: "products", key: "products", label: "注文", icon: "fa-utensils", sfSymbol: "cart.fill"),
        AppTabItem(id: "coupons", key: "coupons", label: "クーポン", icon: "fa-ticket", sfSymbol: "ticket.fill"),
        AppTabItem(id: "stamps", key: "stamps", label: "スタンプ", icon: "fa-stamp", sfSymbol: "star.fill"),
    ]

    static func filterNativeTabs(_ tabs: [AppTabItem]) -> [AppTabItem] {
        var result: [AppTabItem] = []
        for tab in tabs {
            let key = mapTabKey(tab.key)
            guard key != "designlab", supportedTabKeys.contains(key) || tab.url != nil else { continue }
            if result.contains(where: { $0.key == key }) {
                continue
            }
            result.append(
                AppTabItem(
                    id: key,
                    key: key,
                    label: tab.label,
                    icon: tab.icon,
                    sfSymbol: tab.sfSymbol,
                    url: tab.url
                )
            )
        }
        return result
    }

    static func mapTabKey(_ key: String) -> String {
        switch key {
        case "membership": return "member"
        case "mobileOrder", "dineInOrder": return "products"
        case "coupon": return "coupons"
        case "stamp": return "stamps"
        case "reservation": return "reservations"
        case "store", "stores": return "storeSearch"
        default: return key
        }
    }

    static func uniqueItems(_ items: [AppTabItem]) -> [AppTabItem] {
        var seen = Set<String>()
        return items.filter { seen.insert($0.key).inserted }
    }

    static func items(_ requested: [AppTabItem], availableIn available: [AppTabItem]) -> [AppTabItem] {
        requested.compactMap { item in available.first(where: { $0.key == item.key }) }
    }

    private func loadMembership(using client: any ReggitAPIServing) async throws -> MemberProfileSnapshot {
        var member = try await client.fetchMemberProfile()
        if member.isMember, member.memberNumber.isEmpty,
           let card = try? await client.fetchPointCard() {
            member = member.withCard(card)
        }
        return member
    }

    private func loadAppConfig(client _: any ReggitAPIServing) async -> AppConfig {
        if let cached = SecondaryScreenCache.appConfig {
            return cached
        }
        let shell = configRepository.resolveConfig()
        if let fetched = await AppConfigClient(baseUrl: shell.baseUrl, projectKey: shell.projectKey).fetchConfig() {
            SecondaryScreenCache.appConfig = fetched
            return fetched
        }
        return AppConfig(projectName: "Reggit", tabs: Self.defaultTabs, primaryColor: "#173F35")
    }
}
