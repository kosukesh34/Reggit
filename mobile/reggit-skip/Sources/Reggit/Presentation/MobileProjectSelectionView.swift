import Observation
import SkipFuse
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
            let fetched = try await apiClient.fetchProjects(baseURL: configRepository.resolveConfig().baseUrl)
            projects = fetched
            if selectedProjectKey.isEmpty || !fetched.contains(where: { $0.projectKey == selectedProjectKey }) {
                selectedProjectKey = fetched.first?.projectKey ?? ""
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func confirmSelection() -> Bool {
        let key = selectedProjectKey.trimmed
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
    @State var viewModel = MobileProjectSelectionViewModel()
    let onCompleted: () -> Void

    var body: some View {
        SkipRefreshing {
            VStack(spacing: 20) {
                VStack(spacing: 8) {
                    Text("店舗を選択")
                        .font(.title2.bold())
                    Text("利用する店舗を選んでください")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                        .multilineTextAlignment(.center)
                }

                if viewModel.isLoading {
                    VStack(spacing: 12) {
                        SkeletonBlock(height: 48, cornerRadius: 12)
                        SkeletonBlock(height: 48, cornerRadius: 12)
                        SkeletonBlock(height: 48, cornerRadius: 12)
                    }
                } else if viewModel.projects.isEmpty {
                    AppEmptyState(
                        title: "店舗を取得できませんでした",
                        subtitle: viewModel.errorMessage ?? "通信環境をご確認ください",
                        systemImage: "building.2",
                        actionTitle: "再読み込み",
                        action: { Task { await viewModel.loadProjects() } }
                    )
                } else {
                    VStack(spacing: 8) {
                        ForEach(viewModel.projects) { project in
                            Button {
                                viewModel.selectedProjectKey = project.projectKey
                            } label: {
                                HStack {
                                    Text(project.name)
                                        .foregroundStyle(AppDesign.ink)
                                    Spacer()
                                    if viewModel.selectedProjectKey == project.projectKey {
                                        Image(systemName: "checkmark")
                                            .foregroundStyle(AppDesign.ink)
                                    }
                                }
                                .padding(14)
                                .background(AppDesign.surface)
                                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                                .overlay(
                                    RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                                        .stroke(
                                            viewModel.selectedProjectKey == project.projectKey
                                                ? AppDesign.ink
                                                : AppDesign.hairline,
                                            lineWidth: 1
                                        )
                                )
                            }
                            .buttonStyle(.plain)
                        }
                    }

                    if let errorMessage = viewModel.errorMessage {
                        Text(errorMessage)
                            .font(.footnote)
                            .foregroundStyle(AppDesign.danger)
                    }

                    AppPrimaryButton(title: "この店舗ではじめる") {
                        if viewModel.confirmSelection() {
                            onCompleted()
                        }
                    }
                    .disabled(viewModel.selectedProjectKey.isEmpty)
                }
            }
            .padding(24)
            .frame(maxWidth: .infinity, maxHeight: .infinity)
            .background(AppDesign.canvas.ignoresSafeArea())
        }
        .task {
            await viewModel.loadProjects()
        }
    }
}
