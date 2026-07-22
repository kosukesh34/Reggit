import SwiftUI

struct ProjectSelectionView: View {
    @StateObject private var viewModel = ProjectSelectionViewModel()
    let onCompleted: () -> Void

    var body: some View {
        NavigationView {
            Form {
                Section {
                    LabeledContent("向け先", value: AppBuildConfig.serverTargetLabel)
                    LabeledContent("API URL", value: AppBuildConfig.baseURL)
                } header: {
                    Text("サーバー")
                } footer: {
                    Text("接続先は AppBuildConfig.swift でビルド時に固定されています。LINE 設定は API から取得します。")
                }

                Section(header: Text("プロジェクト")) {
                    if viewModel.isLoading {
                        ProgressView("読み込み中…")
                    } else if viewModel.projects.isEmpty {
                        Text("プロジェクトがありません。AppBuildConfig の向け先を確認してください。")
                            .foregroundColor(Theme.Colors.textSecondary)
                    } else {
                        Picker("店舗", selection: $viewModel.selectedProjectKey) {
                            ForEach(viewModel.projects) { project in
                                Text(project.name).tag(project.projectKey)
                            }
                        }
                        .pickerStyle(.inline)
                    }
                }

                if let errorMessage = viewModel.errorMessage {
                    Section {
                        Text(errorMessage)
                            .foregroundColor(.red)
                            .font(.footnote)
                    }
                }
            }
            .navigationTitle("プロジェクト選択")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("決定") {
                        if viewModel.confirmSelection() {
                            onCompleted()
                        }
                    }
                    .fontWeight(.semibold)
                    .disabled(viewModel.selectedProjectKey.isEmpty)
                }
            }
            .task {
                await viewModel.loadProjects()
            }
        }
        .navigationViewStyle(.stack)
    }
}
