import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class CouponsViewModel {
    var isLoading = true
    var errorMessage: String?
    var coupons: [CouponItem] = []
    var deviceOnceCoupons: [DeviceOnceCouponItem] = []
    var actionMessage: String?
    var selectedSegment = 0
    var pendingUse: CouponItem?
    var redemptionResult: CouponRedemptionResult?
    var isUsing = false

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var mediaBaseURL: String { apiClient.baseURL }
    var availableCoupons: [CouponItem] { coupons.filter(\.isAvailableTab) }
    var archiveCoupons: [CouponItem] { coupons.filter(\.isArchiveTab) }
    var usedCouponCount: Int { archiveCoupons.filter { $0.status == "used" }.count }
    var expiredCouponCount: Int { archiveCoupons.filter { $0.status == "expired" }.count }

    func load(force: Bool = false) async {
        if !force, let cached = SecondaryScreenCache.coupons {
            coupons = cached
            deviceOnceCoupons = SecondaryScreenCache.deviceOnceCoupons ?? []
            isLoading = false
            return
        }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            if !force, let cached = SecondaryScreenCache.recentMemberCoupons {
                coupons = cached
                deviceOnceCoupons = (try? await apiClient.fetchDeviceOnceCoupons()) ?? []
            } else {
                async let owned = apiClient.fetchMemberCoupons()
                async let claimable = apiClient.fetchDeviceOnceCoupons()
                coupons = try await owned
                deviceOnceCoupons = (try? await claimable) ?? []
            }
            SecondaryScreenCache.coupons = coupons
            SecondaryScreenCache.recentMemberCoupons = coupons
            SecondaryScreenCache.deviceOnceCoupons = deviceOnceCoupons
        }
        catch { errorMessage = error.localizedDescription }
    }

    func claim(_ coupon: DeviceOnceCouponItem) async {
        guard !isUsing else { return }
        isUsing = true
        defer { isUsing = false }
        do {
            try await apiClient.claimDeviceOnceCoupon(id: coupon.id)
            actionMessage = "「\(coupon.name)」を獲得しました"
            await load(force: true)
        } catch {
            actionMessage = error.localizedDescription
        }
    }

    func redeemCoupon(_ coupon: CouponItem) async {
        guard !isUsing else { return }
        isUsing = true
        defer { isUsing = false }
        do {
            let result = try await apiClient.redeemCoupon(id: coupon.id)
            if result.shouldShowBarcode {
                redemptionResult = result
            } else {
                actionMessage = "「\(coupon.name)」を利用しました"
            }
            await load(force: true)
        } catch {
            actionMessage = error.localizedDescription
        }
    }
}

struct CouponsView: View {
    @State var viewModel: CouponsViewModel
    /// クーポンは店舗を限定できないため、プロジェクトの全店舗で使える
    let storeNames: [String]

    init(apiClient: any ReggitAPIServing, storeNames: [String] = []) {
        _viewModel = State(initialValue: CouponsViewModel(apiClient: apiClient))
        self.storeNames = storeNames
    }

    static func storeLabel(_ names: [String]) -> String? {
        switch names.count {
        case 0: return nil
        case 1: return "\(names[0])で使えます"
        default: return "全店舗で使えます（\(names.joined(separator: "・"))）"
        }
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                VStack(spacing: 0) {
                    AppSegmentedControl(
                        titles: ["利用可能", "履歴"],
                        selectedIndex: Binding(
                            get: { viewModel.selectedSegment },
                            set: { viewModel.selectedSegment = $0 }
                        )
                    )
                    .padding(.horizontal, 16)
                    .padding(.top, 8)
                    .padding(.bottom, 8)

                    content
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .refreshable { await viewModel.load(force: true) }
                .overlay {
                    AppToastHost(message: Binding(
                        get: { viewModel.actionMessage },
                        set: { viewModel.actionMessage = $0 }
                    ))
                }
                .alert(
                    "クーポンを使いますか？",
                    isPresented: Binding(
                        get: { viewModel.pendingUse != nil },
                        set: { if !$0 { viewModel.pendingUse = nil } }
                    )
                ) {
                    Button("使用する") {
                        if let coupon = viewModel.pendingUse {
                            viewModel.pendingUse = nil
                            Task { await viewModel.redeemCoupon(coupon) }
                        }
                    }
                    Button("キャンセル", role: .cancel) {
                        viewModel.pendingUse = nil
                    }
                } message: {
                    Text("スタッフにご確認のうえ利用してください。利用後は元に戻せません。\n" + (viewModel.pendingUse?.name ?? ""))
                }
                .navigationDestination(item: Binding(
                    get: { viewModel.redemptionResult },
                    set: { viewModel.redemptionResult = $0 }
                )) { result in
                    CouponBarcodeView(result: result) {
                        viewModel.redemptionResult = nil
                    }
                    .headerBackDestination()
                }
            }
        }
        .task { await viewModel.load() }
    }

    @ViewBuilder
    private var content: some View {
        let list = viewModel.selectedSegment == 0 ? viewModel.availableCoupons : viewModel.archiveCoupons
        AppAsyncScreen(
            isLoading: viewModel.isLoading,
            errorMessage: viewModel.errorMessage,
            isEmpty: list.isEmpty,
            emptyTitle: viewModel.selectedSegment == 0 ? "利用できるクーポンはありません" : "履歴はありません",
            emptySubtitle: viewModel.selectedSegment == 0
                ? "新しいクーポンが届くとここに表示されます"
                : "使用済み・期限切れのクーポンがここに表示されます",
            emptyIcon: "ticket",
            onRetry: { Task { await viewModel.load() } },
            skeleton: { CouponsSkeletonView() }
        ) {
            ScrollView {
                LazyVStack(spacing: 14) {
                    if viewModel.selectedSegment == 0, !viewModel.deviceOnceCoupons.isEmpty {
                        deviceCouponSection
                    }
                    if viewModel.selectedSegment == 1, !list.isEmpty {
                        historySummary
                    }
                    ForEach(list) { coupon in
                        couponCard(coupon, showUse: viewModel.selectedSegment == 0)
                    }
                }
                .padding(16)
            }
        }
    }

    private var deviceCouponSection: some View {
        VStack(alignment: .leading, spacing: 10) {
            FontAwesomeLabel("この端末で獲得できるクーポン", systemImage: "gift.fill")
                .font(.headline)
                .foregroundStyle(AppDesign.ink)
            ForEach(viewModel.deviceOnceCoupons) { coupon in
                AppCard {
                    HStack(spacing: 12) {
                        RemoteImageView(
                            urlString: coupon.imageURL,
                            baseURL: viewModel.mediaBaseURL,
                            contentMode: .fill
                        )
                        .frame(width: 72, height: 72)
                        .clipped()
                        VStack(alignment: .leading, spacing: 4) {
                            Text(coupon.name).font(.subheadline.weight(.bold))
                            if !coupon.description.isEmpty {
                                Text(coupon.description).font(.caption).foregroundStyle(AppDesign.inkSecondary).lineLimit(2)
                            }
                            Text("1端末1回限り").font(.caption2.weight(.semibold)).foregroundStyle(AppDesign.forest)
                        }
                        Spacer(minLength: 4)
                        Button("獲得") { Task { await viewModel.claim(coupon) } }
                            .buttonStyle(.borderedProminent)
                            .disabled(viewModel.isUsing)
                    }
                    .padding(12)
                }
            }
        }
    }

    private var historySummary: some View {
        HStack(spacing: 12) {
            FontAwesomeIcon(systemName: "clock.arrow.circlepath")
                .foregroundStyle(AppDesign.forest)
                .frame(width: 42, height: 42)
                .background(AppDesign.surface)
                .clipShape(Circle())
            VStack(alignment: .leading, spacing: 3) {
                Text("これまでのクーポン")
                    .font(.subheadline.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                Text("利用済み \(viewModel.usedCouponCount)件 · 期限切れ \(viewModel.expiredCouponCount)件")
                    .font(.caption)
                    .foregroundStyle(AppDesign.inkSecondary)
            }
            Spacer(minLength: 0)
        }
        .padding(14)
        .background(AppDesign.surfaceMuted)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
    }

    private func couponCard(_ coupon: CouponItem, showUse: Bool) -> some View {
        let isInactive = !coupon.isUsable
        return AppCard {
            VStack(alignment: .leading, spacing: 0) {
                ZStack(alignment: .topLeading) {
                    RemoteImageView(
                        urlString: coupon.imageURL,
                        baseURL: viewModel.mediaBaseURL,
                        contentMode: .fill
                    )
                    .frame(maxWidth: .infinity)
                    .frame(height: 180)
                    .clipped()
                    .opacity(isInactive ? 0.5 : 1)

                    StatusBadge(text: coupon.statusLabel, emphasized: coupon.isUsable)
                        .padding(12)
                }

                VStack(alignment: .leading, spacing: 10) {
                    Text(coupon.name)
                        .font(.system(size: 19, weight: .bold))
                        .foregroundStyle(AppDesign.ink)
                        .fixedSize(horizontal: false, vertical: true)
                    if !coupon.description.isEmpty {
                        Text(coupon.description)
                            .font(.system(size: 14))
                            .foregroundStyle(AppDesign.inkSecondary)
                            .fixedSize(horizontal: false, vertical: true)
                    }
                    VStack(alignment: .leading, spacing: 6) {
                        if !coupon.validFrom.isEmpty {
                            FontAwesomeLabel("利用開始 \(AppFormat.dateTime(coupon.validFrom))", systemImage: "calendar.badge.clock")
                                .font(.system(size: 12, weight: .medium))
                                .foregroundStyle(AppDesign.inkSecondary)
                        }
                        if !coupon.validUntil.isEmpty {
                            FontAwesomeLabel("利用終了 \(AppFormat.dateTime(coupon.validUntil))", systemImage: "calendar")
                                .font(.system(size: 12, weight: .medium))
                                .foregroundStyle(AppDesign.inkSecondary)
                        }
                        if coupon.validFrom.isEmpty, coupon.validUntil.isEmpty {
                            FontAwesomeLabel("期間中いつでも利用できます", systemImage: "infinity")
                                .font(.system(size: 12, weight: .medium))
                                .foregroundStyle(AppDesign.inkSecondary)
                        }
                        if let storeLabel = Self.storeLabel(storeNames) {
                            FontAwesomeLabel(storeLabel, systemImage: "location.fill")
                                .font(.system(size: 12, weight: .medium))
                                .foregroundStyle(AppDesign.inkSecondary)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                    }

                    if showUse, coupon.isUsable {
                        HStack {
                            Spacer(minLength: 0)
                            Button {
                                viewModel.pendingUse = coupon
                            } label: {
                                if viewModel.isUsing {
                                    ProgressView().tint(.white)
                                } else {
                                    Text("使う").font(.subheadline.weight(.semibold))
                                }
                            }
                            .frame(minWidth: 160, minHeight: 48)
                            .foregroundStyle(.white)
                            .background(AppDesign.ink)
                            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                            .buttonStyle(.plain)
                            .disabled(viewModel.isUsing)
                            Spacer(minLength: 0)
                        }
                    } else if showUse {
                        HStack {
                            Spacer(minLength: 0)
                            FontAwesomeLabel("利用開始前", systemImage: "lock")
                                .font(.caption.weight(.semibold))
                                .foregroundStyle(AppDesign.inkSecondary)
                            Spacer(minLength: 0)
                        }
                    }
                }
                .padding(16)

                if !showUse {
                    Divider()
                    HStack(spacing: 8) {
                        FontAwesomeIcon(systemName: coupon.status == "used" ? "checkmark.circle.fill" : "clock.badge.exclamationmark")
                        Text(coupon.statusLabel).fontWeight(.semibold)
                        Spacer(minLength: 8)
                        Text(coupon.status == "used" && !coupon.usedAt.isEmpty
                            ? "利用日 \(AppFormat.dateTime(coupon.usedAt))"
                            : "再利用できません")
                            .font(.caption)
                            .foregroundStyle(AppDesign.inkSecondary)
                    }
                    .font(.subheadline)
                    .foregroundStyle(AppDesign.inkSecondary)
                    .padding(.horizontal, 16)
                    .frame(minHeight: 48)
                    .background(AppDesign.surfaceMuted.opacity(0.72))
                }
            }
        }
    }
}

struct CouponBarcodeView: View {
    let result: CouponRedemptionResult
    let onClose: () -> Void
    @State var remainingSeconds = 300

    private var remainingLabel: String {
        let seconds = remainingSeconds % 60
        let secondsText = seconds < 10 ? "0\(seconds)" : "\(seconds)"
        return "\(remainingSeconds / 60):\(secondsText)"
    }

    var body: some View {
        VStack(spacing: 24) {
                FontAwesomeIcon(systemName: "checkmark.circle.fill")
                    .foregroundStyle(AppDesign.success)
                VStack(spacing: 8) {
                    Text("クーポンを使用しました")
                        .font(.title2.weight(.bold))
                    Text(result.couponName)
                        .font(.body)
                        .foregroundStyle(AppDesign.inkSecondary)
                    if !result.usedAt.isEmpty {
                        Text("利用日 \(AppFormat.dateTime(result.usedAt))")
                            .font(.caption)
                            .foregroundStyle(AppDesign.inkSecondary)
                    }
                }
                VStack(spacing: 14) {
                    Text("このバーコードをスタッフに提示してください")
                        .font(.subheadline.weight(.semibold))
                        .multilineTextAlignment(.center)
                    MemberCodeView(value: result.code, codeType: "barcode", height: 92)
                    Text(result.code)
                        .font(.system(size: 17, weight: .bold, design: .monospaced))
                        .tracking(1)
                    Text("表示終了まで \(remainingLabel)")
                        .font(.caption.weight(.semibold))
                        .foregroundStyle(remainingSeconds <= 60 ? AppDesign.danger : AppDesign.inkSecondary)
                }
                .padding(24)
                .background(AppDesign.surface)
                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
                Spacer()
                AppPrimaryButton(title: "閉じる", action: onClose)
            }
            .padding(24)
            .background(AppDesign.canvas.ignoresSafeArea())
            .navigationTitle("クーポン")
            .navigationBarTitleDisplayMode(.inline)
        .task {
            while remainingSeconds > 0 && !Task.isCancelled {
                try? await Task.sleep(nanoseconds: 1_000_000_000)
                remainingSeconds -= 1
            }
            if remainingSeconds <= 0 { onClose() }
        }
    }
}
