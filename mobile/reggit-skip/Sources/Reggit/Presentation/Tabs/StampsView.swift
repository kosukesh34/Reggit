import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class StampsViewModel {
    var isLoading = true
    var errorMessage: String?
    var cards: [StampCardItem] = []

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var mediaBaseURL: String { apiClient.baseURL }

    func load(force: Bool = false) async {
        if !force, let cached = SecondaryScreenCache.stamps {
            cards = cached
            isLoading = false
            return
        }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            cards = try await apiClient.fetchStampCards()
            SecondaryScreenCache.stamps = cards
        }
        catch { errorMessage = error.localizedDescription }
    }
}

struct StampsView: View {
    @State var viewModel: StampsViewModel
    @Environment(\.brandAccent) var accent

    init(apiClient: any ReggitAPIServing) {
        _viewModel = State(initialValue: StampsViewModel(apiClient: apiClient))
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                AppAsyncScreen(
                    isLoading: viewModel.isLoading,
                    errorMessage: viewModel.errorMessage,
                    isEmpty: viewModel.cards.isEmpty,
                    emptyTitle: "スタンプカードはありません",
                    emptySubtitle: "スタンプカードがあるとここに表示されます",
                    emptyIcon: "star",
                    onRetry: { Task { await viewModel.load() } },
                    skeleton: { StampsSkeletonView() }
                ) {
                    ScrollView {
                        LazyVStack(spacing: 0) {
                            ForEach(Array(viewModel.cards.enumerated()), id: \.element.id) { index, card in
                                stampCard(card)
                                if index < viewModel.cards.count - 1 { Divider() }
                            }
                        }
                        .padding(16)
                    }
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .refreshable { await viewModel.load(force: true) }
            }
        }
        .task { await viewModel.load() }
    }

    private func stampCard(_ card: StampCardItem) -> some View {
        let slots = max(min(card.requiredStamps, 12), 1)
        return VStack(alignment: .leading, spacing: 14) {
                HStack(spacing: 14) {
                    if card.imageURL != nil {
                        RemoteImageView(urlString: card.imageURL, baseURL: viewModel.mediaBaseURL, contentMode: .fill, cornerRadius: 18)
                            .frame(width: 64, height: 64)
                    } else {
                        FontAwesomeIcon(systemName: "seal")
                            .frame(width: 64, height: 64)
                            .background(AppDesign.canvas).clipShape(RoundedRectangle(cornerRadius: 18))
                    }
                    VStack(alignment: .leading, spacing: 6) {
                        Text(card.name)
                            .font(.system(size: 18, weight: .bold))
                        Text(card.progressLabel)
                            .font(.system(size: 28, weight: .bold, design: .rounded))
                            .foregroundStyle(accent)
                    }
                    Spacer()
                }

                GeometryReader { geometry in
                    ZStack(alignment: .leading) {
                        Capsule().fill(AppDesign.hairline)
                        Capsule().fill(accent)
                            .frame(width: geometry.size.width * CGFloat(max(0, min(card.currentStamps, max(card.requiredStamps, 1)))) / CGFloat(max(card.requiredStamps, 1)))
                    }
                }
                    .frame(height: 4)
                    .accessibilityLabel("スタンプの達成状況")
                    .accessibilityValue("\(max(0, min(card.currentStamps, max(card.requiredStamps, 1))) * 100 / max(card.requiredStamps, 1))%")

                // A lazy grid inside the cards' lazy stack is suppressed by Skip's
                // nested scroll handling. These at most twelve slots need no laziness.
                VStack(spacing: 8) {
                    ForEach(0 ..< (slots + 4) / 5, id: \.self) { row in
                        HStack(spacing: 8) {
                            ForEach(0 ..< min(slots, 5), id: \.self) { column in
                                let index = row * 5 + column
                                Group {
                                    if index < slots {
                                        ZStack {
                                            Circle()
                                                .stroke(AppDesign.hairline, lineWidth: 1.5)
                                                .background(Circle().fill(index < card.currentStamps ? AppDesign.ink : AppDesign.canvas))
                                            if index < card.currentStamps {
                                                FontAwesomeIcon(systemName: "star.fill")
                                                    .foregroundStyle(.white)
                                            }
                                        }
                                        .frame(width: 40, height: 40)
                                    } else {
                                        Color.clear.frame(width: 40, height: 40)
                                    }
                                }
                                .frame(maxWidth: .infinity)
                            }
                        }
                    }
                }

                if card.requiredStamps > slots {
                    Text("全 \(card.requiredStamps) 個のうち \(card.currentStamps) 個獲得")
                        .font(.caption).foregroundStyle(AppDesign.inkSecondary)
                }
                if card.currentStamps >= card.requiredStamps && card.requiredStamps > 0 {
                    FontAwesomeLabel("スタンプが貯まりました", systemImage: "checkmark.seal.fill")
                        .font(.subheadline.weight(.semibold))
                }
                if !card.rewardDescription.isEmpty {
                    FontAwesomeLabel(card.rewardDescription, systemImage: "gift")
                        .font(.subheadline).foregroundStyle(AppDesign.ink)
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .padding(16).background(AppDesign.surfaceMuted)
                        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
                }
        }
        .padding(.vertical, 20)
    }
}
