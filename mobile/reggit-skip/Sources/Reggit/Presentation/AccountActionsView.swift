import SwiftUI
import UIKit

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
    }

    var body: some View {
        Group {
            if embedded {
                form.padding(16).background(AppDesign.surface).clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
            } else {
                NavigationStack {
                    ScrollView { form.padding(16) }
                        .background(AppDesign.canvas.ignoresSafeArea())
                        .navigationTitle(model.mode == "withdraw" ? "退会" : "引き継ぎ")
                        .toolbar {
                            ToolbarItem(placement: .primaryAction) {
                                Button("閉じる", action: onClose)
                            }
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
        VStack(alignment: .leading, spacing: 24) {
            if isMember {
                sectionTitle("この端末から渡す")
                Text("新しい端末で、会員IDとコードの両方が必要です。コードは30分で一度だけ使えます。")
                    .font(.subheadline)
                    .foregroundStyle(AppDesign.inkSecondary)
                AppPrimaryButton(title: "IDとコードを発行", isLoading: model.busyAction == "issue", enabled: !model.isBusy) {
                    Task { await model.issue() }
                }
                if let issued = model.issuedCode {
                    copyRow(title: "会員ID", value: String(issued.memberId))
                    copyRow(title: "引き継ぎコード", value: issued.code)
                    Text("有効期限 \(issued.expiresAt)")
                        .font(.footnote)
                        .foregroundStyle(AppDesign.inkSecondary)
                }
            }
            sectionTitle("この端末で受け取る")
            labeledField(title: "会員ID", text: $model.memberId, keyboard: .numberPad)
            labeledField(title: "引き継ぎコード", text: $model.code, autocapitalize: true)
            AppPrimaryButton(title: "この端末へ引き継ぐ", isLoading: model.busyAction == "claim", enabled: !model.isBusy) {
                Task {
                    if await model.claim() { onTransferred() }
                }
            }
        }
    }

    private func sectionTitle(_ title: String) -> some View {
        Text(title)
            .font(.headline)
            .foregroundStyle(AppDesign.ink)
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
                    .textSelection(.enabled)
                    .frame(maxWidth: .infinity, alignment: .leading)
                Button {
                    UIPasteboard.general.string = value
                    model.message = "\(title)をコピーしました"
                    model.errorMessage = nil
                } label: {
                    Label("コピー", systemImage: "doc.on.doc")
                        .font(.subheadline.weight(.semibold))
                }
                .buttonStyle(.bordered)
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
