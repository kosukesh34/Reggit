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

    func history(cardId: Int) async throws -> [StampHistoryItem] {
        try await apiClient.fetchStampHistory(cardId: cardId)
    }

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
                        LazyVStack(spacing: 16) {
                            ForEach(viewModel.cards) { card in
                                StampCardSection(card: card, mediaBaseURL: viewModel.mediaBaseURL, loadHistory: { try await viewModel.history(cardId: card.id) })
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
}

/// スタンプカード1枚。デザイン（画像の置き方）と並べ方は管理画面の設定に従い、多いときはページを分ける。
/// 見た目は LINE アプリ（resources/js/liff-ui/native/NativeStampsPage.tsx）と揃える
struct StampCardSection: View {
    let card: StampCardItem
    let mediaBaseURL: String
    var loadHistory: () async throws -> [StampHistoryItem] = { [] }
    @State var page: Int?
    @State var showsHistory = false
    @State var history: [StampHistoryItem]?
    @State var historyError: String?
    @Environment(\.brandAccent) var accent

    var body: some View {
        let layout = StampLayout(card: card, page: page)
        return VStack(alignment: .leading, spacing: 14) {
            if layout.design == "membership" {
                membershipFace(layout)
            } else {
                if layout.design == "banner" && card.imageURL != nil {
                    RemoteImageView(urlString: card.imageURL, baseURL: mediaBaseURL, contentMode: .fill)
                        .aspectRatio(1125.0 / 432.0, contentMode: .fit)
                        .clipShape(RoundedRectangle(cornerRadius: 20))
                        .padding(.horizontal, -18)
                        .padding(.top, -18)
                }
                HStack(spacing: 14) {
                    if layout.design == "standard" {
                        if card.imageURL != nil {
                            RemoteImageView(urlString: card.imageURL, baseURL: mediaBaseURL, contentMode: .fill, cornerRadius: 14)
                                .frame(width: 56, height: 56)
                        } else {
                            FontAwesomeIcon(systemName: "star.fill", size: 22)
                                .foregroundStyle(accent)
                                .frame(width: 56, height: 56)
                                .background(AppDesign.surfaceMuted).clipShape(RoundedRectangle(cornerRadius: 14))
                        }
                    }
                    VStack(alignment: .leading, spacing: 2) {
                        Text(card.name)
                            .font(.system(size: 14, weight: .semibold))
                            .foregroundStyle(AppDesign.inkSecondary)
                        HStack(alignment: .lastTextBaseline, spacing: 4) {
                            Text("\(layout.current)")
                                .font(.system(size: 34, weight: .heavy, design: .rounded))
                                .foregroundStyle(accent)
                            Text("/ \(layout.required)")
                                .font(.system(size: 15, weight: .semibold))
                                .foregroundStyle(AppDesign.ink)
                        }
                    }
                    Spacer(minLength: 0)
                }
            }

            nextRewardBanner(layout)
            if layout.layout != "minimal" && layout.design != "membership" {
                progressBar(layout)
            }
            if layout.design != "membership" {
                slotGrid(layout, size: layout.dense ? 27 : (layout.layout == "row" ? 36 : (layout.layout == "minimal" ? 28 : 44)))
            }
            if layout.pages > 1 {
                pager(layout)
            }
            if !layout.milestones.isEmpty {
                milestoneList(layout)
            }
            historySection
            if card.validUntil != nil || !card.rewardDescription.isEmpty {
                VStack(alignment: .leading, spacing: 4) {
                    if let until = card.validUntil {
                        FontAwesomeLabel("有効期限 \(String(until.prefix(10)).replacingOccurrences(of: "-", with: "/"))", systemImage: "calendar")
                    }
                    if !card.rewardDescription.isEmpty {
                        FontAwesomeLabel(card.rewardDescription, systemImage: "info.circle")
                    }
                }
                .font(.caption)
                .foregroundStyle(AppDesign.inkSecondary)
            }
        }
        .padding(18)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(AppDesign.surface)
        .clipShape(RoundedRectangle(cornerRadius: 20))
        .shadow(color: Color.black.opacity(0.06), radius: 12, x: 0, y: 4)
    }

    /// 会員証のような横長カード。画像を背景にして、名前と数をのせ、スタンプは読みやすいよう白い面に並べる
    private func membershipFace(_ layout: StampLayout) -> some View {
        VStack(alignment: .leading, spacing: 14) {
            HStack(alignment: .top, spacing: 12) {
                Text(card.name)
                    .font(.system(size: 15, weight: .bold))
                Spacer(minLength: 0)
                HStack(alignment: .lastTextBaseline, spacing: 2) {
                    Text("\(layout.current)").font(.system(size: 28, weight: .heavy, design: .rounded))
                    Text("/ \(layout.required)").font(.system(size: 14, weight: .semibold)).opacity(0.85)
                }
            }
            .foregroundStyle(.white)
            .shadow(color: Color.black.opacity(0.35), radius: 3, x: 0, y: 1)
            Spacer(minLength: 0)
            slotGrid(layout, size: layout.dense ? 25 : 36)
                .padding(.vertical, 12).padding(.horizontal, 10)
                .background(Color.white.opacity(0.92))
                .clipShape(RoundedRectangle(cornerRadius: 14))
        }
        .padding(16)
        .frame(maxWidth: .infinity, minHeight: 205, alignment: .topLeading)
        .background(
            ZStack {
                LinearGradient(colors: [accent, AppDesign.forest], startPoint: .topLeading, endPoint: .bottomTrailing)
                if card.imageURL != nil {
                    RemoteImageView(urlString: card.imageURL, baseURL: mediaBaseURL, contentMode: .fill)
                }
                LinearGradient(colors: [Color.black.opacity(0.42), Color.black.opacity(0)], startPoint: .top, endPoint: .center)
            }
        )
        .clipShape(RoundedRectangle(cornerRadius: 18))
        .shadow(color: Color.black.opacity(0.2), radius: 14, x: 0, y: 10)
    }

    @ViewBuilder
    private func nextRewardBanner(_ layout: StampLayout) -> some View {
        let complete = layout.nextReward == nil
        HStack(spacing: 10) {
            FontAwesomeIcon(systemName: complete ? "checkmark.seal.fill" : "gift", size: 13)
                .foregroundStyle(complete ? accent : .white)
                .frame(width: 30, height: 30)
                .background(Circle().fill(complete ? Color.white : accent))
            if let next = layout.nextReward {
                HStack(alignment: .lastTextBaseline, spacing: 0) {
                    Text("あと")
                    Text("\(next.remaining)").font(.system(size: 18, weight: .heavy)).foregroundStyle(accent)
                    Text(next.goal ? "個でゴール" : "個で「\(next.description)」")
                }
                .font(.subheadline)
                .foregroundStyle(AppDesign.ink)
            } else {
                Text("スタンプが貯まりました")
                    .font(.subheadline.weight(.bold))
                    .foregroundStyle(.white)
            }
            Spacer(minLength: 0)
        }
        .padding(.vertical, 12).padding(.horizontal, 14)
        .background(complete ? accent : accent.opacity(0.08))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }

    private func progressBar(_ layout: StampLayout) -> some View {
        let done = min(layout.current, layout.required)
        return GeometryReader { geometry in
            ZStack(alignment: .leading) {
                Capsule().fill(AppDesign.hairline)
                Capsule().fill(accent)
                    .frame(width: geometry.size.width * CGFloat(done) / CGFloat(layout.required))
            }
        }
        .frame(height: 4)
        .accessibilityLabel("スタンプの達成状況")
        .accessibilityValue("\(done * 100 / layout.required)%")
    }

    // A lazy grid inside the cards' lazy stack is suppressed by Skip's
    // nested scroll handling. A page holds at most fifty slots, so plain stacks are enough.
    /// 横にスワイプしてページを切り替える。縦のスクロールを止めないよう simultaneousGesture にする
    private func slotGrid(_ layout: StampLayout, size: CGFloat) -> some View {
        slotPage(layout, size: size)
            .contentShape(Rectangle())
            .simultaneousGesture(
                DragGesture(minimumDistance: 24)
                    .onEnded { value in
                        guard layout.pages > 1, abs(value.translation.width) > abs(value.translation.height), abs(value.translation.width) > 40 else { return }
                        let target = layout.page + (value.translation.width < 0 ? 1 : -1)
                        if target >= 0 && target < layout.pages {
                            withAnimation(.easeOut(duration: 0.2)) { page = target }
                        }
                    }
            )
    }

    @ViewBuilder
    private func slotPage(_ layout: StampLayout, size: CGFloat) -> some View {
        if let columns = layout.columns {
            let perRow = min(layout.slots.count, columns)
            let grid = VStack(spacing: layout.dense ? 7 : 10) {
                ForEach(0 ..< (layout.slots.count + perRow - 1) / perRow, id: \.self) { row in
                    HStack(spacing: layout.dense ? 4 : 8) {
                        ForEach(0 ..< perRow, id: \.self) { column in
                            let index = row * perRow + column
                            Group {
                                if index < layout.slots.count {
                                    slotView(layout.slots[index], size: size)
                                } else {
                                    Color.clear.frame(width: size, height: size)
                                }
                            }
                            .frame(maxWidth: .infinity)
                        }
                    }
                }
            }
            if layout.layout == "card" && layout.design != "membership" {
                grid.padding(.vertical, 14).padding(.horizontal, 10)
                    .background(AppDesign.surfaceMuted)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
            } else {
                grid
            }
        } else {
            ScrollView(.horizontal, showsIndicators: false) {
                HStack(spacing: 8) {
                    ForEach(layout.slots, id: \.position) { slot in
                        slotView(slot, size: size)
                    }
                }
                .padding(.vertical, 4)
            }
        }
    }

    /// 押した枠は塗りつぶし、まだの枠は点線の丸に番号、特典の枠はギフトの印にする
    private func slotView(_ slot: StampSlot, size: CGFloat) -> some View {
        ZStack {
            if let image = slot.image {
                RemoteImageView(urlString: image, baseURL: mediaBaseURL, contentMode: .fill, cornerRadius: size / 2)
            } else if slot.filled {
                Circle().fill(accent)
                FontAwesomeIcon(systemName: slot.reward ? "gift" : "star.fill", size: size * 0.38)
                    .foregroundStyle(.white)
            } else {
                Circle().fill(slot.reward ? accent.opacity(0.08) : AppDesign.surface)
                Circle().stroke(slot.reward ? accent.opacity(0.55) : AppDesign.hairline, style: StrokeStyle(lineWidth: size < 30 ? 1 : 1.5, dash: [3, 3]))
                if slot.reward {
                    FontAwesomeIcon(systemName: "gift", size: size * 0.34)
                        .foregroundStyle(accent)
                } else {
                    Text("\(slot.position)")
                        .font(.system(size: size < 30 ? 9 : 12, weight: .semibold))
                        .foregroundStyle(AppDesign.inkSecondary)
                }
            }
        }
        .frame(width: size, height: size)
        .accessibilityElement(children: .ignore)
        .accessibilityLabel("\(slot.position)番目\(slot.filled ? "、押印済み" : "、未押印")\(slot.reward ? "、特典あり" : "")")
    }

    private func pager(_ layout: StampLayout) -> some View {
        HStack(spacing: 16) {
            Button { page = layout.page - 1 } label: {
                FontAwesomeIcon(systemName: "chevron.left").frame(width: 44, height: 36)
                    .background(AppDesign.surfaceMuted).clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
            }
            .disabled(layout.page == 0)
            .accessibilityLabel("前のスタンプ")
            Text("\(layout.first)〜\(layout.last)個目")
                .font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
            Button { page = layout.page + 1 } label: {
                FontAwesomeIcon(systemName: "chevron.right").frame(width: 44, height: 36)
                    .background(AppDesign.surfaceMuted).clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
            }
            .disabled(layout.page == layout.pages - 1)
            .accessibilityLabel("次のスタンプ")
        }
        .frame(maxWidth: .infinity)
    }

    private func milestoneList(_ layout: StampLayout) -> some View {
        VStack(spacing: 0) {
            Divider()
            ForEach(layout.milestones, id: \.count) { milestone in
                let achieved = layout.current >= milestone.count
                HStack(spacing: 10) {
                    FontAwesomeIcon(systemName: achieved ? "checkmark" : "gift", size: 12)
                        .foregroundStyle(achieved ? .white : AppDesign.inkSecondary)
                        .frame(width: 28, height: 28)
                        .background(Circle().fill(achieved ? accent : AppDesign.surfaceMuted))
                    Text("\(milestone.count)個")
                        .font(.footnote.weight(.bold))
                        .foregroundStyle(AppDesign.inkSecondary)
                        .frame(width: 48, alignment: .leading)
                    Text(milestone.description)
                        .font(.subheadline)
                        .foregroundStyle(achieved ? AppDesign.inkSecondary : AppDesign.ink)
                        .strikethrough(achieved)
                    Spacer(minLength: 0)
                }
                .padding(.vertical, 10)
                Divider()
            }
        }
        .accessibilityElement(children: .contain)
        .accessibilityLabel("達成特典")
    }

    /// 獲得履歴。開いたときに読み込む
    private var historySection: some View {
        VStack(alignment: .leading, spacing: 0) {
            Button {
                showsHistory.toggle()
                if showsHistory && history == nil {
                    Task {
                        do { history = try await loadHistory() } catch { historyError = error.localizedDescription }
                    }
                }
            } label: {
                HStack(spacing: 8) {
                    FontAwesomeIcon(systemName: "clock.arrow.circlepath", size: 14)
                    Text("獲得履歴").font(.subheadline.weight(.semibold))
                    Spacer(minLength: 0)
                    FontAwesomeIcon(systemName: "chevron.down", size: 13)
                        .rotationEffect(.degrees(showsHistory ? 180 : 0))
                        .foregroundStyle(AppDesign.inkSecondary)
                }
                .foregroundStyle(AppDesign.ink)
                .frame(minHeight: 44)
                .padding(.horizontal, 14)
                .contentShape(Rectangle())
            }
            .buttonStyle(.plain)
            .accessibilityValue(showsHistory ? "開いています" : "閉じています")
            if showsHistory {
                Group {
                    if let historyError {
                        Text(historyError).font(.footnote).foregroundStyle(AppDesign.danger)
                    } else if let history {
                        if history.isEmpty {
                            Text("まだ履歴はありません").font(.footnote).foregroundStyle(AppDesign.inkSecondary)
                        } else {
                            VStack(spacing: 0) {
                                ForEach(history) { item in
                                    Divider()
                                    HStack(spacing: 10) {
                                        VStack(alignment: .leading, spacing: 2) {
                                            Text(item.label).font(.subheadline.weight(.semibold))
                                            if let createdAt = item.createdAt {
                                                Text(Self.historyDate(createdAt)).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                                            }
                                        }
                                        Spacer(minLength: 0)
                                        Text(item.delta > 0 ? "+\(item.delta)" : "\(item.delta)")
                                            .font(.system(size: 16, weight: .heavy))
                                            .foregroundStyle(item.delta < 0 ? AppDesign.danger : accent)
                                        Text("計\(item.balanceAfter)個")
                                            .font(.caption).foregroundStyle(AppDesign.inkSecondary)
                                            .frame(width: 56, alignment: .trailing)
                                    }
                                    .padding(.vertical, 10)
                                }
                            }
                        }
                    } else {
                        Text("読み込み中…").font(.footnote).foregroundStyle(AppDesign.inkSecondary)
                    }
                }
                .padding(.horizontal, 14)
                .padding(.bottom, 12)
            }
        }
        .background(AppDesign.surfaceMuted)
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }

    /// サーバーの ISO 8601 から「2026/10/30 10:00」の形にする。読めないときはそのまま出す
    static func historyDate(_ iso: String) -> String {
        let digits = iso.replacingOccurrences(of: "T", with: " ")
        guard digits.count >= 16 else { return iso }
        return String(digits.prefix(16)).replacingOccurrences(of: "-", with: "/")
    }
}
