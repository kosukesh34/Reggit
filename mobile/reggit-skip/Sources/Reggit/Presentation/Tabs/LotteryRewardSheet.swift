import SkipFuse
import SwiftUI

/// Purchase rewards stay above the order screen and play without extra taps.
struct LotteryRewardSheet: View {
    @State var viewModel: LotteryViewModel
    @Environment(\.accessibilityReduceMotion) var reduceMotion
    let onClose: () -> Void
    let isPreview: Bool

    private let ink = Color(red: 0.12, green: 0.23, blue: 0.21)
    private let paper = Color(red: 0.98, green: 0.97, blue: 0.94)

    init(apiClient: any ReggitAPIServing, onClose: @escaping () -> Void) {
        _viewModel = State(initialValue: LotteryViewModel(apiClient: apiClient))
        self.onClose = onClose
        self.isPreview = false
    }

    init(previewGame: LotteryGameKind, onClose: @escaping () -> Void) {
        _viewModel = State(initialValue: LotteryViewModel(service: LotteryPreviewService(game: previewGame), usesCache: false))
        self.onClose = onClose
        self.isPreview = true
    }

    var body: some View {
        SkipRefreshing {
            GeometryReader { geometry in
                let stageHeight = max(150, min(250, geometry.size.height - 164))
                ScrollView {
                    VStack(spacing: 14) {
                        header
                        if let result = viewModel.revealedResult {
                            resultContent(result)
                        } else if let error = viewModel.errorMessage, viewModel.state == nil {
                            failureContent(error)
                        } else if let error = viewModel.alertMessage {
                            failureContent(error)
                        } else if viewModel.isLoading {
                            ProgressView("購入特典を確認しています")
                                .tint(ink)
                                .frame(maxWidth: .infinity, minHeight: 190)
                        } else if viewModel.nextEntitlement != nil || viewModel.isPlaying {
                            machine
                                .frame(height: 250)
                                .scaleEffect(stageHeight / 250)
                                .frame(height: stageHeight)
                            HStack(spacing: 8) {
                                ProgressView().tint(ink).scaleEffect(0.8)
                                Text(viewModel.selectedGame == .scratch ? "スクラッチを開いています…" : "抽選中です。お楽しみに…")
                                    .font(.system(size: 13, weight: .medium))
                            }
                            .foregroundStyle(ink.opacity(0.7))
                            .accessibilityLabel("抽選中です。結果は自動で表示されます")
                        } else {
                            Text("現在利用できる購入特典はありません")
                                .font(.subheadline)
                                .padding(.vertical, 48)
                            action("注文に戻る", action: onClose)
                        }
                    }
                    .padding(.horizontal, 24)
                    .padding(.top, 22)
                    .padding(.bottom, 24)
                    .frame(maxWidth: .infinity)
                }
                .background(paper.ignoresSafeArea())
            }
        }
        .interactiveDismissDisabled(viewModel.isLoading || viewModel.isPlaying || viewModel.isScratchResolving)
        .task {
            await viewModel.load(force: true)
            guard !Task.isCancelled, viewModel.errorMessage == nil else { return }
            await viewModel.playNextAutomatically(reduceMotion: reduceMotion)
        }
    }

    private var header: some View {
        HStack(alignment: .top) {
            VStack(alignment: .leading, spacing: 6) {
                HStack(spacing: 5) {
                    Image(systemName: "checkmark.seal.fill")
                    Text(isPreview ? "テスト表示 · 抽選券は消費しません" : "ご購入ありがとうございます")
                }
                .font(.system(size: 11, weight: .semibold))
                .foregroundStyle(ink.opacity(0.65))
                Text(viewModel.revealedResult == nil ? "\(isPreview ? "テスト" : "購入特典") · \(viewModel.selectedGame.title)" : "抽選結果")
                    .font(.system(size: 23, weight: .bold, design: .rounded))
                    .foregroundStyle(ink)
            }
            Spacer(minLength: 8)
            if !viewModel.isLoading && !viewModel.isPlaying && !viewModel.isScratchResolving {
                Button(action: onClose) {
                    Image(systemName: "xmark")
                        .font(.system(size: 13, weight: .semibold))
                        .foregroundStyle(ink.opacity(0.65))
                        .frame(width: 36, height: 36)
                        .background(ink.opacity(0.06))
                        .clipShape(Circle())
                }
                .buttonStyle(.plain)
                .accessibilityLabel("閉じて注文に戻る")
            }
        }
    }

    @ViewBuilder
    private var machine: some View {
        switch viewModel.selectedGame {
        case .gacha:
            PurchaseGachaMachine(
                rotation: viewModel.gachaRotation,
                shake: viewModel.gachaShakeOffset,
                dropped: viewModel.showsDroppedCapsule,
                opened: viewModel.opensDroppedCapsule
            )
            .scaleEffect(0.92)
        case .roulette:
            RouletteWheelView(labels: viewModel.rouletteLabels, rotation: viewModel.rouletteRotation)
                .scaleEffect(0.60)
                .frame(height: 220)
        case .scratch:
            ScratchTicketView(
                progress: viewModel.scratchProgress,
                scratchedCells: viewModel.scratchedCells,
                result: viewModel.scratchResult,
                enabled: true,
                isResolving: viewModel.isScratchResolving,
                onScratch: { _, _, _, _ in },
                showsInstructions: false
            )
            .scaleEffect(0.72)
            .frame(height: 220)
            .allowsHitTesting(false)
        }
    }

    private func resultContent(_ result: LotteryPlayResult) -> some View {
        VStack(spacing: 12) {
            ZStack {
                Circle().fill(ink.opacity(0.05)).frame(width: 86, height: 86)
                Image(systemName: result.isWin ? "gift.fill" : "sparkles")
                    .font(.system(size: 34))
                    .foregroundStyle(result.isWin ? Color(red: 0.70, green: 0.49, blue: 0.22) : ink.opacity(0.5))
            }
            Text(result.isWin ? "おめでとうございます！" : "今回ははずれでした")
                .font(.system(size: 20, weight: .bold, design: .rounded))
            if let coupon = result.coupon {
                VStack(spacing: 6) {
                    Text(coupon.name).font(.system(size: 17, weight: .bold))
                    if let description = coupon.description?.trimmedNonEmpty {
                        Text(description).font(.system(size: 12)).foregroundStyle(ink.opacity(0.65))
                    }
                    Text(isPreview ? "テストのためクーポンは発行されません" : "クーポン一覧に追加しました")
                        .font(.system(size: 11, weight: .medium))
                        .foregroundStyle(ink.opacity(0.65))
                }
                .multilineTextAlignment(.center)
                .padding(14)
                .frame(maxWidth: .infinity)
                .background(Color.white)
                .clipShape(RoundedRectangle(cornerRadius: 16))
                .overlay(RoundedRectangle(cornerRadius: 16).stroke(ink.opacity(0.10), style: StrokeStyle(lineWidth: 1, dash: [4, 4])))
            } else {
                Text("またのご利用をお待ちしています")
                    .font(.system(size: 13)).foregroundStyle(ink.opacity(0.65))
            }
            action(viewModel.nextEntitlement == nil ? (isPreview ? "テストを閉じる" : "注文に戻る") : "次の抽選へ") {
                if viewModel.nextEntitlement == nil {
                    onClose()
                } else {
                    viewModel.revealedResult = nil
                    Task { await viewModel.playNextAutomatically(reduceMotion: reduceMotion) }
                }
            }
        }
        .foregroundStyle(ink)
        .padding(.top, 4)
    }

    private func failureContent(_ message: String) -> some View {
        VStack(spacing: 16) {
            Text("購入特典を確認できませんでした").font(.headline)
            Text(message).font(.subheadline).multilineTextAlignment(.center)
            action("もう一度確認する") {
                viewModel.alertMessage = nil
                Task {
                    await viewModel.load(force: true)
                    await viewModel.playNextAutomatically(reduceMotion: reduceMotion)
                }
            }
        }
        .foregroundStyle(ink)
        .padding(.vertical, 22)
    }

    private func action(_ title: String, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            Text(title)
                .font(.system(size: 15, weight: .semibold))
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity, minHeight: 48)
                .background(ink)
                .clipShape(RoundedRectangle(cornerRadius: 14))
        }
        .buttonStyle(.plain)
        .disabled(viewModel.isPlaying || viewModel.isScratchResolving)
    }
}

/// Compact tabletop machine: transparent hopper, enamel body and metal crank.
struct PurchaseGachaMachine: View {
    let rotation: Double
    let shake: Double
    let dropped: Bool
    let opened: Bool
    private let coral = Color(red: 0.72, green: 0.27, blue: 0.22)

    var body: some View {
        VStack(spacing: -3) {
            Capsule()
                .fill(LinearGradient(colors: [.white, Color(white: 0.73)], startPoint: .top, endPoint: .bottom))
                .frame(width: 180, height: 14)
            ZStack {
                RoundedRectangle(cornerRadius: 28)
                    .fill(LinearGradient(colors: [Color.white.opacity(0.95), Color(red: 0.78, green: 0.87, blue: 0.85).opacity(0.45)], startPoint: .topLeading, endPoint: .bottomTrailing))
                ForEach(0..<8, id: \.self) { index in
                    CapsuleBall(color: index % 3 == 0 ? coral : index % 3 == 1 ? Color(red: 0.29, green: 0.51, blue: 0.48) : Color(red: 0.83, green: 0.66, blue: 0.32))
                        .frame(width: 38, height: 38)
                        .rotationEffect(.degrees(Double(index * 23) + rotation * 0.025))
                        .offset(x: CGFloat(index % 4) * 39 - 58, y: CGFloat(index / 4) * 34 - 4)
                }
                RoundedRectangle(cornerRadius: 28)
                    .stroke(LinearGradient(colors: [.white, Color(white: 0.65), .white], startPoint: .topLeading, endPoint: .bottomTrailing), lineWidth: 3)
                Capsule().fill(Color.white.opacity(0.75)).frame(width: 9, height: 64).rotationEffect(.degrees(15)).offset(x: -66, y: -8)
            }
            .frame(width: 180, height: 112)
            .clipShape(RoundedRectangle(cornerRadius: 28))
            ZStack {
                RoundedRectangle(cornerRadius: 17)
                    .fill(LinearGradient(colors: [coral, Color(red: 0.50, green: 0.16, blue: 0.13)], startPoint: .topLeading, endPoint: .bottomTrailing))
                RoundedRectangle(cornerRadius: 14).stroke(Color.white.opacity(0.22), lineWidth: 1).padding(5)
                HStack(spacing: 18) {
                    ZStack {
                        Circle().fill(LinearGradient(colors: [.white, Color(white: 0.55)], startPoint: .topLeading, endPoint: .bottomTrailing)).frame(width: 60, height: 60)
                        Circle().fill(Color(white: 0.89)).frame(width: 44, height: 44)
                        Capsule().fill(Color(white: 0.30)).frame(width: 44, height: 12)
                        Circle().fill(.white).frame(width: 10, height: 10).offset(x: 17)
                    }
                    .rotationEffect(.degrees(rotation))
                    .shadow(color: .black.opacity(0.2), radius: 2, y: 3)
                    ZStack {
                        RoundedRectangle(cornerRadius: 10).fill(Color(white: 0.12)).frame(width: 58, height: 56)
                        OpenedCapsuleView(isOpen: opened).scaleEffect(0.56).opacity(dropped ? 1 : 0).offset(y: dropped ? 4 : -30)
                    }
                }
            }
            .frame(width: 194, height: 94)
            Capsule().fill(Color(white: 0.26)).frame(width: 174, height: 9)
            Ellipse().fill(Color.black.opacity(0.12)).frame(width: 204, height: 12).blur(radius: 4)
        }
        .offset(x: CGFloat(shake))
        .shadow(color: Color.black.opacity(0.10), radius: 8, y: 5)
        .accessibilityLabel("カプセルが入った透明ケースと赤い本体、銀色のハンドルのガチャ")
    }
}
