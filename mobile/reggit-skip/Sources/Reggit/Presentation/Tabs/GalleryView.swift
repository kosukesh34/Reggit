import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class GalleryViewModel {
    var isLoading = true
    var errorMessage: String?
    var categories: [GalleryCategoryItem] = []
    var items: [GalleryItem] = []
    var selectedCategoryId: Int?

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var mediaBaseURL: String { apiClient.baseURL }

    func load(force: Bool = false) async {
        let key = SecondaryScreenCache.galleryKey(selectedCategoryId)
        if !force, let cached = SecondaryScreenCache.galleries[key] {
            categories = cached.categories
            items = cached.items
            isLoading = false
            return
        }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let snapshot = try await apiClient.fetchGalleryContent(categoryId: selectedCategoryId)
            categories = snapshot.categories
            items = snapshot.items
            SecondaryScreenCache.galleries[key] = snapshot
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func selectCategory(_ id: Int?) async {
        selectedCategoryId = id
        await load()
    }
}

struct GalleryView: View {
    @State var viewModel: GalleryViewModel
    @State var selectedItem: GalleryItem?
    @Environment(\.brandAccent) var accent

    private let columns = [
        GridItem(.flexible(), spacing: 14),
        GridItem(.flexible(), spacing: 14),
    ]

    private var featuredItem: GalleryItem? {
        viewModel.items.first(where: \.isFeatured) ?? viewModel.items.first
    }

    private var gridItems: [GalleryItem] {
        guard let featuredItem else { return viewModel.items }
        return viewModel.items.filter { $0.id != featuredItem.id }
    }

    init(apiClient: any ReggitAPIServing) {
        _viewModel = State(initialValue: GalleryViewModel(apiClient: apiClient))
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                VStack(spacing: 0) {
                    if !viewModel.categories.isEmpty {
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 8) {
                                categoryChip(title: "すべて", id: nil)
                                ForEach(viewModel.categories) { category in
                                    categoryChip(title: category.name, id: category.id)
                                }
                            }
                            .padding(.horizontal, 16)
                            .padding(.vertical, 10)
                        }
                    }
                    content
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .refreshable { await viewModel.load(force: true) }
                .navigationDestination(item: $selectedItem) { item in
                    galleryDetail(item).headerBackDestination()
                }
            }
        }
        .task { await viewModel.load() }
    }

    private func galleryDetail(_ item: GalleryItem) -> some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                RemoteImageView(
                    urlString: item.imageURL,
                    baseURL: viewModel.mediaBaseURL,
                    contentMode: .fit,
                    cornerRadius: 12
                )
                .frame(maxWidth: .infinity)
                .frame(minHeight: 280)
                if !item.title.isEmpty {
                    Text(item.title).font(.title2.weight(.bold))
                }
                if !item.description.isEmpty {
                    Text(item.description).font(.body).foregroundStyle(.secondary)
                }
            }
            .padding(20)
        }
        .background(AppDesign.canvas.ignoresSafeArea())
        .navigationTitle("")
    }

    private func categoryChip(title: String, id: Int?) -> some View {
        let isOn = viewModel.selectedCategoryId == id
        return Button {
            Task { await viewModel.selectCategory(id) }
        } label: {
            Text(title)
                .font(.system(size: 13, weight: .semibold))
                .padding(.horizontal, 14)
                .frame(minHeight: 44)
                .background(isOn ? accent : AppDesign.surface)
                .foregroundStyle(isOn ? Color.white : AppDesign.ink)
                .clipShape(Capsule())
                .overlay(
                    Capsule().stroke(isOn ? Color.clear : AppDesign.hairline, lineWidth: 1)
                )
        }
        .buttonStyle(.plain)
    }

    @ViewBuilder
    private var content: some View {
        AppAsyncScreen(
            isLoading: viewModel.isLoading && viewModel.items.isEmpty,
            errorMessage: viewModel.items.isEmpty ? viewModel.errorMessage : nil,
            isEmpty: viewModel.items.isEmpty,
            emptyTitle: "ギャラリー画像はありません",
            emptySubtitle: "店舗の写真が登録されるとここに表示されます",
            emptyIcon: "photo.on.rectangle",
            onRetry: { Task { await viewModel.load() } },
            skeleton: { GallerySkeletonView() }
        ) {
            ScrollView {
                VStack(alignment: .leading, spacing: 22) {
                    VStack(alignment: .leading, spacing: 5) {
                        Text("STORE JOURNAL")
                            .font(.caption.weight(.bold))
                            .foregroundStyle(AppDesign.coral)
                        Text("お店の風景とおすすめ")
                            .font(.title2.weight(.bold))
                            .foregroundStyle(AppDesign.ink)
                        Text("\(viewModel.items.count)件の写真")
                            .font(.caption)
                            .foregroundStyle(AppDesign.inkSecondary)
                    }

                    if let featured = featuredItem {
                        Button { selectedItem = featured } label: {
                            ZStack(alignment: .bottomLeading) {
                                RemoteImageView(urlString: featured.imageURL, baseURL: viewModel.mediaBaseURL, contentMode: .fill, cornerRadius: AppDesign.radiusL)
                                    .frame(maxWidth: .infinity).frame(height: 236).clipped()
                                LinearGradient(colors: [Color.clear, Color.black.opacity(0.72)], startPoint: .center, endPoint: .bottom)
                                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
                                VStack(alignment: .leading, spacing: 5) {
                                    Text("FEATURED").font(.caption2.weight(.bold)).foregroundStyle(Color.white.opacity(0.72))
                                    Text(featured.title.isEmpty ? "おすすめ" : featured.title)
                                        .font(.title3.weight(.bold)).foregroundStyle(Color.white)
                                    if !featured.description.isEmpty {
                                        Text(featured.description).font(.caption).foregroundStyle(Color.white.opacity(0.82)).lineLimit(2)
                                    }
                                }
                                .padding(18)
                            }
                        }
                        .buttonStyle(.plain)
                    }

                    LazyVGrid(columns: columns, spacing: 18) {
                        ForEach(gridItems) { item in
                            Button { selectedItem = item } label: {
                                VStack(alignment: .leading, spacing: 10) {
                                    RemoteImageView(urlString: item.imageURL, baseURL: viewModel.mediaBaseURL, contentMode: .fill, cornerRadius: AppDesign.radiusM)
                                        .frame(maxWidth: .infinity)
                                        .frame(height: item.displaySize == "small" ? 136 : 176)
                                        .clipped()
                                    if !item.title.isEmpty {
                                        Text(item.title).font(.subheadline.weight(.semibold))
                                            .foregroundStyle(AppDesign.ink).multilineTextAlignment(.leading).lineLimit(2)
                                    }
                                    if !item.description.isEmpty {
                                        Text(item.description).font(.caption2).foregroundStyle(AppDesign.inkSecondary).lineLimit(2)
                                    }
                                }
                            }
                            .buttonStyle(.plain)
                        }
                    }
                }
                .padding(20)
            }
        }
    }
}
