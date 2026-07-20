import SwiftUI

struct ProjectSelectionView: View {
    @StateObject private var viewModel = ProjectSelectionViewModel()
    let onCompleted: () -> Void

    var body: some View {
        NavigationView {
            Form {
                Section(header: Text("サーバー")) {
                    TextField("Base URL", text: $viewModel.baseURL)
                        .keyboardType(.URL)
                        .textInputAutocapitalization(.never)
                        .disableAutocorrection(true)
                    Button("プロジェクト一覧を取得") {
                        Task { await viewModel.loadProjects() }
                    }
                    .disabled(viewModel.isLoading)
                }

                Section(header: Text("プロジェクト")) {
                    if viewModel.isLoading {
                        ProgressView("読み込み中…")
                    } else if viewModel.projects.isEmpty {
                        Text("プロジェクトがありません。Base URL を確認して再取得してください。")
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
