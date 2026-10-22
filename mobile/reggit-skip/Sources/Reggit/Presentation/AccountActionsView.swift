import SwiftUI
#if !os(Android)
import UIKit
#endif

@MainActor
@Observable
final class AccountActionsModel {
    var mode = "transfer"
    var memberId = ""
    var code = ""
    var issuedCode: TransferCode?
    var message: String?
    var errorMessage: String?
    var isBusy = false
    var busyAction = ""

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    func withdraw() async -> Bool {
        isBusy = true
        busyAction = "withdraw"
        errorMessage = nil
        defer { isBusy = false; busyAction = "" }
        do {
            try await apiClient.withdrawMember()
            return true
        } catch {
            errorMessage = error.localizedDescription
            return false
        }
    }

    func issue() async {
        isBusy = true
        busyAction = "issue"
        errorMessage = nil
        message = nil
        defer { isBusy = false; busyAction = "" }
        do {
            issuedCode = try await apiClient.issueTransferCode()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func claim() async -> Bool {
        let normalized = code.trimmingCharacters(in: .whitespacesAndNewlines)
        let id = Int(memberId.trimmingCharacters(in: .whitespacesAndNewlines)) ?? 0
        guard id > 0, !normalized.isEmpty else {
            errorMessage = "会員IDと引き継ぎコードを入力してください"
            return false
        }
        isBusy = true
        busyAction = "claim"
        errorMessage = nil
        defer { isBusy = false; busyAction = "" }
        do {
            try await apiClient.claimTransferCode(memberId: id, code: normalized)
            message = "会員を引き継ぎました"
            return true
        } catch {
            errorMessage = error.localizedDescription
            return false
        }
    }
}

struct AccountActionsView: View {
    let isMember: Bool
    var embedded = false
    var initialMode = "transfer"
    let onWithdrawn: () -> Void
    let onTransferred: () -> Void
    let onClose: () -> Void
    @State var model: AccountActionsModel
    @State var transferTab = 0
    @State var showsIssuedQR = false
    @State var showsScanner = false
    @Environment(\.brandAccent) var accent

    init(
        apiClient: any ReggitAPIServing,
        isMember: Bool,
        embedded: Bool = false,
        initialMode: String = "transfer",
        onWithdrawn: @escaping () -> Void,
        onTransferred: @escaping () -> Void,
        onClose: @escaping () -> Void
    ) {
        self.isMember = isMember
        self.embedded = embedded
        self.initialMode = initialMode
        self.onWithdrawn = onWithdrawn
        self.onTransferred = onTransferred
        self.onClose = onClose
        let model = AccountActionsModel(apiClient: apiClient)
        model.mode = initialMode
        _model = State(initialValue: model)
        // 未会員は発行できないので、受け取り側（引き継ぐ）を最初に出す
        _transferTab = State(initialValue: isMember ? 0 : 1)
    }

    var body: some View {
        Group {
            if embedded {
                form.padding(16).background(AppDesign.surface).clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
            } else {
                ScrollView { form.padding(16) }
                    .background(AppDesign.canvas.ignoresSafeArea())
                    .navigationTitle(model.mode == "withdraw" ? "退会" : "引き継ぎ")
                    .navigationBarTitleDisplayMode(.inline)
                    .toolbar(.visible, for: .navigationBar)
                    .toolbar {
                        ToolbarItem(placement: .primaryAction) {
                            Button("閉じる", action: onClose)
                        }
                    }
            }
        }
    }

    private var form: some View {
        VStack(alignment: .leading, spacing: 16) {
                    if model.mode == "withdraw" {
                        withdrawSection
                    } else {
                        transferSection
                    }
                    if let errorMessage = model.errorMessage {
                        Text(errorMessage).font(.footnote).foregroundStyle(AppDesign.danger)
                    }
                    if let message = model.message {
                        Text(message).font(.footnote).foregroundStyle(accent)
                    }
        }
    }

    private var withdrawSection: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("退会すると会員情報・ポイント・スタンプは復元できません。")
                .font(.subheadline)
                .foregroundStyle(AppDesign.inkSecondary)
            Button("退会する") {
                Task {
                    if await model.withdraw() { onWithdrawn() }
                }
            }
            .buttonStyle(.borderedProminent)
            .tint(AppDesign.danger)
            .disabled(model.isBusy)
        }
    }

    private var transferSection: some View {
        VStack(alignment: .leading, spacing: 20) {
            AppSegmentedControl(titles: ["IDを発行", "引き継ぐ"], selectedIndex: $transferTab)
            if transferTab == 0 {
                issueSection
            } else {
                claimSection
            }
        }
    }

    private var issueSection: some View {
        VStack(alignment: .leading, spacing: 16) {
            if !isMember {
                Text("会員登録後に、この端末の会員IDを発行できます。")
                    .font(.subheadline)
                    .foregroundStyle(AppDesign.inkSecondary)
            } else {
                Text("新しい端末で読み取るか、会員IDとコードを入力します。コードは30分で一度だけ使えます。")
                    .font(.subheadline)
                    .foregroundStyle(AppDesign.inkSecondary)
                AppPrimaryButton(title: "IDとコードを発行", isLoading: model.busyAction == "issue", enabled: !model.isBusy) {
                    showsIssuedQR = false
                    Task { await model.issue() }
                }
                if let issued = model.issuedCode {
                    copyRow(title: "会員ID", value: String(issued.memberId))
                    copyRow(title: "引き継ぎコード", value: issued.code)
                    Text("有効期限 \(issued.expiresAt)")
                        .font(.footnote)
                        .foregroundStyle(AppDesign.inkSecondary)
                    Button(showsIssuedQR ? "QRを隠す" : "QRを表示") {
                        showsIssuedQR.toggle()
                    }
                    .buttonStyle(.bordered)
                    if showsIssuedQR {
                        QRCodeView(value: "\(issued.memberId):\(issued.code)")
                            .frame(maxWidth: 240)
                            .frame(maxWidth: .infinity)
                            .padding(12)
                            .background(Color.white)
                            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                    }
                }
            }
        }
    }

    private var claimSection: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("渡す側の会員IDと引き継ぎコードを入力してください。")
                .font(.subheadline)
                .foregroundStyle(AppDesign.inkSecondary)
            #if !SKIP && os(iOS)
            Button("QRを読み取る") { showsScanner = true }
                .buttonStyle(.bordered)
                .sheet(isPresented: $showsScanner) {
                    NavigationStack {
                        TransferQRScanner { raw in
                            applyScannedCode(raw)
                        }
                        .ignoresSafeArea()
                        .navigationTitle("QRを読み取る")
                        .navigationBarTitleDisplayMode(.inline)
                        .toolbar {
                            ToolbarItem(placement: .cancellationAction) {
                                Button("閉じる") { showsScanner = false }
                            }
                        }
                    }
                }
            #endif
            labeledField(title: "会員ID", text: $model.memberId, keyboard: .numberPad)
            labeledField(title: "引き継ぎコード", text: $model.code, autocapitalize: true)
            AppPrimaryButton(title: "この端末へ引き継ぐ", isLoading: model.busyAction == "claim", enabled: !model.isBusy) {
                Task {
                    if await model.claim() { onTransferred() }
                }
            }
        }
    }

    private func applyScannedCode(_ raw: String) {
        let parts = raw.split(separator: ":", maxSplits: 1, omittingEmptySubsequences: false).map(String.init)
        let memberId = parts.count == 2 ? Int(parts[0].trimmingCharacters(in: .whitespacesAndNewlines)) ?? 0 : 0
        let code = parts.count == 2 ? parts[1].trimmingCharacters(in: .whitespacesAndNewlines) : ""
        guard memberId > 0, !code.isEmpty else {
            model.errorMessage = "引き継ぎ用のQRではありません"
            showsScanner = false
            return
        }
        model.memberId = String(memberId)
        model.code = code
        model.errorMessage = nil
        showsScanner = false
    }

    private func copyRow(title: String, value: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption.weight(.semibold))
                .foregroundStyle(AppDesign.inkSecondary)
            HStack(spacing: 12) {
                Text(value)
                    .font(.title3.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                    #if !os(Android)
                    .textSelection(.enabled)
                    #endif
                    .frame(maxWidth: .infinity, alignment: .leading)
                #if !os(Android)
                Button {
                    UIPasteboard.general.string = value
                    model.message = "\(title)をコピーしました"
                    model.errorMessage = nil
                } label: {
                    FontAwesomeLabel("コピー", systemImage: "doc.on.doc")
                        .font(.subheadline.weight(.semibold))
                }
                .buttonStyle(.bordered)
                #endif
            }
            .padding(14)
            .background(AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                    .stroke(AppDesign.hairline, lineWidth: 1)
            }
        }
    }

    private func labeledField(title: String, text: Binding<String>, keyboard: UIKeyboardType = .default, autocapitalize: Bool = false) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption.weight(.semibold))
                .foregroundStyle(AppDesign.inkSecondary)
            TextField(title, text: text)
                .textFieldStyle(.roundedBorder)
                .keyboardType(keyboard)
                .textInputAutocapitalization(autocapitalize ? .characters : .never)
        }
    }
}
