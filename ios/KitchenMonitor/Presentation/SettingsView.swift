import SwiftUI

struct SettingsView: View {
    @StateObject private var viewModel = SettingsViewModel()
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            Form {
                Section("接続") {
                    LabeledContent("向け先", value: viewModel.serverLabel)
                    LabeledContent("プロジェクト", value: viewModel.projectKey)
                    Text("プロジェクトはログインした店舗に固定されます。変更する場合はログアウトしてください。")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                Section("端末") {
                    TextField("端末名", text: $viewModel.deviceName)
                    SecureField("管理パスコード", text: $viewModel.passcode)
                        .keyboardType(.numberPad)
                }
                Section {
                    Button("ログアウト", role: .destructive) {
                        appState.logout()
                        dismiss()
                    }
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
