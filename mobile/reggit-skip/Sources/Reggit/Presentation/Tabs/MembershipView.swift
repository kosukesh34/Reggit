import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class MembershipViewModel {
    var isLoading = true
    var errorMessage: String?
    var member: MemberProfileSnapshot?
    var pointHistory: [PointHistoryItem] = []
    var recentCoupons: [CouponItem] = []
    var registerLastName = ""
    var registerFirstName = ""
    var registerPhone = ""
    var isRegistering = false
    var registerError: String?
    var showProfileDetails = false
    var showProfileEdit = false
    var editName = ""
    var editPhone = ""
    var editPayload: [String: String] = [:]
    var editListPayload: [String: [String]] = [:]
    var isSavingProfile = false
    var profileError: String?
    var profileFieldErrors: [String: String] = [:]
    var profileSuccess: String?
    var isAddingWallet = false
    var walletMessage: String?
    var walletError: String?
    var showBarcodeEnlarge = false
    let showPointHistory: Bool
    let showCouponActivity: Bool

    private let apiClient: any ReggitAPIServing

    init(
        apiClient: any ReggitAPIServing,
        initialMember: MemberProfileSnapshot? = nil,
        showPointHistory: Bool = true,
        showCouponActivity: Bool = true
    ) {
        self.apiClient = apiClient
        self.member = initialMember
        self.isLoading = initialMember == nil
        self.showPointHistory = showPointHistory
        self.showCouponActivity = showCouponActivity
    }

    var mediaBaseURL: String { apiClient.baseURL }
    var canAddToWallet: Bool { NativeWalletBridge.canAddAppleWalletPass }

    func load(latestMember: MemberProfileSnapshot? = nil, force: Bool = false) async {
        if let latestMember { member = latestMember }
        if member == nil { isLoading = true }
        errorMessage = nil
        defer { isLoading = false }
        do {
            if member == nil || force || SecondaryScreenCache.memberProfileNeedsRefresh {
                var snapshot = try await apiClient.fetchMemberProfile()
                if snapshot.isMember, snapshot.memberNumber.isEmpty,
                   let card = try? await apiClient.fetchPointCard() {
                    snapshot = snapshot.withCard(card)
                }
                member = snapshot
                SecondaryScreenCache.memberProfileNeedsRefresh = false
            }
            if member?.isMember == true {
                if showPointHistory {
                    if !force, let cached = SecondaryScreenCache.pointHistory { pointHistory = cached }
                    else {
                        if let latest = try? await apiClient.fetchPointHistory() {
                            pointHistory = latest
                            SecondaryScreenCache.pointHistory = latest
                        }
                    }
                }
                if showCouponActivity {
                    if !force, let cached = SecondaryScreenCache.recentMemberCoupons { recentCoupons = cached }
                    else {
                        if let latest = try? await apiClient.fetchMemberCoupons() {
                            recentCoupons = latest
                            SecondaryScreenCache.recentMemberCoupons = latest
                        }
                    }
                }
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func register() async {
        let name = [registerLastName, registerFirstName]
            .map { $0.trimmed }
            .filter { !$0.isEmpty }
            .joined(separator: " ")
        let phone = registerPhone.trimmed
        guard !registerLastName.trimmed.isEmpty, !registerFirstName.trimmed.isEmpty, !phone.isEmpty else {
            registerError = "姓、名、電話番号を入力してください"
            return
        }
        isRegistering = true
        registerError = nil
        defer { isRegistering = false }
        do {
            member = try await apiClient.registerMember(name: name, phone: phone)
        } catch {
            registerError = error.localizedDescription
        }
    }

    func openProfileEdit() {
        editName = member?.name ?? ""
        editPhone = member?.phone ?? ""
        editPayload = member?.profilePayload ?? [:]
        editPayload["name"] = editName
        editPayload["phone"] = editPhone
        editListPayload = member?.profileListPayload ?? [:]
        profileError = nil
        profileFieldErrors = [:]
        showProfileEdit = true
    }

    func saveProfile() async {
        guard !isSavingProfile else { return }
        let name = (editPayload["name"] ?? editName).trimmed
        let phone = (editPayload["phone"] ?? editPhone).trimmed
        for field in member?.editableProfileFields ?? [] {
            if let message = field.validationMessage(value: editPayload[field.key] ?? "", selections: editListPayload[field.key] ?? []) {
                profileError = message
                return
            }
        }
        isSavingProfile = true
        profileError = nil
        profileFieldErrors = [:]
        defer { isSavingProfile = false }
        do {
            member = try await apiClient.updateMemberProfile(
                name: name,
                phone: phone,
                profilePayload: editPayload,
                profileListPayload: editListPayload
            )
            showProfileEdit = false
            profileSuccess = "プロフィールを更新しました"
        } catch {
            if case ReggitAPIError.validation(let errors) = error {
                profileFieldErrors = errors
                profileError = errors.values.sorted().joined(separator: "\n")
            } else {
                profileError = error.localizedDescription
            }
        }
    }

    func addToAppleWallet() async {
        guard canAddToWallet else {
            walletError = "この端末では Apple Wallet に対応していません"
            return
        }
        isAddingWallet = true
        walletError = nil
        walletMessage = nil
        defer { isAddingWallet = false }
        do {
            let passURL = try await apiClient.fetchWalletPassDownloadURL()
            try await NativeWalletBridge.addAppleWalletPass?(passURL)
        } catch {
            walletError = error.localizedDescription
        }
    }
}

struct MembershipView: View {
    @State var viewModel: MembershipViewModel
    @Environment(\.appButtonColors) var buttonColors
    @Environment(\.brandForeground) var brandForeground
    let initialMember: MemberProfileSnapshot?
    let projectName: String
    let membershipRevision: Int
    @State var didOpenProfileEditor = false
    let opensProfileEditor: Bool
    let pageStyle: String
    let showRankProgress: Bool
    let showPointHistory: Bool
    let showCouponActivity: Bool
    let onMembershipUpdated: ((MemberProfileSnapshot) -> Void)?
    @Environment(\.brandAccent) var accent

    init(
        apiClient: any ReggitAPIServing,
        projectName: String,
        initialMember: MemberProfileSnapshot? = nil,
        membershipRevision: Int = 0,
        opensProfileEditor: Bool = false,
        pageStyle: String = "classic",
        showRankProgress: Bool = true,
        showPointHistory: Bool = true,
        showCouponActivity: Bool = true,
        onMembershipUpdated: ((MemberProfileSnapshot) -> Void)? = nil
    ) {
        _viewModel = State(initialValue: MembershipViewModel(
            apiClient: apiClient,
            initialMember: initialMember,
            showPointHistory: showPointHistory,
            showCouponActivity: showCouponActivity
        ))
        self.initialMember = initialMember
        self.projectName = projectName
        self.membershipRevision = membershipRevision
        self.opensProfileEditor = opensProfileEditor
        self.pageStyle = pageStyle
        self.showRankProgress = showRankProgress
        self.showPointHistory = showPointHistory
        self.showCouponActivity = showCouponActivity
        self.onMembershipUpdated = onMembershipUpdated
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                Group {
                    if viewModel.isLoading && viewModel.member == nil {
                        MembershipSkeletonView()
                    } else if let error = viewModel.errorMessage, viewModel.member == nil {
                        AppEmptyState(
                            title: "読み込みに失敗",
                            subtitle: error,
                            systemImage: "exclamationmark.triangle",
                            actionTitle: "再読み込み",
                            action: { Task { await viewModel.load() } }
                        )
                    } else if let member = viewModel.member {
                        if member.isMember {
                            memberContent(member)
                        } else {
                            guestForm(member)
                        }
                    }
                }
                .background((pageStyle == "minimal" ? Color.white : pageStyle == "ticket" ? Color(red: 0.98, green: 0.96, blue: 0.93) : AppDesign.canvas).ignoresSafeArea())
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .refreshable { await viewModel.load(force: true) }
                .overlay {
                    AppToastHost(message: Binding(get: { viewModel.profileSuccess }, set: { viewModel.profileSuccess = $0 }))
                }
                .navigationDestination(isPresented: $viewModel.showProfileDetails) {
                    if let member = viewModel.member {
                        ScrollView {
                            profileSummary(member)
                                .padding(16)
                        }
                        .background(AppDesign.canvas.ignoresSafeArea())
                        .headerBackDestination()
                        .navigationTitle("プロフィール")
                        .navigationBarTitleDisplayMode(.inline)
                        .toolbar(.visible, for: .navigationBar)
                    }
                }
                .navigationDestination(isPresented: Binding(
                    get: { viewModel.showProfileEdit },
                    set: { viewModel.showProfileEdit = $0 }
                )) {
                    ProfileEditView(
                        fields: viewModel.member?.editableProfileFields ?? [],
                        initialValues: viewModel.editPayload,
                        initialListValues: viewModel.editListPayload,
                        isSaving: viewModel.isSavingProfile,
                        errorMessage: viewModel.profileError,
                        serverFieldErrors: viewModel.profileFieldErrors,
                        onSave: { values, listValues in
                            viewModel.editPayload = values
                            viewModel.editListPayload = listValues
                            viewModel.editName = values["name"] ?? ""
                            viewModel.editPhone = values["phone"] ?? ""
                            Task {
                                await viewModel.saveProfile()
                                if viewModel.profileError == nil, let member = viewModel.member {
                                    onMembershipUpdated?(member)
                                }
                            }
                        }
                    )
                    .environment(\.appButtonColors, buttonColors)
                        .headerBackDestination()
                    .environment(\.brandAccent, accent)
                    .environment(\.brandForeground, brandForeground)
                }
                .sheet(isPresented: $viewModel.showBarcodeEnlarge) {
                    if let member = viewModel.member {
                        MemberCodeSheet(member: member, onClose: { viewModel.showBarcodeEnlarge = false })
                            .presentationDetents(PlatformRuntime.supportsMultipleModalDetents ? [.medium, .large] : [.large])
                            .presentationDragIndicator(.visible)
                    }
                }
                .alert("Wallet", isPresented: Binding(
                    get: { viewModel.walletMessage != nil },
                    set: { if !$0 { viewModel.walletMessage = nil } }
                )) {
                    Button("OK", role: .cancel) {}
                } message: {
                    Text(viewModel.walletMessage ?? "")
                }
                .alert("エラー", isPresented: Binding(
                    get: { viewModel.walletError != nil },
                    set: { if !$0 { viewModel.walletError = nil } }
                )) {
                    Button("OK", role: .cancel) {}
                } message: {
                    Text(viewModel.walletError ?? "")
                }
            }
        }
        .task(id: membershipRevision) {
            await viewModel.load(latestMember: initialMember)
            if opensProfileEditor && !didOpenProfileEditor && viewModel.member?.isMember == true {
                didOpenProfileEditor = true
                viewModel.openProfileEdit()
            }
        }
    }

    private func memberContent(_ member: MemberProfileSnapshot) -> some View {
        ScrollView {
            VStack(spacing: 24) {
                MembershipPassCard(
                    member: member,
                    projectName: projectName,
                    baseURL: viewModel.mediaBaseURL,
                    pageStyle: pageStyle,
                    onEnlargeCode: { viewModel.showBarcodeEnlarge = true }
                )

                if !member.editableProfileFields.isEmpty {
                    Button { viewModel.openProfileEdit() } label: {
                        HStack(spacing: 12) {
                            FontAwesomeIcon(systemName: "person.crop.circle")
                                .foregroundStyle(accent)
                                .frame(width: 36, height: 36)
                                .background(AppDesign.surfaceMuted)
                                .clipShape(Circle())
                            VStack(alignment: .leading, spacing: 3) {
                                Text("プロフィール編集")
                                    .font(.subheadline.weight(.semibold))
                                Text("登録情報を確認・編集")
                                    .font(.caption)
                                    .foregroundStyle(AppDesign.inkSecondary)
                            }
                            Spacer()
                            FontAwesomeIcon(systemName: "chevron.right")
                                .foregroundStyle(AppDesign.inkSecondary)
                        }
                        .foregroundStyle(AppDesign.ink)
                        .padding(.horizontal, 16)
                        .frame(maxWidth: .infinity, minHeight: 64)
                        .background(AppDesign.surface)
                        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                    }
                    .buttonStyle(.plain)
                }

                if viewModel.canAddToWallet {
                    VStack(spacing: 10) {
                        AppleWalletButton(enabled: !viewModel.isAddingWallet && !member.memberNumber.isEmpty) {
                            Task { await viewModel.addToAppleWallet() }
                        }
                        .fixedSize()
                        .frame(minHeight: 44)
                        if viewModel.isAddingWallet {
                            ProgressView("会員証を準備しています")
                                .font(.caption).tint(AppDesign.ink)
                        } else {
                            Text("会員証をWalletに保存して、すぐに提示。")
                                .font(.caption).foregroundStyle(AppDesign.inkSecondary)
                        }
                    }
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 8)
                }

                if showRankProgress, member.showRankProgress,
                   let points = member.pointsToNextRank,
                   let next = member.nextRankLabel,
                   !next.isEmpty {
                    VStack(alignment: .leading, spacing: 10) {
                        Text("次のランクまで")
                            .font(.caption)
                            .foregroundStyle(AppDesign.inkSecondary)
                        Text("あと \(points.formatted())ポイントで\(next)")
                            .font(.body.weight(.semibold))
                        GeometryReader { geometry in
                            ZStack(alignment: .leading) {
                                Capsule().fill(AppDesign.hairline)
                                Capsule().fill(accent)
                                    .frame(width: geometry.size.width * rankProgressValue(member: member, remaining: points))
                            }
                        }
                        .frame(height: 4)
                        .accessibilityLabel("次のランクまでの達成状況")
                        .accessibilityValue("\(Int(rankProgressValue(member: member, remaining: points) * 100))%")
                    }
                    .padding(.vertical, 8)
                }

                if showPointHistory {
                    pointHistorySection
                }

                if showCouponActivity {
                    couponActivitySection
                }

            }
            .padding(16)
        }
    }

    private var pointHistorySection: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("ポイント履歴").font(.headline)
            if viewModel.pointHistory.isEmpty {
                Text("ポイントの付与・利用履歴はまだありません")
                    .font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
            } else {
                ForEach(viewModel.pointHistory.prefix(10)) { item in
                    HStack(spacing: 12) {
                        VStack(alignment: .leading, spacing: 3) {
                            Text(item.description).font(.subheadline.weight(.medium))
                            Text(AppFormat.dateTime(item.createdAt)).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                        }
                        Spacer()
                        Text("\(item.points >= 0 ? "+" : "")\(item.points) pt")
                            .font(.subheadline.weight(.bold))
                            .foregroundStyle(item.points >= 0 ? accent : AppDesign.danger)
                    }
                    .padding(.vertical, 8)
                    Divider()
                }
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    private var couponActivitySection: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("最近獲得したクーポン").font(.headline)
            if viewModel.recentCoupons.isEmpty {
                Text("獲得したクーポンはまだありません")
                    .font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
            } else {
                ForEach(viewModel.recentCoupons.sorted { $0.issuedAt > $1.issuedAt }.prefix(5)) { coupon in
                    HStack {
                        VStack(alignment: .leading, spacing: 3) {
                            Text(coupon.name).font(.subheadline.weight(.semibold))
                            Text(coupon.issuedAt.isEmpty ? coupon.status : "獲得日 \(AppFormat.date(coupon.issuedAt))")
                                .font(.caption).foregroundStyle(AppDesign.inkSecondary)
                        }
                        Spacer()
                        FontAwesomeIcon(systemName: "ticket.fill").foregroundStyle(accent)
                    }
                    .padding(.vertical, 8)
                    Divider()
                }
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    private func profileSummary(_ member: MemberProfileSnapshot) -> some View {
        let visibleFields = member.editableProfileFields
        return Group {
            if !visibleFields.isEmpty {
                VStack(alignment: .leading, spacing: 16) {
                    AppSectionHeader(title: "登録情報", subtitle: "会員情報の確認と変更", actionTitle: "編集", action: viewModel.openProfileEdit)
                    VStack(spacing: 0) {
                        ForEach(Array(visibleFields.enumerated()), id: \.element.id) { index, field in
                            let value = member.profileValue(for: field)
                            VStack(alignment: .leading, spacing: 6) {
                                Text(field.label)
                                    .font(.caption)
                                    .foregroundStyle(AppDesign.inkSecondary)
                                Text(value.isEmpty ? "未登録" : value)
                                    .font(.body.weight(.medium))
                                    .foregroundStyle(value.isEmpty ? AppDesign.inkSecondary : AppDesign.ink)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                    .fixedSize(horizontal: false, vertical: true)
                            }
                            .padding(.horizontal, 16)
                            .padding(.vertical, 14)
                            if index < visibleFields.count - 1 { Divider() }
                        }
                    }
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
                }
            }
        }
    }

    private func rankProgressValue(member: MemberProfileSnapshot, remaining: Int) -> Double {
        min(max(Double(member.points) / Double(max(member.points + remaining, 1)), 0), 1)
    }

    private func guestForm(_ member: MemberProfileSnapshot) -> some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                Text(member.message ?? "登録するとポイントやクーポンが使えます。")
                    .foregroundStyle(.secondary)
                MemberFieldStack(
                    lastName: Binding(
                        get: { viewModel.registerLastName },
                        set: { viewModel.registerLastName = $0 }
                    ),
                    firstName: Binding(
                        get: { viewModel.registerFirstName },
                        set: { viewModel.registerFirstName = $0 }
                    ),
                    phone: Binding(
                        get: { viewModel.registerPhone },
                        set: { viewModel.registerPhone = $0 }
                    )
                )
                if let registerError = viewModel.registerError {
                    Text(registerError)
                        .font(.footnote)
                        .foregroundStyle(AppDesign.danger)
                }
                AppPrimaryButton(
                    title: "会員登録する",
                    isLoading: viewModel.isRegistering
                ) {
                    Task {
                        await viewModel.register()
                        if let member = viewModel.member, member.isMember {
                            onMembershipUpdated?(member)
                        }
                    }
                }
            }
            .padding(20)
        }
    }

    private func barcodeDialog(_ member: MemberProfileSnapshot) -> some View {
        VStack(spacing: 18) {
            HStack {
                Text("会員コード").font(.headline)
                Spacer()
                Button { viewModel.showBarcodeEnlarge = false } label: {
                    FontAwesomeIcon(systemName: "xmark.circle.fill", size: 22).foregroundStyle(AppDesign.inkSecondary)
                }
                .accessibilityLabel("閉じる")
            }
            MemberCodeView(value: member.memberNumber, codeType: member.cardCodeType, height: 120)
            Text(member.memberNumber)
                .font(.system(size: 18, weight: .bold, design: .monospaced))
                .tracking(2)
            Text("スタッフにこちらのコードをご提示ください")
                .font(.subheadline)
                .foregroundStyle(.secondary)
        }
        .padding(22)
        .frame(maxWidth: 430)
        .background(AppDesign.surface)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusXL, style: .continuous))
        .shadow(color: Color.black.opacity(0.24), radius: 24, x: 0, y: 12)
    }
}

struct MembershipPassCard: View {
    let member: MemberProfileSnapshot
    let projectName: String
    let baseURL: String
    var pageStyle: String = "classic"
    var onEnlargeCode: (() -> Void)? = nil
    @Environment(\.brandAccent) var accent

    private var cardColor: Color { member.rankColorHex?.toNativeColor(fallback: accent) ?? accent }
    private var cardForeground: Color { BrandPalette(hex: member.rankColorHex ?? "#173F35").usesDarkForeground ? .black : .white }

    @ViewBuilder private var imageHeader: some View {
        if let imageURL = member.cardLogoURL {
            RemoteImageView(urlString: imageURL, baseURL: baseURL, contentMode: .fill, usesDemoFallback: false)
                .frame(maxWidth: .infinity).frame(height: 110).clipped()
        }
    }

    private func codeSection(height: CGFloat) -> some View {
        Button { onEnlargeCode?() } label: {
            VStack(spacing: 10) {
                MemberCodeView(value: member.memberNumber, codeType: member.cardCodeType, height: height)
                Text(member.memberNumber).font(.system(.footnote, design: .monospaced).weight(.semibold)).tracking(1.5)
                Text("タップして拡大").font(.caption2).foregroundStyle(.secondary)
            }
            .padding(22).frame(maxWidth: .infinity).foregroundStyle(Color.black).background(Color.white)
        }
        .buttonStyle(.plain).accessibilityLabel("会員コードを拡大")
    }

    @ViewBuilder private var expiry: some View {
        if let value = member.pointsExpiryLabel, !value.isEmpty {
            Text("ポイント期限 " + value).font(.caption)
        }
    }

    private var classicCard: some View {
        VStack(spacing: 0) {
            imageHeader
            HStack(alignment: .top) {
                VStack(alignment: .leading, spacing: 6) {
                    Text(projectName)
                        .font(.subheadline.weight(.semibold))
                        .foregroundStyle(cardForeground.opacity(0.72))
                    Text("会員証")
                        .font(.title2.weight(.bold))
                        .foregroundStyle(cardForeground)
                    if !member.name.trimmed.isEmpty {
                        Text(member.name)
                            .font(.title3.weight(.semibold))
                            .foregroundStyle(cardForeground)
                    }
                }
                Spacer(minLength: 8)
                if !member.rankLabel.isEmpty {
                    Text(member.rankLabel)
                        .font(.caption.weight(.bold))
                        .padding(.horizontal, 10)
                        .padding(.vertical, 5)
                        .background(Color(red: 0.85, green: 0.96, blue: 0.56))
                        .clipShape(RoundedRectangle(cornerRadius: 7, style: .continuous))
                        .foregroundStyle(AppDesign.forest)
                }
            }
            .padding(.horizontal, 18)
            .padding(.top, 18)
            .padding(.bottom, 14)

            HStack(alignment: .firstTextBaseline, spacing: 6) {
                Text("\(member.points)")
                    .font(.system(size: 36, weight: .bold, design: .rounded))
                    .foregroundStyle(cardForeground)
                Text("pt")
                    .font(.title3.weight(.semibold))
                    .foregroundStyle(cardForeground.opacity(0.72))
                Spacer()
                if let expiry = member.pointsExpiryLabel, !expiry.isEmpty {
                    VStack(alignment: .trailing, spacing: 2) {
                        Text("ポイント期限")
                            .font(.caption2)
                            .foregroundStyle(cardForeground.opacity(0.72))
                        Text(expiry)
                            .font(.caption.weight(.semibold))
                            .foregroundStyle(cardForeground)
                    }
                }

            }
            .padding(.horizontal, 18)
            .padding(.bottom, 16)

            if !member.memberNumber.isEmpty {
                Button {
                    onEnlargeCode?()
                } label: {
                    VStack(spacing: 8) {
                        MemberCodeView(value: member.memberNumber, codeType: member.cardCodeType, height: 64)
                        Text(member.memberNumber)
                            .font(.system(.footnote, design: .monospaced).weight(.semibold))
                            .foregroundStyle(.primary)
                            .tracking(1.5)
                        Text("タップして拡大")
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                    }
                    .padding(.horizontal, 16)
                    .padding(.vertical, 16)
                    .frame(maxWidth: .infinity)
                    .background(AppDesign.surface)
                }
                .buttonStyle(.plain)
                .accessibilityLabel("会員コードを拡大")
            }
        }
        .background(
            cardColor
        )
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
        .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous).stroke(cardColor.opacity(0.35), lineWidth: 1))
        .shadow(color: cardColor.opacity(0.2), radius: 20, x: 0, y: 12)

    }

    var body: some View {
        if pageStyle == "classic" {
            classicCard
        } else if pageStyle == "minimal" {
            VStack(spacing: 0) {
                imageHeader
                VStack(spacing: 8) {
                    Text(projectName).font(.caption).foregroundStyle(.secondary)
                    Text("会員証").font(.title2.weight(.bold))
                    Text(member.name).font(.headline)
                }.padding(.top, 24)
                if !member.memberNumber.isEmpty { codeSection(height: 96) }
                HStack {
                    VStack(spacing: 6) {
                        Text("保有ポイント").font(.caption).foregroundStyle(.secondary)
                        Text("\(member.points) pt").font(.title2.weight(.bold))
                    }.frame(maxWidth: .infinity)
                    Divider().frame(height: 42)
                    VStack(spacing: 6) {
                        Text("会員ランク").font(.caption).foregroundStyle(.secondary)
                        Text(member.rankLabel).font(.headline)
                    }.frame(maxWidth: .infinity)
                }.padding(20).background(Color.black.opacity(0.025))
                expiry.padding(.bottom, 16)
            }
            .background(Color.white).clipShape(RoundedRectangle(cornerRadius: 16))
            .overlay(RoundedRectangle(cornerRadius: 16).stroke(Color.black.opacity(0.1), lineWidth: 1))
        } else if pageStyle == "ticket" {
            VStack(spacing: 0) {
                imageHeader
                HStack(spacing: 0) {
                    VStack(alignment: .leading, spacing: 20) {
                        Text("MEMBER PASS").font(.caption2.weight(.bold)).tracking(2)
                        Text(projectName).font(.title3.weight(.bold))
                        Text(member.rankLabel).font(.caption.weight(.bold))
                    }
                    .frame(maxWidth: .infinity, alignment: .leading).padding(20)
                    .frame(minHeight: 170).background(cardColor).foregroundStyle(cardForeground)
                    VStack(alignment: .leading, spacing: 12) {
                        Text("会員名").font(.caption).foregroundStyle(.secondary)
                        Text(member.name).font(.headline)
                        Text("\(member.points) pt").font(.title2.weight(.bold))
                        expiry.foregroundStyle(.secondary)
                    }.frame(maxWidth: .infinity, alignment: .leading).padding(18)
                }
                if !member.memberNumber.isEmpty {
                    codeSection(height: 64).overlay(alignment: .top) {
                        Rectangle().stroke(Color.black.opacity(0.2), style: StrokeStyle(lineWidth: 1, dash: [5, 4])).frame(height: 1)
                    }
                }
            }
            .background(Color.white).clipShape(RoundedRectangle(cornerRadius: 12))
            .shadow(color: Color.black.opacity(0.05), radius: 12, x: 0, y: 4)
        } else {
            VStack(spacing: 16) {
                VStack(spacing: 0) {
                    imageHeader
                    VStack(alignment: .leading, spacing: 16) {
                        HStack {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(projectName).font(.caption)
                                Text(member.name).font(.headline)
                            }
                            Spacer()
                            Text(member.rankLabel).font(.caption.weight(.bold)).padding(8)
                                .background(cardForeground.opacity(0.12)).clipShape(Capsule())
                        }
                        Text("保有ポイント").font(.subheadline)
                        HStack(alignment: .firstTextBaseline, spacing: 8) {
                            Text("\(member.points)").font(.system(size: 64, weight: .bold, design: .rounded))
                            Text("pt").font(.title3)
                        }
                        expiry
                    }.padding(24).frame(maxWidth: .infinity, alignment: .leading)
                }
                .foregroundStyle(cardForeground).background(cardColor)
                .clipShape(RoundedRectangle(cornerRadius: 24))
                if !member.memberNumber.isEmpty {
                    VStack(alignment: .leading, spacing: 0) {
                        Text("会員コード").font(.headline).padding(.horizontal, 22).padding(.top, 18)
                        codeSection(height: 64)
                    }.background(Color.white).clipShape(RoundedRectangle(cornerRadius: 20))
                }
            }
        }
    }
}

struct ProfileEditView: View {
    let fields: [ProfileFieldDefinition]
    let initialValues: [String: String]
    let initialListValues: [String: [String]]
    let isSaving: Bool
    let errorMessage: String?
    let serverFieldErrors: [String: String]
    let onSave: ([String: String], [String: [String]]) -> Void
    var isRegistration = false
    var introduction: String? = nil
    var onSkip: (() -> Void)? = nil

    @State var values: [String: String] = [:]
    @State var listValues: [String: [String]] = [:]
    @State var didInitialize = false
    @Environment(\.brandAccent) var accent

    @Environment(\.appButtonColors) var buttonColors
    @Environment(\.brandForeground) var foreground
    @State var attemptedSave = false
    @Environment(\.dismiss) var dismiss

    private var groups: [String] { ["基本情報", "住所", "その他の情報"] }

    private func group(for field: ProfileFieldDefinition) -> String {
        if ["postal_code", "address", "address1", "address2", "prefecture", "city"].contains(field.key) { return "住所" }
        if ["name", "kana", "name_kana", "phone", "email", "birthday", "birth_date", "sex"].contains(field.key) { return "基本情報" }
        return "その他の情報"
    }

    var body: some View {
        VStack(spacing: 0) {
            Text(isRegistration ? "会員登録" : "プロフィール編集")
                .font(.headline)
                .frame(maxWidth: .infinity, minHeight: 64)
                .accessibilityAddTraits(.isHeader)
            ScrollView {
                VStack(alignment: .leading, spacing: 24) {
                    Text(introduction ?? "連絡先や登録情報を変更できます。必須項目を入力して保存してください。")
                        .font(.subheadline)
                        .foregroundStyle(AppDesign.inkSecondary)
                    ForEach(groups, id: \.self) { title in
                        let groupFields = fields.filter { group(for: $0) == title }
                        if !groupFields.isEmpty {
                            VStack(alignment: .leading, spacing: 10) {
                                Text(title).font(.subheadline.weight(.semibold)).foregroundStyle(AppDesign.inkSecondary)
                                VStack(spacing: 0) {
                                    ForEach(Array(groupFields.enumerated()), id: \.element.id) { index, field in
                                        VStack(alignment: .leading, spacing: 8) {
                                            fieldLabel(field)
                                            fieldEditor(field)
                                            if let description = field.description, !description.isEmpty {
                                                Text(description).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                                            }
                                            if let message = validationMessage(for: field) {
                                                FontAwesomeLabel(message, systemImage: "exclamationmark.circle.fill")
                                                    .font(.caption).foregroundStyle(AppDesign.danger)
                                            }
                                        }
                                        .padding(16)
                                        if index < groupFields.count - 1 {
                                            Divider().padding(.horizontal, 16)
                                        }
                                    }
                                }
                                .background(AppDesign.surface)
                                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
                            }
                        }
                    }
                    if let errorMessage, !errorMessage.isEmpty {
                        FontAwesomeLabel(errorMessage, systemImage: "exclamationmark.triangle.fill")
                            .foregroundStyle(AppDesign.danger).font(.footnote)
                    }
                }
                .padding(16)
                #if os(Android)
                .padding(.bottom, onSkip == nil ? 90 : 130)
                #endif
            }
        }
        .foregroundStyle(AppDesign.ink)
        .scrollDismissesKeyboard(.interactively)
        .disabled(isSaving)
        .background(AppDesign.canvas.ignoresSafeArea())
        .navigationBarBackButtonHidden(true)
        .toolbar(.hidden, for: .navigationBar)
        .tint(accent)
        #if !os(Android)
        .safeAreaInset(edge: .bottom) {
            VStack(spacing: 8) {
                if attemptedSave && !isValid {
                    Text("入力内容をご確認ください").font(.caption).foregroundStyle(AppDesign.danger)
                }
                Button {
                    attemptedSave = true
                    if isValid { onSave(values, listValues) }
                } label: {
                    HStack(spacing: 8) {
                        if isSaving { ProgressView().tint((buttonColors["primary_button_text"] ?? "").toNativeColor(fallback: foreground)) }
                        else { FontAwesomeIcon(systemName: "checkmark") }
                        Text(isSaving ? (isRegistration ? "登録中…" : "保存中…") : (isRegistration ? "登録してはじめる" : "変更を保存")).fontWeight(.semibold)
                    }
                    .frame(maxWidth: .infinity, minHeight: 50)
                    .foregroundStyle((buttonColors["primary_button_text"] ?? "").toNativeColor(fallback: foreground))
                    .background((buttonColors["primary_button_background"] ?? "").toNativeColor(fallback: accent))
                    .clipShape(RoundedRectangle(cornerRadius: 14))
                }
                .buttonStyle(.plain)
                .disabled(isSaving || fields.isEmpty)
                if let onSkip {
                    Button("あとで登録する", action: onSkip).disabled(isSaving)
                }
            }
            .padding(.horizontal, 16).padding(.vertical, 12)
            .background(AppDesign.surface)
        }
        #endif
        .overlay(alignment: .bottom) {
            #if os(Android)
            VStack(spacing: 8) {
                if attemptedSave && !isValid {
                    Text("入力内容をご確認ください").font(.caption).foregroundStyle(AppDesign.danger)
                }
                Button {
                    attemptedSave = true
                    if isValid { onSave(values, listValues) }
                } label: {
                    HStack(spacing: 8) {
                        if isSaving { ProgressView().tint((buttonColors["primary_button_text"] ?? "").toNativeColor(fallback: foreground)) }
                        else { FontAwesomeIcon(systemName: "checkmark") }
                        Text(isSaving ? (isRegistration ? "登録中…" : "保存中…") : (isRegistration ? "登録してはじめる" : "変更を保存")).fontWeight(.semibold)
                    }
                    .frame(maxWidth: .infinity, minHeight: 50)
                    .foregroundStyle((buttonColors["primary_button_text"] ?? "").toNativeColor(fallback: foreground))
                    .background((buttonColors["primary_button_background"] ?? "").toNativeColor(fallback: accent))
                    .clipShape(RoundedRectangle(cornerRadius: 14))
                }
                .buttonStyle(.plain)
                .disabled(isSaving || fields.isEmpty)
                if let onSkip {
                    Button("あとで登録する", action: onSkip).disabled(isSaving)
                }
            }
            .padding(.horizontal, 16).padding(.vertical, 12)
            .background(AppDesign.surface)
            #endif
        }
        .onAppear {
            guard !didInitialize else { return }
            values = initialValues
            listValues = initialListValues
            for field in fields where field.inputType == "checkbox" {
                if listValues[field.key] == nil { listValues[field.key] = [] }
            }
            splitStoredNames()
            didInitialize = true
        }
    }

    private var isValid: Bool {
        fields.allSatisfy { $0.validationMessage(value: values[$0.key] ?? "", selections: listValues[$0.key] ?? []) == nil }
    }

    private func validationMessage(for field: ProfileFieldDefinition) -> String? {
        if let message = serverFieldErrors[field.key] { return message }
        guard attemptedSave else { return nil }
        if isSplitName(field.key), field.required {
            let last = (values["\(field.key)_last"] ?? "").trimmed
            let first = (values["\(field.key)_first"] ?? "").trimmed
            if last.isEmpty || first.isEmpty {
                return "\(field.label)は姓と名の両方を入力してください"
            }
        }
        return field.validationMessage(value: values[field.key] ?? "", selections: listValues[field.key] ?? [])
    }

    private func isSplitName(_ key: String) -> Bool {
        key == "name" || key == "kana" || key == "name_kana"
    }

    private func splitStoredNames() {
        for field in fields where isSplitName(field.key) {
            let stored = (values[field.key] ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
            guard let space = stored.firstIndex(where: { $0.isWhitespace }) else {
                values["\(field.key)_last"] = stored
                values["\(field.key)_first"] = ""
                continue
            }
            values["\(field.key)_last"] = String(stored[..<space])
            values["\(field.key)_first"] = String(stored[stored.index(after: space)...]).trimmingCharacters(in: .whitespacesAndNewlines)
        }
    }

    private func splitNameField(_ field: ProfileFieldDefinition, part: String, title: String, placeholder: String) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(title).font(.caption).foregroundStyle(AppDesign.inkSecondary)
            TextField(placeholder, text: namePartBinding(field, part: part))
                .accessibilityLabel(Text("\(field.label)の\(title)"))
                .padding(.horizontal, 12)
                .frame(height: 46)
                .background(AppDesign.surfaceMuted)
                .clipShape(RoundedRectangle(cornerRadius: 10))
                .overlay { RoundedRectangle(cornerRadius: 10).stroke(AppDesign.hairline, lineWidth: 1) }
                #if !os(macOS)
                .textInputAutocapitalization(.never)
                #endif
        }
    }

    private func namePartBinding(_ field: ProfileFieldDefinition, part: String) -> Binding<String> {
        let partKey = "\(field.key)_\(part)"
        return Binding(
            get: { values[partKey] ?? "" },
            set: { newValue in
                values[partKey] = newValue
                let last = (part == "last" ? newValue : (values["\(field.key)_last"] ?? "")).trimmed
                let first = (part == "first" ? newValue : (values["\(field.key)_first"] ?? "")).trimmed
                values[field.key] = [last, first].filter { !$0.isEmpty }.joined(separator: " ")
            }
        )
    }

    private func textBinding(_ key: String) -> Binding<String> {
        Binding(get: { values[key] ?? "" }, set: { values[key] = $0 })
    }

    @ViewBuilder
    private func fieldEditor(_ field: ProfileFieldDefinition) -> some View {
        if field.inputType == "checkbox" {
            ForEach(field.choices, id: \.self) { choice in
                Button {
                    var selected = listValues[field.key] ?? []
                    if selected.contains(choice) { selected.removeAll { $0 == choice } }
                    else { selected.append(choice) }
                    listValues[field.key] = selected
                } label: {
                    HStack {
                        FontAwesomeIcon(systemName: (listValues[field.key] ?? []).contains(choice) ? "square.checkmark" : "square.empty")
                            .foregroundStyle(accent)
                        Text(field.displayValue(choice)).foregroundStyle(AppDesign.ink)
                        Spacer()
                    }.frame(minHeight: 44)
                }
                .buttonStyle(.plain)
                .accessibilityValue((listValues[field.key] ?? []).contains(choice) ? "選択済み" : "未選択")
            }
        } else if !field.choices.isEmpty || field.inputType == "selectbox" || field.inputType == "select" {
            Picker(field.label, selection: textBinding(field.key)) {
                Text("選択してください").tag("")
                ForEach(field.choices, id: \.self) { Text(field.displayValue($0)).tag($0) }
            }
            .labelsHidden()
            .pickerStyle(.menu)
            .frame(maxWidth: .infinity, minHeight: 46, alignment: .leading)
            .padding(.horizontal, 12)
            .background(AppDesign.surfaceMuted)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusS))
        } else if field.inputType == "date" {
            Toggle("登録する", isOn: Binding(
                get: { !(values[field.key] ?? "").isEmpty },
                set: { values[field.key] = $0 ? dateString(Date()) : "" }
            ))
            if !(values[field.key] ?? "").isEmpty {
                DatePicker(field.label, selection: Binding(
                    get: { parsedDate(values[field.key] ?? "") },
                    set: { values[field.key] = dateString($0) }
                ), displayedComponents: [.date])
                .labelsHidden()
            }
        } else if isSplitName(field.key) {
            HStack(alignment: .top, spacing: 8) {
                splitNameField(field, part: "last", title: field.key == "name" ? "姓" : "セイ", placeholder: field.key == "name" ? "山田" : "ヤマダ")
                splitNameField(field, part: "first", title: field.key == "name" ? "名" : "メイ", placeholder: field.key == "name" ? "太郎" : "タロウ")
            }
        } else if field.inputType == "textarea" {
            TextEditor(text: textBinding(field.key))
                .frame(minHeight: 76)
                .padding(10)
                .background(AppDesign.surfaceMuted)
                .clipShape(RoundedRectangle(cornerRadius: 10))
                .overlay { RoundedRectangle(cornerRadius: 10).stroke(AppDesign.hairline, lineWidth: 1) }
                .scrollContentBackground(.hidden)
                .accessibilityLabel(Text(field.label))
        } else {
            TextField(placeholder(for: field), text: textBinding(field.key))
                .accessibilityLabel(Text(field.label))
                .padding(.horizontal, 12)
                .frame(height: 46)
                .background(AppDesign.surfaceMuted)
                .clipShape(RoundedRectangle(cornerRadius: 10))
                .overlay { RoundedRectangle(cornerRadius: 10).stroke(AppDesign.hairline, lineWidth: 1) }
                .autocorrectionDisabled(field.key == "email" || field.key == "phone")
                #if !os(macOS)
                .keyboardType(field.key == "phone" || field.inputType == "tel" ? .phonePad : field.key == "email" || field.inputType == "email" ? .emailAddress : field.key == "postal_code" ? .numberPad : .default)
                .textInputAutocapitalization(.never)
                #endif
        }
    }

    private func placeholder(for field: ProfileFieldDefinition) -> String {
        switch field.key {
        case "name": return "例：山田 太郎"
        case "kana", "name_kana": return "例：ヤマダ タロウ"
        case "phone": return "例：09012345678"
        case "email": return "例：name@example.com"
        case "postal_code": return "例：1234567"
        default: return field.label
        }
    }

    private func dateString(_ date: Date) -> String {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter.string(from: date)
    }

    private func parsedDate(_ value: String) -> Date {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter.date(from: String(value.prefix(10))) ?? Date()
    }

    private func fieldLabel(_ field: ProfileFieldDefinition) -> some View {
        HStack(spacing: 8) {
            Text(field.label).font(.subheadline.weight(.semibold))
            if field.required {
                Text("必須").font(.caption2.weight(.semibold)).foregroundStyle(AppDesign.danger)
                    .padding(.horizontal, 6).padding(.vertical, 3)
                    .background(AppDesign.canvas).clipShape(Capsule())
            }
        }
    }
}
