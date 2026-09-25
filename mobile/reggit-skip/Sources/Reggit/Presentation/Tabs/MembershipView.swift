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
    var registerName = ""
    var registerPhone = ""
    var isRegistering = false
    var registerError: String?
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

    func load() async {
        if member?.isMember != true { isLoading = true }
        errorMessage = nil
        defer { isLoading = false }
        do {
            var snapshot = try await apiClient.fetchMemberProfile()
            if snapshot.isMember, snapshot.memberNumber.isEmpty,
               let card = try? await apiClient.fetchPointCard() {
                snapshot = snapshot.withCard(card)
            }
            member = snapshot
            if snapshot.isMember {
                if showPointHistory { pointHistory = (try? await apiClient.fetchPointHistory()) ?? [] }
                if showCouponActivity { recentCoupons = (try? await apiClient.fetchMemberCoupons()) ?? [] }
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func register() async {
        let name = registerName.trimmed
        let phone = registerPhone.trimmed
        guard !name.isEmpty, !phone.isEmpty else {
            registerError = "氏名と電話番号を入力してください"
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
            NativeWalletBridge.addAppleWalletPass?(try await apiClient.fetchWalletPassDownloadURL())
            walletMessage = "Apple Wallet に追加します"
        } catch {
            walletError = error.localizedDescription
        }
    }
}

struct MembershipView: View {
    @State var viewModel: MembershipViewModel
    let projectName: String
    let membershipRevision: Int
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
        self.projectName = projectName
        self.membershipRevision = membershipRevision
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
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .refreshable { await viewModel.load() }
                .overlay {
                    AppToastHost(message: Binding(get: { viewModel.profileSuccess }, set: { viewModel.profileSuccess = $0 }))
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
                }
                .navigationDestination(isPresented: $viewModel.showBarcodeEnlarge) {
                    if let member = viewModel.member {
                        barcodeDialog(member)
                            .padding(24)
                            .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
                            .background(AppDesign.canvas.ignoresSafeArea())
                            .navigationTitle("会員コード")
                            .navigationBarTitleDisplayMode(.inline)
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
            await viewModel.load()
        }
    }

    private func memberContent(_ member: MemberProfileSnapshot) -> some View {
        ScrollView {
            VStack(spacing: 24) {
                MembershipPassCard(
                    member: member,
                    projectName: projectName,
                    baseURL: viewModel.mediaBaseURL,
                    onEnlargeCode: { viewModel.showBarcodeEnlarge = true }
                )

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

                profileSummary(member)

                if showRankProgress, member.showRankProgress,
                   let points = member.pointsToNextRank,
                   let next = member.nextRankLabel,
                   !next.isEmpty {
                    VStack(alignment: .leading, spacing: 10) {
                        Text("次のランクまで")
                            .font(.caption)
                            .foregroundStyle(AppDesign.inkSecondary)
                        Text("あと \(points)ポイントで\(next)")
                            .font(.body.weight(.semibold))
                        ProgressView(value: rankProgressValue(member: member, remaining: points))
                            .tint(accent)
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
                VStack(alignment: .leading, spacing: 10) {
                    AppSectionHeader(title: "プロフィール情報", actionTitle: "編集", action: viewModel.openProfileEdit)
                    VStack(spacing: 0) {
                        ForEach(Array(visibleFields.enumerated()), id: \.element.id) { index, field in
                            let value = member.profileValue(for: field)
                            VStack(alignment: .leading, spacing: 5) {
                                Text(field.label)
                                    .font(.caption)
                                    .foregroundStyle(AppDesign.inkSecondary)
                                Text(value)
                                    .font(.body.weight(.medium))
                                    .foregroundStyle(AppDesign.ink)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                    .fixedSize(horizontal: false, vertical: true)
                            }
                            .padding(.vertical, 12)
                            if index < visibleFields.count - 1 { Divider() }
                        }
                    }
                }
            }
        }
    }

    private func rankProgressValue(member: MemberProfileSnapshot, remaining: Int) -> Double {
        Double(member.points) / Double(max(member.points + remaining, 1))
    }

    private func guestForm(_ member: MemberProfileSnapshot) -> some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                Text(member.message ?? "登録するとポイントやクーポンが使えます。")
                    .foregroundStyle(.secondary)
                MemberFieldStack(
                    name: Binding(
                        get: { viewModel.registerName },
                        set: { viewModel.registerName = $0 }
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
    var onEnlargeCode: (() -> Void)? = nil
    @Environment(\.brandAccent) var accent

    private var cardColor: Color {
        member.rankColorHex?.toNativeColor(fallback: accent) ?? accent
    }

    private var cardForeground: Color {
        BrandPalette(hex: member.rankColorHex ?? "#173F35").usesDarkForeground ? .black : .white
    }

    var body: some View {
        VStack(spacing: 0) {
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
                if let imageURL = member.cardLogoURL {
                    RemoteImageView(
                        urlString: imageURL,
                        baseURL: baseURL,
                        contentMode: .fill,
                        cornerRadius: 20,
                        usesDemoFallback: false
                    )
                    .frame(width: 40, height: 40)
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
}

struct ProfileEditView: View {
    let fields: [ProfileFieldDefinition]
    let initialValues: [String: String]
    let initialListValues: [String: [String]]
    let isSaving: Bool
    let errorMessage: String?
    let serverFieldErrors: [String: String]
    let onSave: ([String: String], [String: [String]]) -> Void

    @State var values: [String: String] = [:]
    @State var listValues: [String: [String]] = [:]
    @State var didInitialize = false
    @Environment(\.brandAccent) var accent

    var body: some View {
        ScrollView {
            LazyVStack(alignment: .leading, spacing: 12) {
                ForEach(fields) { field in
                    VStack(alignment: .leading, spacing: 7) {
                        fieldLabel(field)
                        fieldEditor(field)
                        if let description = field.description {
                            Text(description).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                        }
                        if let message = validationMessage(for: field) {
                            Label(message, systemImage: "exclamationmark.circle.fill")
                                .font(.caption)
                                .foregroundStyle(AppDesign.danger)
                        }
                    }
                    .padding(14)
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                    .overlay {
                        RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                            .stroke(validationMessage(for: field) == nil ? AppDesign.hairline : AppDesign.danger, lineWidth: 1)
                    }
                }
                if let errorMessage, !errorMessage.isEmpty {
                    Label(errorMessage, systemImage: "exclamationmark.triangle.fill")
                        .foregroundStyle(AppDesign.danger).font(.footnote)
                }
            }
            .padding(16)
        }
        .disabled(isSaving)
        .scrollContentBackground(.hidden)
        .background(AppDesign.canvas)
        .navigationTitle("")
        .tint(accent)
        .toolbar {
            ToolbarItem(placement: .primaryAction) {
                Button {
                    onSave(values, listValues)
                } label: {
                    if isSaving {
                        ProgressView()
                    } else {
                        Text("保存").fontWeight(.semibold)
                    }
                }
                .disabled(!isValid || isSaving)
            }
        }
        .onAppear {
            guard !didInitialize else { return }
            values = initialValues
            listValues = initialListValues
            for field in fields where field.inputType == "checkbox" {
                if listValues[field.key] == nil { listValues[field.key] = [] }
            }
            didInitialize = true
        }
    }

    private var isValid: Bool {
        fields.allSatisfy { $0.validationMessage(value: values[$0.key] ?? "", selections: listValues[$0.key] ?? []) == nil }
    }

    private func validationMessage(for field: ProfileFieldDefinition) -> String? {
        field.validationMessage(value: values[field.key] ?? "", selections: listValues[field.key] ?? [])
            ?? serverFieldErrors[field.key]
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
                        FontAwesomeIcon(systemName: (listValues[field.key] ?? []).contains(choice) ? "checkmark.circle.fill" : "circle")
                            .foregroundStyle(accent)
                        Text(field.displayValue(choice)).foregroundStyle(AppDesign.ink)
                        Spacer()
                    }.frame(minHeight: 44)
                }
                .buttonStyle(.plain)
                .accessibilityValue((listValues[field.key] ?? []).contains(choice) ? "選択済み" : "未選択")
            }
        } else if !field.choices.isEmpty {
            Picker(field.label, selection: textBinding(field.key)) {
                Text("選択してください").tag("")
                ForEach(field.choices, id: \.self) { Text(field.displayValue($0)).tag($0) }
            }
            .labelsHidden()
            .pickerStyle(.menu)
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
        } else if field.inputType == "textarea" {
            TextEditor(text: textBinding(field.key))
                .frame(minHeight: 76)
                .padding(10)
                .background(AppDesign.surfaceMuted)
                .clipShape(RoundedRectangle(cornerRadius: 10))
                .accessibilityLabel(Text(field.label))
        } else {
            TextField(field.label, text: textBinding(field.key))
                .padding(.horizontal, 12)
                .frame(minHeight: 46)
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
                Text("必須").font(.caption2).foregroundStyle(AppDesign.inkSecondary)
                    .padding(.horizontal, 6).padding(.vertical, 3)
                    .background(AppDesign.canvas).clipShape(Capsule())
            }
        }
    }
}
