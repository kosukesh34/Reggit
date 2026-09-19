import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class AnnouncementsViewModel {
    var isLoading = true
    var errorMessage: String?
    var items: [AnnouncementItem] = []
    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) { self.apiClient = apiClient }

    func load() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do { items = try await apiClient.fetchPublishedAnnouncements() }
        catch { errorMessage = error.localizedDescription }
    }
}

struct AnnouncementsView: View {
    @State var viewModel: AnnouncementsViewModel

    init(apiClient: any ReggitAPIServing) {
        _viewModel = State(initialValue: AnnouncementsViewModel(apiClient: apiClient))
    }

    var body: some View {
        NavigationStack {
            AppAsyncScreen(
                isLoading: viewModel.isLoading,
                errorMessage: viewModel.errorMessage,
                isEmpty: viewModel.items.isEmpty,
                emptyTitle: "お知らせはありません",
                emptySubtitle: "新しいお知らせはこちらに表示されます",
                emptyIcon: "bell",
                onRetry: { Task { await viewModel.load() } },
                skeleton: { SkeletonBlock(height: 160, cornerRadius: AppDesign.radiusL) }
            ) {
                List(viewModel.items) { item in
                    VStack(alignment: .leading, spacing: 6) {
                        Text(item.title.isEmpty ? "お知らせ" : item.title).font(.headline)
                        Text(item.body).font(.subheadline).foregroundStyle(AppDesign.inkSecondary).lineLimit(3)
                        Text(AppFormat.dateTime(item.publishedAt)).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                    }
                    .padding(.vertical, 6)
                }
                .scrollContentBackground(.hidden)
            }
            .background(AppDesign.canvas.ignoresSafeArea())
            .navigationTitle("お知らせ")
            .refreshable { await viewModel.load() }
        }
        .task { await viewModel.load() }
    }
}
