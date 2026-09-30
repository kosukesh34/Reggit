import Observation
import SwiftUI

@MainActor
@Observable
final class LotteryViewModel {
    var isLoading = true
    var errorMessage: String?
    var state: LotteryStateSnapshot?
    var selectedGame: LotteryGameKind = .gacha
    var isPlaying = false
    var alertTitle: String?
    var alertMessage: String?
    var revealedResult: LotteryPlayResult?

    var gachaRotation = 0.0
    var gachaShakeOffset = 0.0
    var showsDroppedCapsule = false
    var opensDroppedCapsule = false

    var scratchProgress = 0.0
    var scratchedCells: [Int] = []
    var scratchResult: LotteryPlayResult?
    var scratchRequested = false
    var isScratchResolving = false

    var rouletteRotation = 0.0
    private var playedEntitlementIDs: Set<Int> = []

    private let service: any LotteryGameProviding
    private let usesCache: Bool
    private let scratchEngine: ScratchRevealEngine
    private let roulettePlanner: RouletteSpinPlanner

    convenience init(apiClient: any ReggitAPIServing) {
        self.init(service: ReggitLotteryGameService(apiClient: apiClient))
    }

    init(
        service: any LotteryGameProviding,
        usesCache: Bool = true,
        scratchEngine: ScratchRevealEngine = ScratchRevealEngine(),
        roulettePlanner: RouletteSpinPlanner = RouletteSpinPlanner()
    ) {
        self.service = service
        self.usesCache = usesCache
        self.scratchEngine = scratchEngine
        self.roulettePlanner = roulettePlanner
    }

    func load(force: Bool = false) async {
        if usesCache, !force, let cached = SecondaryScreenCache.lottery {
            state = cached
            selectPendingGame()
            isLoading = false
            return
        }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let latest = try await service.fetchState()
            state = latest
            if usesCache { SecondaryScreenCache.lottery = latest }
            selectPendingGame()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    var nextEntitlement: LotteryEntitlement? {
        state?.playableEntitlements.first { !playedEntitlementIDs.contains($0.id) }
    }

    private func pendingEntitlement(for type: String) -> LotteryEntitlement? {
        state?.playableEntitlements.first { $0.rewardType == type && !playedEntitlementIDs.contains($0.id) }
    }

    private func selectPendingGame() {
        if let pending = nextEntitlement, let game = LotteryGameKind(rawValue: pending.rewardType) {
            selectedGame = game
        } else if !availableGames.contains(selectedGame), let first = availableGames.first {
            selectedGame = first
        }
    }

    /// The server determines the result; this only advances the presentation.
    func playNextAutomatically(reduceMotion: Bool) async {
        guard !isPlaying, !isScratchResolving, revealedResult == nil,
              let entitlement = nextEntitlement,
              let game = LotteryGameKind(rawValue: entitlement.rewardType) else { return }
        selectedGame = game
        switch game {
        case .gacha: await playGacha(reduceMotion: reduceMotion)
        case .roulette: await playRoulette(reduceMotion: reduceMotion)
        case .scratch: await playScratchAutomatically(reduceMotion: reduceMotion)
        }
    }

    func playScratchAutomatically(reduceMotion: Bool) async {
        guard !isPlaying, !isScratchResolving,
              let entitlement = pendingEntitlement(for: "scratch") else { return }
        isPlaying = true
        isScratchResolving = true
        resetScratch()
        scratchRequested = true
        defer { isPlaying = false; isScratchResolving = false }
        do {
            let result = try await service.play(entitlementId: entitlement.id)
            playedEntitlementIDs.insert(entitlement.id)
            scratchResult = result
            let steps = reduceMotion ? 1 : ScratchRevealEngine.columns
            for step in 1...steps {
                let count = ScratchRevealEngine.cellCount * step / steps
                withAnimation(.easeOut(duration: reduceMotion ? 0.1 : 0.12)) {
                    scratchedCells = (0..<ScratchRevealEngine.cellCount).filter {
                        $0 % ScratchRevealEngine.columns < ScratchRevealEngine.columns * step / steps
                    }
                    scratchProgress = Double(count) / Double(ScratchRevealEngine.cellCount)
                }
                try? await Task.sleep(nanoseconds: reduceMotion ? 100_000_000 : 110_000_000)
            }
            try? await Task.sleep(nanoseconds: reduceMotion ? 100_000_000 : 350_000_000)
            await present(result)
            await refreshState()
        } catch {
            scratchRequested = false
            showAlert(title: "抽選結果を確認できませんでした", message: error.localizedDescription)
        }
    }

    func hasEntitlement(_ type: String) -> Bool {
        pendingEntitlement(for: type) != nil
    }

    var availableGames: [LotteryGameKind] {
        guard let state else { return LotteryGameKind.all }
        var games: [LotteryGameKind] = []
        if state.gachaEnabled { games.append(.gacha) }
        if state.scratchEnabled { games.append(.scratch) }
        if state.rouletteEnabled { games.append(.roulette) }
        return games.isEmpty ? LotteryGameKind.all : games
    }

    var canScratchCurrentTicket: Bool {
        hasEntitlement("scratch") || scratchRequested || scratchResult != nil
    }

    var eligibilityMessage: String {
        guard let state else { return "購入特典として抽選券が付与されます" }
        var enabledGames: [String] = []
        if state.gachaEnabled { enabledGames.append("ガチャ") }
        if state.scratchEnabled { enabledGames.append("スクラッチ") }
        if state.rouletteEnabled { enabledGames.append("ルーレット") }
        let gameLabel = enabledGames.isEmpty ? "抽選1回" : enabledGames.joined(separator: "・") + "各1回"
        switch state.grantTrigger {
        case "min_amount":
            let amount = Int(state.minimumPurchaseAmount ?? 0)
            return "¥\(amount)以上の購入で\(gameLabel)"
        case "once_per_day":
            return "購入した方へ1日1回、\(gameLabel)"
        default:
            return "購入ごとに\(gameLabel)"
        }
    }

    func playGacha(reduceMotion: Bool) async {
        guard !isPlaying else { return }
        guard let entitlement = pendingEntitlement(for: "gacha") else {
            showAlert(title: "プレイできません", message: "現在利用できるガチャの権利がありません。")
            return
        }
        isPlaying = true
        showsDroppedCapsule = false
        opensDroppedCapsule = false
        withAnimation(.easeInOut(duration: reduceMotion ? 0.15 : 1.1)) {
            gachaRotation += reduceMotion ? 90 : 1080
        }
        do {
            let result = try await service.play(entitlementId: entitlement.id)
            playedEntitlementIDs.insert(entitlement.id)
            if !reduceMotion {
                for index in 0 ..< 6 {
                    try? await Task.sleep(nanoseconds: 85_000_000)
                    withAnimation(.easeInOut(duration: 0.08)) {
                        gachaShakeOffset = index % 2 == 0 ? -6 : 6
                    }
                }
            }
            withAnimation(.easeOut(duration: 0.12)) { gachaShakeOffset = 0 }
            withAnimation(.easeOut(duration: reduceMotion ? 0.12 : 0.48)) { showsDroppedCapsule = true }
            try? await Task.sleep(nanoseconds: reduceMotion ? 120_000_000 : 520_000_000)
            withAnimation(.easeOut(duration: reduceMotion ? 0.12 : 0.28)) { opensDroppedCapsule = true }
            try? await Task.sleep(nanoseconds: reduceMotion ? 120_000_000 : 420_000_000)
            await present(result)
        } catch {
            showAlert(title: "プレイできませんでした", message: error.localizedDescription)
        }
        isPlaying = false
        await refreshState()
    }

    /// Returns true only on the first contact, when the result should be requested.
    func scratch(atX x: CGFloat, y: CGFloat, width: CGFloat, height: CGFloat) -> Bool {
        guard canScratchCurrentTicket else {
            showAlert(title: "プレイできません", message: "現在利用できるスクラッチの権利がありません。")
            return false
        }
        scratchedCells = scratchEngine.scratchedIndices(
            atX: x,
            y: y,
            width: width,
            height: height,
            current: scratchedCells
        )
        scratchProgress = scratchEngine.progress(for: scratchedCells)
        guard !scratchRequested, scratchResult == nil else { return false }
        scratchRequested = true
        return true
    }

    func resolveScratch() async {
        guard !isScratchResolving,
              let entitlement = pendingEntitlement(for: "scratch") else { return }
        isScratchResolving = true
        defer { isScratchResolving = false }
        do {
            let result = try await service.play(entitlementId: entitlement.id)
            playedEntitlementIDs.insert(entitlement.id)
            withAnimation(.easeOut(duration: 0.2)) { scratchResult = result }
            if result.isWin, result.coupon != nil {
                try? await service.notifyWin(entitlementId: result.entitlementId)
            }
            await refreshState()
        } catch {
            scratchRequested = false
            showAlert(title: "プレイできませんでした", message: error.localizedDescription)
        }
    }

    func resetScratch() {
        scratchProgress = 0
        scratchedCells = []
        scratchResult = nil
        scratchRequested = false
    }

    func playRoulette(reduceMotion: Bool) async {
        guard !isPlaying else { return }
        guard let entitlement = pendingEntitlement(for: "roulette") else {
            showAlert(title: "プレイできません", message: "現在利用できるルーレットの権利がありません。")
            return
        }
        isPlaying = true
        do {
            let result = try await service.play(entitlementId: entitlement.id)
            playedEntitlementIDs.insert(entitlement.id)
            let labels = rouletteLabels
            let resultLabel = result.coupon?.name ?? "ハズレ"
            let pickedIndex = labels.firstIndex(of: resultLabel) ?? 0
            let plan = roulettePlanner.plan(
                pickedIndex: pickedIndex,
                labelCount: labels.count,
                currentRotation: rouletteRotation,
                reduceMotion: reduceMotion
            )
            withAnimation(.easeOut(duration: reduceMotion ? 0.2 : 2.4)) {
                rouletteRotation += plan.travel
            }
            try? await Task.sleep(nanoseconds: reduceMotion ? 220_000_000 : 2_450_000_000)
            await present(result)
        } catch {
            showAlert(title: "プレイできませんでした", message: error.localizedDescription)
        }
        isPlaying = false
        await refreshState()
    }

    var rouletteLabels: [String] {
        roulettePlanner.labels(from: state?.roulettePrizeLabels ?? [])
    }

    private func refreshState() async {
        if let latest = try? await service.fetchState() {
            state = latest
            if usesCache { SecondaryScreenCache.lottery = latest }
        }
    }

    private func present(_ result: LotteryPlayResult) async {
        if result.isWin, result.coupon != nil {
            try? await service.notifyWin(entitlementId: result.entitlementId)
        }
        withAnimation(.easeOut(duration: 0.24)) { revealedResult = result }
    }

    private func showAlert(title: String, message: String) {
        alertTitle = title
        alertMessage = message
    }
}
