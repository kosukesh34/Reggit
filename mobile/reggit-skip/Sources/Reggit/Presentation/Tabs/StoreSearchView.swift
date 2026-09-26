import SkipFuse
import SwiftUI

struct StoreSearchView: View {
    @State var viewModel: StoreSearchViewModel
    let onStoreChanged: () -> Void
    @Environment(\.brandAccent) var accent

    init(
        baseURL: String,
        currentProjectKey: String,
        projectService: any MobileProjectsServing = MobileProjectsAPIClient(),
        onStoreChanged: @escaping () -> Void
    ) {
        _viewModel = State(initialValue: StoreSearchViewModel(
            baseURL: baseURL,
            currentProjectKey: currentProjectKey,
            projectService: projectService
        ))
        self.onStoreChanged = onStoreChanged
    }

    var body: some View {
        NavigationStack {
            SkipRefreshing {
                Group {
                    if viewModel.isLoading && viewModel.projects.isEmpty {
                        loadingContent
                    } else if viewModel.projects.isEmpty {
                        AppEmptyState(
                            title: "店舗を取得できませんでした",
                            subtitle: viewModel.errorMessage ?? "通信環境をご確認ください",
                            systemImage: "building.2",
                            actionTitle: "再読み込み",
                            action: { Task { await viewModel.load() } }
                        )
                    } else {
                        storeList
                    }
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationTitle("店舗を探す")
                .navigationBarTitleDisplayMode(.inline)
                .searchable(text: $viewModel.query, prompt: "店舗名で検索")
                .refreshable { await viewModel.load() }
            }
        }
        .task { await viewModel.load() }
    }

    private var loadingContent: some View {
        VStack(spacing: 10) {
            ForEach(0..<5, id: \.self) { _ in
                SkeletonBlock(height: 64, cornerRadius: AppDesign.radiusM)
            }
            Spacer()
        }
        .padding(16)
    }

    private var storeList: some View {
        ScrollView {
            LazyVStack(spacing: 0) {
                if viewModel.filteredProjects.isEmpty {
                    AppEmptyState(
                        title: "店舗が見つかりません",
                        subtitle: "検索する店舗名を変えてお試しください",
                        systemImage: "magnifyingglass"
                    )
                    .padding(.top, 48)
                } else {
                    ForEach(Array(viewModel.filteredProjects.enumerated()), id: \.element.id) { index, project in
                        storeRow(project)
                        if index < viewModel.filteredProjects.count - 1 {
                            Divider().padding(.leading, 56)
                        }
                    }
                }
            }
            .background(AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                    .stroke(AppDesign.hairline, lineWidth: 1)
            }
            .padding(16)
        }
    }

    private func storeRow(_ project: MobilePublicProject) -> some View {
        let isCurrent = project.projectKey == viewModel.currentProjectKey
        return Button {
            if viewModel.select(project) {
                onStoreChanged()
            }
        } label: {
            HStack(spacing: 14) {
                FontAwesomeIcon(systemName: "location.fill")
                    .foregroundStyle(isCurrent ? accent : AppDesign.inkSecondary)
                    .frame(width: 28, height: 44)
                VStack(alignment: .leading, spacing: 4) {
                    Text(project.name)
                        .font(.body.weight(.semibold))
                        .foregroundStyle(AppDesign.ink)
                        .frame(maxWidth: .infinity, alignment: .leading)
                    Text(isCurrent ? "現在利用中の店舗" : "この店舗を利用する")
                        .font(.caption)
                        .foregroundStyle(AppDesign.inkSecondary)
                }
                if isCurrent {
                    FontAwesomeIcon(systemName: "checkmark.circle.fill")
                        .foregroundStyle(accent)
                        .accessibilityHidden(true)
                } else {
                    FontAwesomeIcon(systemName: "chevron.right")
                        .foregroundStyle(AppDesign.inkSecondary)
                        .accessibilityHidden(true)
                }
            }
            .padding(.horizontal, 14)
            .frame(minHeight: 64)
            .contentShape(Rectangle())
        }
        .buttonStyle(.plain)
        .disabled(isCurrent)
        .accessibilityLabel(Text(isCurrent ? "\(project.name)、現在利用中" : "\(project.name)に切り替える"))
    }
}
