import Observation
import SkipFuse
import SwiftUI
#if !os(Android)
import UIKit
#endif

@MainActor
@Observable
final class AnnouncementsViewModel {
    var isLoading = true
    var errorMessage: String?
    var items: [AnnouncementItem] = []
    private let onLoaded: ([AnnouncementItem]) -> Void
    let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing, onLoaded: @escaping ([AnnouncementItem]) -> Void = { _ in }) {
        self.apiClient = apiClient
        self.onLoaded = onLoaded
    }

    func load(force: Bool = false) async {
        if !force, let cached = SecondaryScreenCache.announcements {
            items = cached
            errorMessage = nil
            onLoaded(items)
            isLoading = false
            return
        }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            items = try await apiClient.fetchPublishedAnnouncements()
            SecondaryScreenCache.announcements = items
            onLoaded(items)
        }
        catch { errorMessage = error.localizedDescription }
    }
}

struct AnnouncementsView: View {
    @State var viewModel: AnnouncementsViewModel

    init(apiClient: any ReggitAPIServing, onLoaded: @escaping ([AnnouncementItem]) -> Void = { _ in }) {
        _viewModel = State(initialValue: AnnouncementsViewModel(apiClient: apiClient, onLoaded: onLoaded))
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
                onRetry: { Task { await viewModel.load(force: true) } },
                skeleton: { SkeletonBlock(height: 160, cornerRadius: AppDesign.radiusL) }
            ) {
                ScrollView {
                    LazyVStack(spacing: 14) {
                        ForEach(viewModel.items) { item in
                            AnnouncementCardView(item: item, apiClient: viewModel.apiClient)
                        }
                    }
                    .padding(.horizontal, 12)
                    .padding(.vertical, 14)
                }
                .refreshable { await viewModel.load(force: true) }
            }
            .background(AppDesign.canvas.ignoresSafeArea())
            .navigationBarHidden(true)
            .toolbar(.hidden, for: .navigationBar)
        }
        .task { await viewModel.load() }
    }
}

struct AnnouncementCardView: View {
    let item: AnnouncementItem
    let apiClient: any ReggitAPIServing
    @Environment(\.brandAccent) var accent

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            HStack(alignment: .top, spacing: 12) {
                FontAwesomeIcon(systemName: "bell", size: 20)
                    .foregroundStyle(accent)
                    .frame(width: 46, height: 46)
                    .background(accent.opacity(0.08))
                    .clipShape(Circle())
                    .accessibilityHidden(true)
                VStack(alignment: .leading, spacing: 5) {
                    Text(item.title.isEmpty ? "お知らせ" : item.title)
                        .font(.system(size: 16, weight: .medium))
                        .fixedSize(horizontal: false, vertical: true)
                    HStack(spacing: 8) {
                        Text(AppFormat.dateTime(item.publishedAt))
                            .font(.system(size: 12))
                            .foregroundStyle(AppDesign.inkSecondary)
                        if !item.isRead {
                            Text("NEW")
                                .font(.system(size: 12, weight: .semibold))
                                .foregroundStyle(Color.red)
                        }
                    }
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                if item.isPinned {
                    Text("固定")
                        .font(.system(size: 11, weight: .medium))
                        .foregroundStyle(accent)
                        .accessibilityLabel(Text("固定されたお知らせ"))
                }
            }
            if item.imageURL != nil {
                AnnouncementImageView(item: item, apiClient: apiClient)
                    .aspectRatio(4.0 / 3.0, contentMode: .fit)
            }
            if !item.body.isEmpty {
                Text(item.body)
                    .font(.system(size: 14))
                    .lineSpacing(5)
                    .fixedSize(horizontal: false, vertical: true)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
        }
        .foregroundStyle(AppDesign.inkSecondary)
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 10))
        .shadow(color: Color.black.opacity(0.08), radius: 3, x: 0, y: 1)
    }
}

struct AnnouncementImageView: View {
    let item: AnnouncementItem
    let apiClient: any ReggitAPIServing
    @State var image: UIImage?
    @State var failed = false

    private var isPrivateImage: Bool {
        guard let url = MediaURL.resolve(item.imageURL, baseURL: apiClient.baseURL) else { return false }
        return url.path == "/api/v1/announcements/\(item.id)/image"
    }

    var body: some View {
        Group {
            if !isPrivateImage {
                RemoteImageView(urlString: item.imageURL, baseURL: apiClient.baseURL, contentMode: .fit)
            } else if let image {
                Image(uiImage: image).resizable().aspectRatio(contentMode: .fit)
            } else if failed {
                Text("画像を読み込めませんでした")
                    .font(.caption).foregroundStyle(AppDesign.inkSecondary)
            } else {
                ProgressView()
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
        .task(id: item.imageURL) {
            guard isPrivateImage else { return }
            image = nil
            failed = false
            do {
                let data = try await apiClient.fetchAnnouncementImage(id: item.id)
                image = UIImage(data: data)
                failed = image == nil
            } catch {
                if !Task.isCancelled { failed = true }
            }
        }
    }
}
