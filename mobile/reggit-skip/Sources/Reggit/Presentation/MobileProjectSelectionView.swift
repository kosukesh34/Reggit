import Foundation
import Observation
import SwiftUI

@MainActor
@Observable
final class MobileProjectSelectionViewModel {
    var projects: [MobilePublicProject] = []
    var selectedProjectKey: String = ""
    var isLoading = false
    var errorMessage: String?

    private let configRepository: MobileWebConfigRepository
    private let apiClient: MobileProjectsAPIClient

    init(
        configRepository: MobileWebConfigRepository = BundleMobileWebConfigRepository(),
        apiClient: MobileProjectsAPIClient = MobileProjectsAPIClient()
    ) {
        self.configRepository = configRepository
        self.apiClient = apiClient
        self.selectedProjectKey = MobileSelectedProjectStore.selectedProjectKey
            ?? configRepository.resolveConfig().projectKey
    }

    func loadProjects() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let baseURL = configRepository.resolveConfig().baseUrl
            let fetched = try await apiClient.fetchProjects(baseURL: baseURL)
            projects = fetched
            if selectedProjectKey.isEmpty || !fetched.contains(where: { $0.projectKey == selectedProjectKey }) {
                selectedProjectKey = fetched.first?.projectKey ?? ""
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func confirmSelection() -> Bool {
        let key = selectedProjectKey.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !key.isEmpty else {
            errorMessage = "プロジェクトを選択してください"
            return false
        }
        MobileSelectedProjectStore.selectedProjectKey = key
        MobileSelectedProjectStore.hasCompletedSelection = true
        return true
    }
}

struct MobileProjectSelectionView: View {
    @State private var viewModel = MobileProjectSelectionViewModel()
    let onCompleted: () -> Void

    var body: some View {
        VStack(spacing: 16) {
            Text("プロジェクトを選択")
                .font(.title2.bold())
            Text("利用する店舗（プロジェクト）を選んでください")
                .font(.subheadline)
                .foregroundStyle(.secondary)
                .multilineTextAlignment(.center)

            if viewModel.isLoading {
                ProgressView()
            } else if viewModel.projects.isEmpty {
                Text(viewModel.errorMessage ?? "プロジェクトを取得できませんでした")
                    .foregroundStyle(.secondary)
                Button("再読み込み") {
                    Task { await viewModel.loadProjects() }
                }
            } else {
                Picker("プロジェクト", selection: $viewModel.selectedProjectKey) {
                    ForEach(viewModel.projects) { project in
                        Text(project.name).tag(project.projectKey)
                    }
                }
                #if !os(Android)
                .pickerStyle(.wheel)
                #endif
            }

            if let errorMessage = viewModel.errorMessage, !viewModel.projects.isEmpty {
                Text(errorMessage)
                    .font(.footnote)
                    .foregroundStyle(.red)
            }

            Button("このプロジェクトで開始") {
                if viewModel.confirmSelection() {
                    onCompleted()
                }
            }
            .buttonStyle(.borderedProminent)
            .disabled(viewModel.selectedProjectKey.isEmpty)
        }
        .padding(24)
        .task {
            await viewModel.loadProjects()
        }
    }
}
