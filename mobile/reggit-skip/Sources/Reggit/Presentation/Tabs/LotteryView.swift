import SkipFuse
import SwiftUI

private enum LotteryTheme {
    static let night = Color(red: 0.07, green: 0.08, blue: 0.09)
    static let wine = Color(red: 0.13, green: 0.14, blue: 0.15)
    static let red = Color(red: 0.42, green: 0.32, blue: 0.20)
    static let redBright = Color(red: 0.64, green: 0.51, blue: 0.31)
    static let gold = Color(red: 0.77, green: 0.65, blue: 0.43)
    static let goldLight = Color(red: 0.93, green: 0.87, blue: 0.72)
    static let cream = Color(red: 0.97, green: 0.96, blue: 0.93)
    static let steel = Color(red: 0.68, green: 0.70, blue: 0.71)
}

struct LotteryView: View {
    @State var viewModel: LotteryViewModel
    let gachaOnly: Bool
    @Environment(\.accessibilityReduceMotion) var reduceMotion
    @Environment(\.brandAccent) var accent

    init(apiClient: any ReggitAPIServing, gachaOnly: Bool = false) {
        _viewModel = State(initialValue: LotteryViewModel(apiClient: apiClient))
        self.gachaOnly = gachaOnly
    }

    var body: some View {
        SkipRefreshing {
            ZStack {
                LotteryBackdrop()
                NavigationStack {
                    Group {
                        if viewModel.isLoading && viewModel.state == nil {
                            LotterySkeletonView()
                        } else if let error = viewModel.errorMessage, viewModel.state == nil {
                            AppEmptyState(
                                title: "あそびを読み込めませんでした",
                                subtitle: error,
                                systemImage: "exclamationmark.triangle",
                                actionTitle: "再読み込み",
                                action: { Task { await viewModel.load() } }
                            )
                            .padding(20)
                            .background(LotteryTheme.cream)
                            .clipShape(RoundedRectangle(cornerRadius: 24, style: .continuous))
                            .padding(20)
                        } else {
                            ScrollView {
                                VStack(spacing: 18) {
                                    if gachaOnly {
                                        gachaModalContent
                                    } else {
                                        arcadeHeader
                                        eligibilityCard
                                        gameSelector
                                        selectedGame
                                            .id(viewModel.selectedGame)
                                            .transition(.opacity)
                                    }
                                }
                                .padding(.horizontal, 14)
                                .padding(.top, gachaOnly ? 46 : 18)
                                .padding(.bottom, 32)
                            }
                            .refreshable { await viewModel.load() }
                        }
                    }
                    .background(Color.clear)
                    .alert(viewModel.alertTitle ?? "お知らせ", isPresented: Binding(
                        get: { viewModel.alertMessage != nil },
                        set: { if !$0 { viewModel.alertMessage = nil; viewModel.alertTitle = nil } }
                    )) {
                        Button("OK", role: .cancel) {}
                    } message: {
                        Text(viewModel.alertMessage ?? "")
                    }
                }
                .background(Color.clear)

                if let result = viewModel.revealedResult {
                    RewardRevealOverlay(result: result) {
                        withAnimation(.easeOut(duration: 0.18)) {
                            viewModel.revealedResult = nil
                            viewModel.opensDroppedCapsule = false
                            viewModel.showsDroppedCapsule = false
                        }
                    }
                    .transition(.opacity)
                    .zIndex(10)
                }

            }
        }
        .task { await viewModel.load() }
    }

    private var gachaModalContent: some View {
        VStack(spacing: 4) {
            GachaMachineView(
                rotation: viewModel.gachaRotation,
                shakeOffset: viewModel.gachaShakeOffset,
                showsDroppedCapsule: viewModel.showsDroppedCapsule,
                opensDroppedCapsule: viewModel.opensDroppedCapsule
            )
            .scaleEffect(0.76)
            .frame(height: 340)
            Button {
                Task { await viewModel.playGacha(reduceMotion: reduceMotion) }
            } label: {
                Image(systemName: "hand.tap.fill")
                    .font(.system(size: 22, weight: .bold))
                    .foregroundStyle(LotteryTheme.night)
                    .frame(width: 64, height: 48)
                    .background(LotteryTheme.goldLight)
                    .clipShape(Capsule())
            }
            .buttonStyle(.plain)
            .disabled(!viewModel.hasEntitlement("gacha") || viewModel.isPlaying)
            .opacity(viewModel.hasEntitlement("gacha") ? 1 : 0.45)
            .accessibilityLabel("ガチャを回す")
        }
    }

    private var arcadeHeader: some View {
        VStack(spacing: 6) {
            Text("購入特典を楽しむ")
                .font(.system(size: 28, weight: .black, design: .rounded))
                .foregroundStyle(LotteryTheme.cream)
            Text("お好きな抽選を選んでください")
                .font(.system(size: 13, weight: .semibold))
                .foregroundStyle(LotteryTheme.cream.opacity(0.68))
                .multilineTextAlignment(.center)
        }
        .frame(maxWidth: .infinity)
    }

    private var eligibilityCard: some View {
        HStack(spacing: 10) {
            Image(systemName: "bag.fill")
                .font(.system(size: 14, weight: .bold))
                .foregroundStyle(accent)
                .frame(width: 30, height: 30)
                .background(Color.white.opacity(0.08))
                .clipShape(Circle())
            Text(viewModel.eligibilityMessage)
                .font(.system(size: 12, weight: .semibold))
                .foregroundStyle(LotteryTheme.cream.opacity(0.82))
            Spacer(minLength: 0)
        }
        .padding(.horizontal, 13)
        .padding(.vertical, 10)
        .background(Color.white.opacity(0.06))
        .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .stroke(Color.white.opacity(0.10), lineWidth: 1)
        }
    }

    private var gameSelector: some View {
        HStack(spacing: 6) {
            ForEach(0..<viewModel.availableGames.count, id: \.self) { index in
                let game = viewModel.availableGames[index]
                let isSelected = viewModel.selectedGame == game
                Button {
                    if reduceMotion {
                        viewModel.selectedGame = game
                    } else {
                        withAnimation(.easeOut(duration: 0.22)) { viewModel.selectedGame = game }
                    }
                } label: {
                    VStack(spacing: 5) {
                        Image(systemName: game.icon)
                            .font(.system(size: 18, weight: .bold))
                        Text(game.title)
                            .font(.system(size: 11, weight: .black))
                    }
                    .foregroundStyle(isSelected ? LotteryTheme.night : LotteryTheme.cream.opacity(0.68))
                    .frame(maxWidth: .infinity)
                    .frame(height: 58)
                    .background(isSelected ? LotteryTheme.cream : Color.white.opacity(0.07))
                    .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
                    .overlay {
                        RoundedRectangle(cornerRadius: 14, style: .continuous)
                            .stroke(isSelected ? accent : Color.white.opacity(0.10), lineWidth: isSelected ? 2 : 1)
                    }
                }
                #if os(Android)
                .buttonStyle(.plain)
                #else
                .buttonStyle(LotteryPressStyle())
                #endif
                .accessibilityLabel(Text(game.title))
            }
        }
        .padding(5)
        .background(Color.black.opacity(0.34))
        .clipShape(RoundedRectangle(cornerRadius: 18, style: .continuous))
    }

    @ViewBuilder
    private var selectedGame: some View {
        switch viewModel.selectedGame {
        case .gacha:
            lotteryStage(
                eyebrow: "ガチャ",
                title: "クーポンガチャ",
                subtitle: "ハンドルを回すと、カプセルが落ちてきます。",
                available: viewModel.hasEntitlement("gacha"),
                status: viewModel.hasEntitlement("gacha") ? "プレイ可" : "権利なし"
            ) {
                GachaMachineView(
                    rotation: viewModel.gachaRotation,
                    shakeOffset: viewModel.gachaShakeOffset,
                    showsDroppedCapsule: viewModel.showsDroppedCapsule,
                    opensDroppedCapsule: viewModel.opensDroppedCapsule
                )
                .scaleEffect(0.70)
                .frame(height: 330)
                prizeStrip(labels: viewModel.state?.gachaPrizeLabels ?? [])
                LotteryActionButton(
                    title: viewModel.hasEntitlement("gacha") ? "ハンドルを回す" : "抽選券がありません",
                    subtitle: viewModel.hasEntitlement("gacha") ? "タップしてスタート" : "購入条件を達成すると遊べます",
                    isLoading: viewModel.isPlaying,
                    enabled: viewModel.hasEntitlement("gacha")
                ) {
                    Task { await viewModel.playGacha(reduceMotion: reduceMotion) }
                }
            }
        case .scratch:
            lotteryStage(
                eyebrow: "スクラッチ",
                title: "ゴールドスクラッチ",
                subtitle: "銀色の面を指でこすって、結果を確認しよう。",
                available: viewModel.canScratchCurrentTicket,
                status: viewModel.scratchResult?.isWin == true ? "あたり" : (viewModel.scratchResult == nil ? (viewModel.hasEntitlement("scratch") ? "プレイ可" : "権利なし") : "はずれ")
            ) {
                ScratchTicketView(
                    progress: viewModel.scratchProgress,
                    scratchedCells: viewModel.scratchedCells,
                    result: viewModel.scratchResult,
                    enabled: viewModel.canScratchCurrentTicket,
                    isResolving: viewModel.isScratchResolving,
                    onScratch: { x, y, width, height in
                        if viewModel.scratch(atX: x, y: y, width: width, height: height) {
                            Task { await viewModel.resolveScratch() }
                        }
                    }
                )
                .scaleEffect(0.84)
                .frame(height: 270)
                scratchProgress
                if viewModel.scratchResult != nil, viewModel.hasEntitlement("scratch") {
                    LotteryActionButton(title: "次のスクラッチを開く", subtitle: "もう一度挑戦") {
                        viewModel.resetScratch()
                    }
                }
            }
        case .roulette:
            lotteryStage(
                eyebrow: "ルーレット",
                title: "ラッキールーレット",
                subtitle: "ルーレットを回して、特典を引き当てよう。",
                available: viewModel.hasEntitlement("roulette"),
                status: viewModel.hasEntitlement("roulette") ? "プレイ可" : "権利なし"
            ) {
                RouletteWheelView(
                    labels: viewModel.rouletteLabels,
                    rotation: viewModel.rouletteRotation
                )
                .scaleEffect(0.76)
                .frame(height: 290)
                LotteryActionButton(
                    title: viewModel.hasEntitlement("roulette") ? "ルーレットを回す" : "抽選券がありません",
                    subtitle: viewModel.hasEntitlement("roulette") ? "タップしてスタート" : "購入条件を達成すると遊べます",
                    isLoading: viewModel.isPlaying,
                    enabled: viewModel.hasEntitlement("roulette")
                ) {
                    Task { await viewModel.playRoulette(reduceMotion: reduceMotion) }
                }
                Text("抽選結果に応じて、店舗で利用できるクーポンが付与されます")
                    .font(.system(size: 11, weight: .medium))
                    .foregroundStyle(LotteryTheme.cream.opacity(0.48))
                    .frame(maxWidth: .infinity)
            }
        }
    }

    private var scratchProgress: some View {
        VStack(spacing: 7) {
            HStack {
                Text("削り進み")
                    .font(.system(size: 9, weight: .black))
                    .tracking(1.2)
                Spacer()
                Text("\(Int(viewModel.scratchProgress * 100))%")
                    .font(.system(size: 12, weight: .black, design: .rounded))
            }
            .foregroundStyle(LotteryTheme.cream.opacity(0.72))
            GeometryReader { proxy in
                ZStack(alignment: .leading) {
                    Capsule().fill(Color.white.opacity(0.10))
                    Capsule()
                        .fill(LinearGradient(colors: [LotteryTheme.gold, LotteryTheme.goldLight], startPoint: .leading, endPoint: .trailing))
                        .frame(width: max(8, proxy.size.width * CGFloat(viewModel.scratchProgress)))
                }
            }
            .frame(height: 8)
        }
    }

    private func prizeStrip(labels: [String]) -> some View {
        let visible = labels.isEmpty ? ["ドリンク", "デザート", "限定クーポン"] : Array(labels.prefix(3))
        return VStack(alignment: .leading, spacing: 8) {
            Text("景品の例")
                .font(.system(size: 9, weight: .black))
                .tracking(1.4)
                .foregroundStyle(LotteryTheme.cream.opacity(0.48))
            HStack(spacing: 7) {
                ForEach(visible, id: \.self) { label in
                    Text(label)
                        .font(.system(size: 11, weight: .bold))
                        .foregroundStyle(LotteryTheme.cream)
                        .lineLimit(1)
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 9)
                        .background(Color.white.opacity(0.07))
                        .clipShape(RoundedRectangle(cornerRadius: 9, style: .continuous))
                }
            }
        }
    }

    private func lotteryStage<Content: View>(
        eyebrow: String,
        title: String,
        subtitle: String,
        available: Bool,
        status: String,
        @ViewBuilder content: () -> Content
    ) -> some View {
        VStack(spacing: 16) {
            HStack(alignment: .top, spacing: 12) {
                VStack(alignment: .leading, spacing: 5) {
                    Text(eyebrow)
                        .font(.system(size: 9, weight: .black))
                        .tracking(1.25)
                        .foregroundStyle(LotteryTheme.gold)
                    Text(title)
                        .font(.system(size: 23, weight: .black, design: .rounded))
                        .foregroundStyle(LotteryTheme.cream)
                    Text(subtitle)
                        .font(.system(size: 13, weight: .medium))
                        .foregroundStyle(LotteryTheme.cream.opacity(0.62))
                        .fixedSize(horizontal: false, vertical: true)
                }
                Spacer(minLength: 6)
                Text(status)
                    .font(.system(size: 9, weight: .black))
                    .tracking(0.8)
                    .foregroundStyle(available ? LotteryTheme.night : LotteryTheme.cream.opacity(0.58))
                    .padding(.horizontal, 10)
                    .padding(.vertical, 7)
                    .background(available ? LotteryTheme.goldLight : Color.white.opacity(0.09))
                    .clipShape(Capsule())
            }
            content()
        }
        .padding(16)
        .background {
            RoundedRectangle(cornerRadius: 28, style: .continuous)
                .fill(LinearGradient(colors: [Color.white.opacity(0.085), Color.black.opacity(0.28)], startPoint: .topLeading, endPoint: .bottomTrailing))
        }
        .overlay {
            RoundedRectangle(cornerRadius: 28, style: .continuous)
                .stroke(LinearGradient(colors: [Color.white.opacity(0.18), LotteryTheme.gold.opacity(0.25), Color.clear], startPoint: .topLeading, endPoint: .bottomTrailing), lineWidth: 1)
        }
    }
}

struct LotteryBackdrop: View {
    var body: some View {
        ZStack {
            LinearGradient(colors: [LotteryTheme.wine, LotteryTheme.night, Color.black], startPoint: .topLeading, endPoint: .bottomTrailing)
            Circle().fill(LotteryTheme.red.opacity(0.16)).frame(width: 310, height: 310).offset(x: 130, y: -260).blur(radius: 24)
            Circle().fill(LotteryTheme.gold.opacity(0.08)).frame(width: 250, height: 250).offset(x: -150, y: 330).blur(radius: 30)
            ForEach(0 ..< 18, id: \.self) { index in
                Circle()
                    .fill(index % 3 == 0 ? LotteryTheme.gold : Color.white)
                    .frame(width: index % 4 == 0 ? 3 : 1.5, height: index % 4 == 0 ? 3 : 1.5)
                    .opacity(index % 3 == 0 ? 0.35 : 0.18)
                    .offset(x: CGFloat((index * 67) % 330) - 165, y: CGFloat((index * 103) % 720) - 360)
            }
        }
        .ignoresSafeArea()
    }
}

struct LotteryActionButton: View {
    let title: String
    let subtitle: String
    var isLoading = false
    var enabled = true
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            HStack(spacing: 12) {
                ZStack {
                    Circle().fill(Color.black.opacity(0.14)).frame(width: 38, height: 38)
                    if isLoading {
                        ProgressView().tint(LotteryTheme.night)
                    } else {
                        Image(systemName: "play.fill")
                            .font(.system(size: 13, weight: .black))
                    }
                }
                VStack(alignment: .leading, spacing: 1) {
                    Text(title).font(.system(size: 16, weight: .black, design: .rounded))
                    Text(subtitle).font(.system(size: 9, weight: .black)).tracking(1.2).opacity(0.64)
                }
                Spacer()
                Image(systemName: "chevron.right")
                    .font(.system(size: 14, weight: .black))
            }
            .foregroundStyle(enabled ? LotteryTheme.night : LotteryTheme.cream.opacity(0.55))
            .padding(.horizontal, 13)
            .frame(maxWidth: .infinity)
            .frame(minHeight: 62)
            .background {
                RoundedRectangle(cornerRadius: 17, style: .continuous)
                    .fill(enabled ? LinearGradient(colors: [LotteryTheme.goldLight, LotteryTheme.gold], startPoint: .top, endPoint: .bottom) : LinearGradient(colors: [Color.white.opacity(0.10), Color.white.opacity(0.07)], startPoint: .top, endPoint: .bottom))
            }
            .overlay {
                RoundedRectangle(cornerRadius: 17, style: .continuous)
                    .stroke(enabled ? Color.white.opacity(0.55) : Color.white.opacity(0.08), lineWidth: 1)
            }
        }
        #if os(Android)
        .buttonStyle(.plain)
        #else
        .buttonStyle(LotteryPressStyle())
        #endif
        .disabled(!enabled || isLoading)
    }
}

struct LotteryPressStyle: ButtonStyle {
    func makeBody(configuration: Configuration) -> some View {
        configuration.label
            .scaleEffect(configuration.isPressed ? 0.97 : 1)
            .opacity(configuration.isPressed ? 0.92 : 1)
            .animation(.easeOut(duration: 0.12), value: configuration.isPressed)
    }
}

struct GachaMachineView: View {
    let rotation: Double
    let shakeOffset: Double
    let showsDroppedCapsule: Bool
    let opensDroppedCapsule: Bool

    private let capsuleColors: [Color] = [
        Color(red: 0.62, green: 0.35, blue: 0.30), Color(red: 0.25, green: 0.48, blue: 0.61), LotteryTheme.gold,
        Color(red: 0.32, green: 0.52, blue: 0.42), Color(red: 0.48, green: 0.40, blue: 0.58),
        Color(red: 0.66, green: 0.43, blue: 0.48), Color(red: 0.25, green: 0.54, blue: 0.54)
    ]

    var body: some View {
        ZStack(alignment: .bottom) {
            Ellipse()
                .fill(Color.black.opacity(0.55))
                .frame(width: 260, height: 32)
                .offset(y: 5)
                .blur(radius: 5)

            VStack(spacing: -8) {
                ZStack(alignment: .top) {
                    Capsule()
                        .fill(LinearGradient(colors: [Color.white, Color(white: 0.72)], startPoint: .top, endPoint: .bottom))
                        .frame(width: 104, height: 42)
                        .overlay(Capsule().stroke(Color.white.opacity(0.8), lineWidth: 2))
                        .shadow(color: Color.black.opacity(0.18), radius: 6, x: 0, y: 5)
                        .offset(y: -14)

                    ZStack {
                        Circle()
                            .fill(RadialGradient(
                                colors: [
                                    Color.white.opacity(0.50),
                                    Color(red: 0.73, green: 0.88, blue: 0.95).opacity(0.24),
                                    Color(red: 0.24, green: 0.36, blue: 0.43).opacity(0.32),
                                ],
                                center: UnitPoint(x: 0.34, y: 0.24),
                                startRadius: 6,
                                endRadius: 158
                            ))
                        ZStack {
                            ForEach(0 ..< 10, id: \.self) { index in
                                CapsuleBall(color: capsuleColors[index % capsuleColors.count])
                                    .frame(width: capsuleSize(index), height: capsuleSize(index))
                                    .rotationEffect(.degrees(capsuleAngle(index) + rotation * 0.018))
                                    .offset(x: capsuleX(index), y: capsuleY(index))
                            }
                        }
                        GachaDomeReflection()
                    }
                    .frame(width: 236, height: 236)
                    .clipShape(Circle())
                    .overlay {
                        Circle().stroke(Color.white.opacity(0.68), lineWidth: 3).padding(10)
                        Circle().stroke(LinearGradient(colors: [Color.white, Color(white: 0.66), Color(white: 0.92)], startPoint: .top, endPoint: .bottom), lineWidth: 9)
                    }
                    .shadow(color: Color.black.opacity(0.22), radius: 13, x: 0, y: 10)

                    Text("1回")
                        .font(.system(size: 9, weight: .black))
                        .tracking(1)
                        .foregroundStyle(Color(red: 0.19, green: 0.20, blue: 0.23))
                        .padding(.horizontal, 13)
                        .padding(.vertical, 7)
                        .background(Color.white.opacity(0.92))
                        .clipShape(Capsule())
                        .shadow(color: Color.black.opacity(0.14), radius: 4, x: 0, y: 3)
                        .offset(x: 70, y: 16)
                }
                .frame(width: 260, height: 240)

                Capsule()
                    .fill(LinearGradient(colors: [Color.white, Color(white: 0.72), Color(white: 0.50)], startPoint: .top, endPoint: .bottom))
                    .frame(width: 224, height: 25)
                    .overlay(Capsule().stroke(Color.white.opacity(0.78), lineWidth: 2))
                    .shadow(color: Color.black.opacity(0.22), radius: 5, x: 0, y: 5)

                ZStack {
                    RoundedRectangle(cornerRadius: 25, style: .continuous)
                        .fill(LinearGradient(
                            colors: [Color.white, Color(white: 0.94), Color(white: 0.72), Color(white: 0.86)],
                            startPoint: .topLeading,
                            endPoint: .bottomTrailing
                        ))
                        .frame(width: 244, height: 176)
                    RoundedRectangle(cornerRadius: 21, style: .continuous)
                        .stroke(Color.white.opacity(0.88), lineWidth: 2)
                        .frame(width: 232, height: 164)
                    Capsule().fill(Color.black.opacity(0.08)).frame(width: 7, height: 132).offset(x: -104)
                    Capsule()
                        .fill(LinearGradient(colors: [Color.white.opacity(0.72), Color.white.opacity(0.04)], startPoint: .top, endPoint: .bottom))
                        .frame(width: 8, height: 118)
                        .offset(x: 99, y: -4)

                    VStack(spacing: 1) {
                        Text("クーポン")
                            .font(.system(size: 8, weight: .black))
                        Text("ガチャ")
                            .font(.system(size: 15, weight: .black, design: .rounded))
                    }
                    .foregroundStyle(LotteryTheme.night.opacity(0.78))
                    .offset(y: -62)

                    ZStack {
                        RoundedRectangle(cornerRadius: 8, style: .continuous)
                            .fill(LinearGradient(colors: [Color(red: 0.22, green: 0.45, blue: 0.76), Color(red: 0.04, green: 0.20, blue: 0.42)], startPoint: .top, endPoint: .bottom))
                            .frame(width: 50, height: 59)
                        Text("投入口")
                            .font(.system(size: 8, weight: .black))
                            .multilineTextAlignment(.center)
                            .foregroundStyle(Color.white)
                            .offset(y: -11)
                        Capsule().fill(Color.black.opacity(0.76)).frame(width: 26, height: 5).offset(y: 15)
                    }
                    .overlay(RoundedRectangle(cornerRadius: 8).stroke(Color.white.opacity(0.72), lineWidth: 1.5))
                    .offset(x: -76, y: -8)

                    ZStack {
                        Circle()
                            .fill(LinearGradient(colors: [Color.white, Color(white: 0.68), Color(white: 0.36)], startPoint: .topLeading, endPoint: .bottomTrailing))
                            .frame(width: 82, height: 82)
                        ForEach(0 ..< 12, id: \.self) { index in
                            Capsule()
                                .fill(index % 2 == 0 ? Color.white.opacity(0.92) : Color.black.opacity(0.16))
                                .frame(width: 4, height: 9)
                                .offset(y: -33)
                                .rotationEffect(.degrees(Double(index) * 30))
                        }
                        Circle()
                            .fill(LinearGradient(colors: [Color(red: 1.00, green: 0.84, blue: 0.20), Color(red: 0.94, green: 0.54, blue: 0.04)], startPoint: .top, endPoint: .bottom))
                            .frame(width: 60, height: 60)
                        Capsule().fill(Color(red: 0.05, green: 0.28, blue: 0.56)).frame(width: 52, height: 14)
                        Circle().fill(Color.white.opacity(0.94)).frame(width: 13, height: 13).offset(x: 21)
                    }
                    .rotationEffect(.degrees(rotation))
                    .shadow(color: Color.black.opacity(0.32), radius: 5, x: 0, y: 6)
                    .offset(x: 2, y: 0)

                    ZStack {
                        RoundedRectangle(cornerRadius: 12, style: .continuous)
                            .fill(Color(red: 0.07, green: 0.08, blue: 0.10))
                            .frame(width: 64, height: 64)
                        RoundedRectangle(cornerRadius: 9, style: .continuous)
                            .fill(LinearGradient(colors: [Color.black, Color(white: 0.16)], startPoint: .top, endPoint: .bottom))
                            .frame(width: 50, height: 42)
                        RoundedRectangle(cornerRadius: 7, style: .continuous)
                            .stroke(Color.white.opacity(0.10), lineWidth: 1)
                            .frame(width: 44, height: 31)
                        OpenedCapsuleView(isOpen: opensDroppedCapsule)
                            .scaleEffect(0.63)
                            .offset(y: showsDroppedCapsule ? (opensDroppedCapsule ? 1 : -7) : -58)
                            .opacity(showsDroppedCapsule ? 1 : 0)
                    }
                    .overlay(RoundedRectangle(cornerRadius: 12).stroke(Color(white: 0.44), lineWidth: 3))
                    .offset(x: 77, y: 30)

                    ForEach(0 ..< 4, id: \.self) { index in
                        Circle()
                            .fill(LinearGradient(colors: [Color.white, Color(white: 0.38)], startPoint: .topLeading, endPoint: .bottomTrailing))
                            .frame(width: 7, height: 7)
                            .offset(x: index % 2 == 0 ? -105 : 105, y: index < 2 ? -67 : 67)
                    }
                }

                ZStack {
                    Capsule()
                        .fill(LinearGradient(colors: [Color.white, Color(white: 0.64)], startPoint: .top, endPoint: .bottom))
                        .frame(width: 150, height: 19)
                    Capsule().fill(Color.black.opacity(0.30)).frame(width: 108, height: 5).offset(y: -2)
                }
                .shadow(color: Color.black.opacity(0.24), radius: 4, x: 0, y: 4)
                .offset(y: -2)
            }
            .offset(x: CGFloat(shakeOffset), y: -4)

            if opensDroppedCapsule {
                ZStack {
                    Circle().fill(LotteryTheme.goldLight.opacity(0.17)).frame(width: 108, height: 108).blur(radius: 9)
                    Capsule().fill(LotteryTheme.goldLight.opacity(0.20)).frame(width: 13, height: 105).offset(y: -42)
                    Image(systemName: "star.fill")
                        .font(.system(size: 27, weight: .black))
                        .foregroundStyle(LotteryTheme.goldLight)
                }
                .offset(x: 76, y: -85)
            }
        }
        .frame(maxWidth: .infinity)
        .frame(height: 462)
        .accessibilityLabel("透明ケースにカプセルが入った白いガシャポンマシン")
    }

    private func capsuleX(_ index: Int) -> CGFloat {
        let values: [CGFloat] = [-70, -24, 27, 70, -52, 0, 53, -27, 29, 1]
        return values[index % values.count]
    }

    private func capsuleY(_ index: Int) -> CGFloat {
        let values: [CGFloat] = [76, 80, 73, 78, 37, 40, 37, -3, -1, -40]
        return values[index % values.count]
    }

    private func capsuleSize(_ index: Int) -> CGFloat {
        let values: [CGFloat] = [48, 52, 49, 46, 48, 53, 47, 45, 44, 42]
        return values[index % values.count]
    }

    private func capsuleAngle(_ index: Int) -> Double {
        let values: [Double] = [-14, 8, -6, 16, 19, -13, 10, -9, 14, -4]
        return values[index % values.count]
    }
}

struct GachaDomeReflection: View {
    var body: some View {
        ZStack {
            Ellipse()
                .fill(LinearGradient(
                    colors: [Color.white.opacity(0.62), Color.white.opacity(0.18), Color.clear],
                    startPoint: .leading,
                    endPoint: .trailing
                ))
                .frame(width: 118, height: 31)
                .rotationEffect(.degrees(-25))
                .offset(x: -34, y: -72)
                .blur(radius: 0.6)
            Capsule()
                .fill(LinearGradient(
                    colors: [Color.white.opacity(0.68), Color.white.opacity(0.12), Color.clear],
                    startPoint: .top,
                    endPoint: .bottom
                ))
                .frame(width: 17, height: 92)
                .rotationEffect(.degrees(23))
                .offset(x: -71, y: -24)
                .blur(radius: 0.5)
            Ellipse()
                .stroke(Color.white.opacity(0.22), lineWidth: 5)
                .frame(width: 148, height: 45)
                .offset(x: 24, y: 83)
                .blur(radius: 1.2)
            Circle()
                .fill(Color.white.opacity(0.72))
                .frame(width: 8, height: 8)
                .offset(x: 61, y: -67)
                .blur(radius: 0.4)
        }
        .allowsHitTesting(false)
    }
}

struct CapsuleBall: View {
    let color: Color

    var body: some View {
        GeometryReader { proxy in
            let size = min(proxy.size.width, proxy.size.height)
            ZStack {
                Circle()
                    .fill(RadialGradient(
                        colors: [Color.white.opacity(0.84), Color(red: 0.78, green: 0.88, blue: 0.94).opacity(0.48), Color.black.opacity(0.20)],
                        center: UnitPoint(x: 0.30, y: 0.22),
                        startRadius: 1,
                        endRadius: size * 0.72
                    ))
                Rectangle()
                    .fill(LinearGradient(colors: [color.opacity(0.92), color, color.opacity(0.72)], startPoint: .top, endPoint: .bottom))
                    .frame(width: size, height: size * 0.51)
                    .offset(y: size * 0.245)
                Capsule()
                    .fill(Color.black.opacity(0.30))
                    .frame(width: size * 0.88, height: max(2, size * 0.09))
                Capsule()
                    .fill(Color.white.opacity(0.62))
                    .frame(width: size * 0.72, height: max(1, size * 0.035))
                    .offset(y: -size * 0.055)
                Ellipse()
                    .fill(Color.white.opacity(0.78))
                    .frame(width: size * 0.24, height: size * 0.15)
                    .offset(x: -size * 0.20, y: -size * 0.22)
                    .blur(radius: 0.4)
                Circle().stroke(Color.black.opacity(0.34), lineWidth: 1.2)
            }
            .clipShape(Circle())
            .shadow(color: Color.black.opacity(0.24), radius: 3, x: 0, y: 2)
        }
    }
}

struct OpenedCapsuleView: View {
    let isOpen: Bool

    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .fill(LinearGradient(colors: [LotteryTheme.goldLight, LotteryTheme.gold], startPoint: .topLeading, endPoint: .bottomTrailing))
                .frame(width: 62, height: 32)
                .overlay(RoundedRectangle(cornerRadius: 18).stroke(Color.black.opacity(0.34), lineWidth: 1.5))
                .offset(y: isOpen ? -8 : -15)
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .fill(LinearGradient(colors: [LotteryTheme.cream, Color(white: 0.80)], startPoint: .topLeading, endPoint: .bottomTrailing))
                .frame(width: 62, height: 32)
                .overlay(RoundedRectangle(cornerRadius: 18).stroke(Color.black.opacity(0.34), lineWidth: 1.5))
                .offset(y: isOpen ? 34 : 15)
            Capsule().fill(Color.white.opacity(0.88)).frame(width: 62, height: 5).offset(y: isOpen ? 13 : 0)
        }
        .frame(width: 76, height: 84)
        .shadow(color: Color.black.opacity(0.32), radius: 4, x: 0, y: 3)
    }
}

struct ScratchTicketView: View {
    let progress: Double
    let scratchedCells: [Int]
    let result: LotteryPlayResult?
    let enabled: Bool
    let isResolving: Bool
    let onScratch: (CGFloat, CGFloat, CGFloat, CGFloat) -> Void

    private let surfaceWidth: CGFloat = 264
    private let surfaceHeight: CGFloat = 158

    var body: some View {
        VStack(spacing: 12) {
            ZStack {
                RoundedRectangle(cornerRadius: 22, style: .continuous)
                    .fill(LinearGradient(
                        colors: [Color(red: 1.00, green: 0.88, blue: 0.38), LotteryTheme.gold, Color(red: 0.66, green: 0.32, blue: 0.03)],
                        startPoint: .topLeading,
                        endPoint: .bottomTrailing
                    ))
                    .frame(width: 304, height: 286)
                    .shadow(color: Color.black.opacity(0.42), radius: 15, x: 0, y: 11)
                RoundedRectangle(cornerRadius: 17, style: .continuous)
                    .stroke(Color.white.opacity(0.62), style: StrokeStyle(lineWidth: 1.5, dash: [7, 5]))
                    .frame(width: 286, height: 268)

                VStack(spacing: 14) {
                    HStack(spacing: 10) {
                        ZStack {
                            Circle().fill(LotteryTheme.red).frame(width: 38, height: 38)
                            Image(systemName: "sparkles")
                                .font(.system(size: 16, weight: .black))
                                .foregroundStyle(Color.white)
                        }
                        VStack(alignment: .leading, spacing: 1) {
                            Text("ゴールドスクラッチ")
                                .font(.system(size: 17, weight: .black, design: .rounded))
                        }
                        Spacer()
                        Text("挑戦")
                            .font(.system(size: 8, weight: .black))
                            .padding(.horizontal, 9)
                            .padding(.vertical, 6)
                            .overlay(RoundedRectangle(cornerRadius: 6).stroke(LotteryTheme.night.opacity(0.45), lineWidth: 1))
                    }
                    .foregroundStyle(LotteryTheme.night)

                    ZStack {
                        ScratchResultFace(result: result, isResolving: isResolving)
                        ScratchFoilSurface()
                            .mask(ScratchFoilMask(scratchedCells: scratchedCells))
                        if progress < 0.18 {
                            VStack(spacing: 4) {
                                Image(systemName: "hand.draw.fill")
                                    .font(.system(size: 21, weight: .bold))
                                Text("銀面をこすって結果を確認")
                                    .font(.system(size: 13, weight: .black, design: .rounded))
                            }
                            .foregroundStyle(Color.black.opacity(0.48))
                            .opacity(max(0, 1 - progress * 5.5))
                            .allowsHitTesting(false)
                        }
                    }
                    .frame(width: surfaceWidth, height: surfaceHeight)
                    .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
                    .overlay {
                        RoundedRectangle(cornerRadius: 16, style: .continuous)
                            .stroke(Color.white.opacity(0.82), lineWidth: 3)
                    }
                    .shadow(color: Color.black.opacity(0.24), radius: 9, x: 0, y: 7)
                    .gesture(
                        DragGesture(minimumDistance: 0)
                            .onChanged { value in
                                guard enabled else { return }
                                onScratch(value.location.x, value.location.y, surfaceWidth, surfaceHeight)
                            }
                    )
                }
                .frame(width: 270)

                if !enabled {
                    RoundedRectangle(cornerRadius: 22, style: .continuous)
                        .fill(Color.black.opacity(0.72))
                        .frame(width: 304, height: 286)
                    VStack(spacing: 8) {
                        Image(systemName: "lock.fill").font(.title2)
                        Text("スクラッチ券がありません").font(.headline.weight(.black))
                    }
                    .foregroundStyle(Color.white)
                }
            }
            Text(result == nil
                ? (progress == 0 ? "一枚の銀面を指でこすってください" : "結果を確認しています…")
                : (result?.isWin == true ? "あたり！景品内容を確認してください" : "今回はハズレです"))
                .font(.system(size: 12, weight: .bold))
                .foregroundStyle(LotteryTheme.cream.opacity(0.72))
        }
        .frame(maxWidth: .infinity)
        .accessibilityLabel("一面スクラッチ")
        .accessibilityValue("\(Int(progress * 100))パーセント削りました")
    }
}

struct ScratchResultFace: View {
    let result: LotteryPlayResult?
    let isResolving: Bool

    var body: some View {
        RoundedRectangle(cornerRadius: 16, style: .continuous)
            .fill(LinearGradient(
                colors: [Color.white, LotteryTheme.cream, Color(red: 0.88, green: 0.83, blue: 0.72)],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            ))
            .overlay {
                VStack(spacing: 7) {
                    if isResolving && result == nil {
                        ProgressView().tint(LotteryTheme.red)
                        Text("抽選中").font(.system(size: 12, weight: .black)).tracking(1.4)
                    } else {
                        Image(systemName: result?.isWin == true ? "gift.fill" : (result == nil ? "questionmark" : "xmark"))
                            .font(.system(size: 29, weight: .black))
                            .foregroundStyle(result?.isWin == true ? LotteryTheme.red : LotteryTheme.night.opacity(0.72))
                        Text(result?.isWin == true ? "あたり" : (result == nil ? "結果" : "はずれ"))
                            .font(.system(size: 22, weight: .black, design: .rounded))
                        Text(result?.coupon?.name ?? (result == nil ? "この下に結果があります" : "またチャレンジしてね"))
                            .font(.system(size: 14, weight: .bold, design: .rounded))
                            .multilineTextAlignment(.center)
                            .lineLimit(2)
                            .padding(.horizontal, 18)
                    }
                }
                .foregroundStyle(LotteryTheme.night)
            }
    }
}

struct ScratchFoilSurface: View {
    var body: some View {
        RoundedRectangle(cornerRadius: 16, style: .continuous)
            .fill(LinearGradient(
                colors: [Color(white: 0.48), Color(white: 0.96), Color(white: 0.62), Color(white: 0.90), Color(white: 0.42)],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            ))
            .overlay {
                LinearGradient(
                    colors: [Color.clear, Color.white.opacity(0.55), Color.clear, Color.black.opacity(0.16)],
                    startPoint: .leading,
                    endPoint: .trailing
                )
            }
            .overlay {
                VStack(spacing: 16) {
                    ForEach(0 ..< 7, id: \.self) { index in
                        Capsule()
                            .fill(Color.white.opacity(index % 2 == 0 ? 0.19 : 0.09))
                            .frame(height: 1)
                            .rotationEffect(.degrees(index % 2 == 0 ? -4 : 3))
                    }
                }
                .padding(.horizontal, 8)
            }
    }
}

struct ScratchFoilMask: Shape {
    let scratchedCells: [Int]

    func path(in rect: CGRect) -> Path {
        var path = Path()
        let cellWidth = rect.width / CGFloat(ScratchRevealEngine.columns)
        let cellHeight = rect.height / CGFloat(ScratchRevealEngine.rows)
        for index in 0 ..< ScratchRevealEngine.cellCount {
            guard !scratchedCells.contains(index) else { continue }
            let column = index % ScratchRevealEngine.columns
            let row = index / ScratchRevealEngine.columns
            path.addRect(CGRect(
                x: CGFloat(column) * cellWidth - 0.5,
                y: CGFloat(row) * cellHeight - 0.5,
                width: cellWidth + 1,
                height: cellHeight + 1
            ))
        }
        return path
    }
}

struct RouletteWheelView: View {
    let labels: [String]
    let rotation: Double

    var body: some View {
        VStack(spacing: 0) {
            ZStack(alignment: .top) {
                Circle()
                    .fill(LotteryTheme.gold.opacity(0.16))
                    .frame(width: 324, height: 324)
                    .blur(radius: 14)

                Circle()
                    .fill(Color.black.opacity(0.52))
                    .frame(width: 300, height: 300)
                    .offset(y: 10)
                    .blur(radius: 7)

                ZStack {
                    Circle()
                        .fill(AngularGradient(colors: [Color(red: 0.39, green: 0.24, blue: 0.04), LotteryTheme.goldLight, Color(red: 0.48, green: 0.30, blue: 0.05), LotteryTheme.gold, Color(red: 0.34, green: 0.20, blue: 0.03)], center: .center))
                    Circle().stroke(Color.black.opacity(0.34), lineWidth: 3).padding(2)

                    ZStack {
                        Circle()
                            .fill(RadialGradient(
                                colors: [Color(red: 0.32, green: 0.05, blue: 0.06), Color(red: 0.10, green: 0.01, blue: 0.02)],
                                center: .center,
                                startRadius: 30,
                                endRadius: 142
                            ))
                        ForEach(0 ..< 8, id: \.self) { index in
                            Circle()
                                .trim(from: Double(index) / 8.0, to: Double(index + 1) / 8.0)
                                .stroke(rouletteColor(index), style: StrokeStyle(lineWidth: 92, lineCap: .butt))
                                .frame(width: 104, height: 104)
                                .rotationEffect(.degrees(-90))
                            VStack {
                                Text(labels[index])
                                    .font(.system(size: 10, weight: .black, design: .rounded))
                                    .foregroundStyle(Color.white)
                                    .lineLimit(2)
                                    .multilineTextAlignment(.center)
                                    .frame(width: 66)
                                    .shadow(color: Color.black.opacity(0.65), radius: 2, x: 0, y: 1)
                                Spacer()
                            }
                            .frame(width: 236, height: 236)
                            .padding(.top, 23)
                            .rotationEffect(.degrees(Double(index) * 45 + 22.5))
                        }
                    }
                    .frame(width: 256, height: 256)
                    .rotationEffect(.degrees(rotation))

                    Circle()
                        .stroke(LinearGradient(
                            colors: [Color.white.opacity(0.72), LotteryTheme.gold.opacity(0.28), Color.black.opacity(0.48), Color.white.opacity(0.34)],
                            startPoint: .topLeading,
                            endPoint: .bottomTrailing
                        ), lineWidth: 5)
                        .frame(width: 251, height: 251)

                    RouletteGlassReflection()
                        .frame(width: 244, height: 244)

                    ForEach(0 ..< 18, id: \.self) { index in
                        Circle()
                            .fill(index % 3 == 0 ? Color.white : LotteryTheme.goldLight)
                            .frame(width: 9, height: 9)
                            .overlay(Circle().stroke(Color(red: 0.46, green: 0.26, blue: 0.03), lineWidth: 1))
                            .shadow(color: LotteryTheme.goldLight.opacity(0.9), radius: index % 2 == 0 ? 5 : 2)
                            .offset(y: -137)
                            .rotationEffect(.degrees(Double(index) * 20))
                    }

                    Circle()
                        .fill(RadialGradient(
                            colors: [Color.white.opacity(0.94), LotteryTheme.goldLight, LotteryTheme.gold, Color(red: 0.39, green: 0.18, blue: 0.02)],
                            center: UnitPoint(x: 0.30, y: 0.24),
                            startRadius: 1,
                            endRadius: 47
                        ))
                        .frame(width: 68, height: 68)
                        .shadow(color: Color.black.opacity(0.34), radius: 7, x: 0, y: 5)
                    Circle().stroke(Color.white.opacity(0.78), lineWidth: 3).frame(width: 56, height: 56)
                    Circle().fill(Color(red: 0.20, green: 0.12, blue: 0.03)).frame(width: 25, height: 25)
                    Circle().fill(Color.white.opacity(0.38)).frame(width: 7, height: 7).offset(x: -5, y: -6)
                }
                .frame(width: 296, height: 296)

                ZStack {
                    Image(systemName: "triangle.fill")
                        .font(.system(size: 42, weight: .black))
                        .rotationEffect(.degrees(180))
                        .foregroundStyle(LotteryTheme.goldLight)
                        .shadow(color: Color.black.opacity(0.44), radius: 4, x: 0, y: 4)
                    Image(systemName: "circle.fill").font(.system(size: 9)).foregroundStyle(LotteryTheme.red).offset(y: -7)
                }
                .offset(y: -13)
                .zIndex(4)
            }
            .frame(height: 310)

            ZStack(alignment: .top) {
                TrapezoidStand()
                    .fill(LinearGradient(colors: [Color(white: 0.28), Color(white: 0.13), Color(white: 0.06)], startPoint: .top, endPoint: .bottom))
                    .frame(width: 214, height: 64)
                    .overlay(TrapezoidStand().stroke(LotteryTheme.gold.opacity(0.72), lineWidth: 2))
                    .overlay {
                        TrapezoidStand()
                            .fill(LinearGradient(
                                colors: [Color.white.opacity(0.24), Color.clear, Color.black.opacity(0.24)],
                                startPoint: .topLeading,
                                endPoint: .bottomTrailing
                            ))
                            .padding(3)
                    }
                VStack(spacing: 2) {
                    Text("ルーレット").font(.system(size: 12, weight: .black, design: .rounded))
                    Text("回して挑戦").font(.system(size: 7, weight: .black)).opacity(0.68)
                }
                .foregroundStyle(LotteryTheme.cream)
                .padding(.top, 18)
            }
            Capsule().fill(Color.black.opacity(0.34)).frame(width: 190, height: 12).blur(radius: 4).offset(y: -3)
        }
        .frame(maxWidth: .infinity)
        .accessibilityLabel("8つの景品が並ぶラッキールーレット")
    }

    private func rouletteColor(_ index: Int) -> Color {
        let colors: [Color] = [
            Color(red: 0.18, green: 0.20, blue: 0.22),
            Color(red: 0.72, green: 0.62, blue: 0.43),
            Color(red: 0.32, green: 0.40, blue: 0.43),
            Color(red: 0.73, green: 0.72, blue: 0.67),
        ]
        return colors[index % colors.count]
    }
}

struct RouletteGlassReflection: View {
    var body: some View {
        ZStack {
            Circle()
                .fill(RadialGradient(
                    colors: [Color.clear, Color.clear, Color.black.opacity(0.16)],
                    center: UnitPoint(x: 0.42, y: 0.36),
                    startRadius: 70,
                    endRadius: 155
                ))
            Ellipse()
                .fill(LinearGradient(
                    colors: [Color.white.opacity(0.48), Color.white.opacity(0.12), Color.clear],
                    startPoint: .top,
                    endPoint: .bottom
                ))
                .frame(width: 164, height: 42)
                .rotationEffect(.degrees(-24))
                .offset(x: -28, y: -60)
                .blur(radius: 0.8)
            Capsule()
                .fill(LinearGradient(
                    colors: [Color.white.opacity(0.38), Color.clear],
                    startPoint: .leading,
                    endPoint: .trailing
                ))
                .frame(width: 16, height: 78)
                .rotationEffect(.degrees(30))
                .offset(x: -79, y: -17)
                .blur(radius: 0.6)
            Ellipse()
                .stroke(Color.white.opacity(0.14), lineWidth: 4)
                .frame(width: 148, height: 34)
                .offset(x: 27, y: 81)
                .blur(radius: 1)
        }
        .clipShape(Circle())
        .allowsHitTesting(false)
    }
}

struct TrapezoidStand: Shape {
    func path(in rect: CGRect) -> Path {
        var path = Path()
        path.move(to: CGPoint(x: rect.width * 0.18, y: 0))
        path.addLine(to: CGPoint(x: rect.width * 0.82, y: 0))
        path.addLine(to: CGPoint(x: rect.width, y: rect.height))
        path.addLine(to: CGPoint(x: 0, y: rect.height))
        path.closeSubpath()
        return path
    }
}

struct RewardRevealOverlay: View {
    let result: LotteryPlayResult
    let onClose: () -> Void

    var body: some View {
        ZStack {
            Color.black.opacity(0.88).ignoresSafeArea()
            ForEach(0 ..< 14, id: \.self) { index in
                Capsule()
                    .fill(result.isWin ? LotteryTheme.gold.opacity(0.16) : Color.white.opacity(0.05))
                    .frame(width: 8, height: 240)
                    .offset(y: -170)
                    .rotationEffect(.degrees(Double(index) * 25.7))
            }
            VStack(spacing: 18) {
                Text(result.isWin ? "あたり！" : "ざんねん")
                    .font(.system(size: 12, weight: .black)).tracking(3)
                    .foregroundStyle(result.isWin ? LotteryTheme.goldLight : Color.white.opacity(0.64))
                ZStack {
                    Circle().fill(result.isWin ? LotteryTheme.gold.opacity(0.18) : Color.white.opacity(0.08)).frame(width: 174, height: 174)
                    Circle().stroke(result.isWin ? LotteryTheme.gold : Color.white.opacity(0.24), lineWidth: 2).frame(width: 146, height: 146)
                    Image(systemName: result.isWin ? "gift.fill" : "sparkles")
                        .font(.system(size: 64, weight: .black))
                        .foregroundStyle(result.isWin ? LotteryTheme.goldLight : Color.white.opacity(0.62))
                }
                VStack(spacing: 7) {
                    Text(result.isWin ? "おめでとうございます" : "今回はざんねん")
                        .font(.system(size: 24, weight: .black, design: .rounded))
                    Text(result.coupon?.name ?? "またチャレンジしてね")
                        .font(.system(size: 18, weight: .bold, design: .rounded))
                        .multilineTextAlignment(.center)
                    if let description = result.coupon?.description?.trimmedNonEmpty {
                        Text(description).font(.system(size: 13, weight: .medium)).opacity(0.66).multilineTextAlignment(.center)
                    }
                }
                .foregroundStyle(LotteryTheme.cream)
                Button(action: onClose) {
                    Text(result.isWin ? "クーポンを受け取る" : "閉じる")
                        .font(.system(size: 16, weight: .black, design: .rounded))
                        .foregroundStyle(LotteryTheme.night)
                        .frame(maxWidth: .infinity)
                        .frame(height: 56)
                        .background(LotteryTheme.goldLight)
                        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
                }
                #if os(Android)
                .buttonStyle(.plain)
                #else
                .buttonStyle(LotteryPressStyle())
                #endif
            }
            .padding(24)
            .frame(maxWidth: 360)
        }
    }
}

struct LotterySkeletonView: View {
    var body: some View {
        VStack(spacing: 16) {
            Spacer().frame(height: 72)
            SkeletonBlock(height: 68, cornerRadius: 18)
            SkeletonBlock(height: 560, cornerRadius: 28)
        }
        .padding(16)
    }
}
