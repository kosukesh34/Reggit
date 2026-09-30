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
    var gachaStir = 0.0
    var showsDroppedCapsule = false
    var opensDroppedCapsule = false

    var scratchProgress = 0.0
    var scratchedCells: [Int] = []
    var scratchResult: LotteryPlayResult?
    var scratchRequested = false
    var isScratchResolving = false
    var isScratchRevealed = false

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

    func prepareNextGame() {
        revealedResult = nil
        resetScratch()
        selectPendingGame()
    }

    func playSelectedGame(reduceMotion: Bool) async {
        guard !isPlaying, !isScratchResolving, revealedResult == nil else { return }
        switch selectedGame {
        case .gacha: await playGacha(reduceMotion: reduceMotion)
        case .roulette: await playRoulette(reduceMotion: reduceMotion)
        case .scratch: break
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

    var isScratchInProgress: Bool {
        scratchRequested && !isScratchRevealed
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
        async let played = service.play(entitlementId: entitlement.id)
        if reduceMotion {
            withAnimation(.easeInOut(duration: 0.15)) { gachaRotation += 90 }
        } else {
            for step in 1 ... 4 {
                withAnimation(.easeIn(duration: 0.3)) { gachaRotation += 90 }
                await pause(0.3)
                withAnimation(.easeOut(duration: 0.08)) { gachaStir = Double(step) }
                await pause(0.08)
                withAnimation(.spring(response: 0.35, dampingFraction: 0.6)) { gachaStir = 0 }
                await pause(0.18)
            }
        }
        do {
            let result = try await played
            playedEntitlementIDs.insert(entitlement.id)
            withAnimation(reduceMotion ? .easeOut(duration: 0.12) : .spring(response: 0.5, dampingFraction: 0.72)) {
                showsDroppedCapsule = true
            }
            await pause(reduceMotion ? 0.12 : 1.0)
            withAnimation(reduceMotion ? .easeOut(duration: 0.12) : .spring(response: 0.5, dampingFraction: 0.8)) {
                opensDroppedCapsule = true
            }
            await pause(reduceMotion ? 0.12 : 1.1)
            await present(result)
        } catch {
            showAlert(title: "プレイできませんでした", message: error.localizedDescription)
        }
        isPlaying = false
        await refreshState()
    }

    /// Requests the result on the first stroke and reveals it once half of the ticket has been scratched.
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
        revealScratchIfReady()
        guard !scratchRequested, scratchResult == nil else { return false }
        scratchRequested = true
        return true
    }

    func resolveScratch() async {
        guard scratchRequested, !isScratchResolving, scratchResult == nil,
              let entitlement = pendingEntitlement(for: "scratch") else { return }
        isScratchResolving = true
        do {
            let result = try await service.play(entitlementId: entitlement.id)
            playedEntitlementIDs.insert(entitlement.id)
            scratchResult = result
            isScratchResolving = false
            revealScratchIfReady()
            if result.isWin, result.coupon != nil {
                try? await service.notifyWin(entitlementId: result.entitlementId)
            }
            await refreshState()
        } catch {
            isScratchResolving = false
            scratchRequested = false
            showAlert(title: "プレイできませんでした", message: error.localizedDescription)
        }
    }

    private func revealScratchIfReady() {
        guard !isScratchRevealed, scratchResult != nil, scratchProgress >= 0.5 else { return }
        withAnimation(.easeOut(duration: 0.45)) { isScratchRevealed = true }
    }

    func resetScratch() {
        scratchProgress = 0
        scratchedCells = []
        scratchResult = nil
        scratchRequested = false
        isScratchRevealed = false
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
            withAnimation(reduceMotion ? .easeOut(duration: 0.2) : .timingCurve(0.2, 0.7, 0.1, 1, duration: 5.6)) {
                rouletteRotation += plan.travel
            }
            await pause(reduceMotion ? 0.22 : 6.3)
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
        withAnimation(.easeOut(duration: 0.35)) { revealedResult = result }
    }

    private func pause(_ seconds: Double) async {
        try? await Task.sleep(nanoseconds: UInt64(seconds * 1_000_000_000))
    }

    private func showAlert(title: String, message: String) {
        alertTitle = title
        alertMessage = message
    }
}
