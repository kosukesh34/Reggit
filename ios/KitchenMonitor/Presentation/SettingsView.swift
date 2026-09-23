import SwiftUI

struct SettingsView: View {
    @StateObject private var viewModel = SettingsViewModel()
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            Form {
                Section("接続") {
                    SecureField("店舗端末トークン", text: $viewModel.storeToken)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                    LabeledContent("向け先", value: viewModel.serverLabel)
                    LabeledContent("プロジェクト", value: viewModel.projectKey)
                    Text("プロジェクトはビルド設定（AppBuildConfig）で固定です。Waiting と同じ運用です。")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Section("端末") {
                    TextField("端末名", text: $viewModel.deviceName)
                    SecureField("管理パスコード", text: $viewModel.passcode)
                        .keyboardType(.numberPad)
                }
                if let message = viewModel.message {
                    Section {
                        Text(message).foregroundStyle(.secondary)
                    }
                }
            }
            .navigationTitle("設定")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("閉じる") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("保存") {
                        viewModel.save()
                        Task { await viewModel.registerPushIfPossible() }
                    }
                }
            }
        }
    }
}
