import XCTest
@testable import Reggit

final class LotteryGameEngineTests: XCTestCase {
    func testScratchEngineRevealsOneContinuousSurfaceWithoutDuplicateCells() {
        let engine = ScratchRevealEngine()
        let first = engine.scratchedIndices(
            atX: 132,
            y: 79,
            width: 264,
            height: 158,
            current: []
        )
        let second = engine.scratchedIndices(
            atX: 132,
            y: 79,
            width: 264,
            height: 158,
            current: first
        )

        XCTAssertEqual(first.count, 9)
        XCTAssertEqual(second.count, first.count)
        XCTAssertTrue(second.allSatisfy { (0 ..< ScratchRevealEngine.cellCount).contains($0) })
        XCTAssertEqual(engine.progress(for: second), Double(second.count) / Double(ScratchRevealEngine.cellCount))
    }

    func testRoulettePlanStopsSelectedSegmentAtPointer() {
        let planner = RouletteSpinPlanner()
        let plan = planner.plan(labelCount: 8, currentRotation: 127, reduceMotion: false)
        let finalAngle = (127 + plan.travel).truncatingRemainder(dividingBy: 360)
        let expected = 360 - (Double(plan.pickedIndex) * 45 + 22.5)

        XCTAssertEqual(finalAngle, expected, accuracy: 0.0001)
    }

    func testRouletteLabelsAlwaysFillEightSegments() {
        XCTAssertEqual(
            RouletteSpinPlanner().labels(from: ["当たり", "はずれ"]),
            ["当たり", "はずれ", "当たり", "はずれ", "当たり", "はずれ", "当たり", "はずれ"]
        )
    }

    @MainActor
    func testCachedScratchOnlyPurchaseWaitsUntilHalfIsScratched() async {
        let state = purchaseState(gacha: false, scratch: true, roulette: false)
        SecondaryScreenCache.lottery = state
        defer { SecondaryScreenCache.reset() }
        let result = LotteryPlayResult(entitlementId: 2, rewardType: "scratch", isWin: false, coupon: nil)
        let model = LotteryViewModel(service: LotteryGameServiceStub(state: state, result: result))
        await model.load()
        XCTAssertEqual(model.selectedGame, .scratch)
        XCTAssertEqual(model.scratchProgress, 0)
        XCTAssertNil(model.scratchResult)
        await model.resolveScratch()
        XCTAssertNil(model.scratchResult)
        XCTAssertTrue(model.scratch(atX: 132, y: 79, width: 264, height: 158))
        await model.resolveScratch()
        XCTAssertEqual(model.scratchResult, result)
        XCTAssertFalse(model.isScratchRevealed)
        XCTAssertNil(model.nextEntitlement)
        XCTAssertTrue(model.isScratchInProgress)
        scratchUntilThreshold(model)
        XCTAssertTrue(model.isScratchRevealed)
        XCTAssertFalse(model.isScratchInProgress)
        XCTAssertFalse(model.scratch(atX: 132, y: 79, width: 264, height: 158))
    }

    @MainActor
    func testRouletteOnlyPurchaseWaitsForPlayAction() async {
        SecondaryScreenCache.reset()
        let state = purchaseState(gacha: false, scratch: false, roulette: true)
        let result = LotteryPlayResult(entitlementId: 3, rewardType: "roulette", isWin: false, coupon: nil)
        let model = LotteryViewModel(service: LotteryGameServiceStub(state: state, result: result))
        await model.load()
        XCTAssertEqual(model.selectedGame, .roulette)
        XCTAssertNil(model.revealedResult)
        XCTAssertEqual(model.rouletteRotation, 0)
        await model.playSelectedGame(reduceMotion: true)
        XCTAssertEqual(model.revealedResult, result)
        XCTAssertNil(model.nextEntitlement)
        SecondaryScreenCache.reset()
    }

    func testPurchaseOnlyOffersEnabledGamesWithTickets() {
        XCTAssertEqual(purchaseState(gacha: false, scratch: true, roulette: false).playableEntitlements.map(\.rewardType), ["scratch"])
        XCTAssertTrue(purchaseState(gacha: false, scratch: false, roulette: false).playableEntitlements.isEmpty)
        XCTAssertEqual(purchaseState(gacha: true, scratch: true, roulette: true).playableEntitlements.count, 3)
    }

    @MainActor
    func testMenuPreviewsPlayAllThreeGamesWithoutChangingPurchaseCache() async {
        let cached = purchaseState(gacha: true, scratch: true, roulette: true)
        SecondaryScreenCache.lottery = cached
        defer { SecondaryScreenCache.reset() }
        for game in LotteryGameKind.all {
            let model = LotteryViewModel(service: LotteryPreviewService(game: game), usesCache: false)
            await model.load()
            XCTAssertEqual(model.selectedGame, game)
            XCTAssertNil(model.revealedResult)
            if game == .scratch {
                scratchUntilThreshold(model)
                await model.resolveScratch()
            } else {
                await model.playSelectedGame(reduceMotion: true)
            }
            let result = model.revealedResult ?? model.scratchResult
            XCTAssertEqual(result?.rewardType, game.rawValue)
            XCTAssertEqual(result?.entitlementId, -1)
            XCTAssertNil(model.nextEntitlement)
            XCTAssertEqual(SecondaryScreenCache.lottery, cached)
        }
    }

    @MainActor
    private func scratchUntilThreshold(_ model: LotteryViewModel) {
        for row in 0..<ScratchRevealEngine.rows {
            for column in 0..<ScratchRevealEngine.columns {
                _ = model.scratch(atX: Double(column) * 22 + 11, y: Double(row) * 19.75 + 9, width: 264, height: 158)
                if model.scratchProgress >= 0.5 { return }
            }
        }
        XCTFail("Half of the scratched surface must trigger the result")
    }

    private func purchaseState(gacha: Bool, scratch: Bool, roulette: Bool) -> LotteryStateSnapshot {
        LotteryStateSnapshot(
            gachaEnabled: gacha, scratchEnabled: scratch, rouletteEnabled: roulette,
            entitlements: [
                LotteryEntitlement(id: 1, rewardType: "gacha", expiresAt: nil),
                LotteryEntitlement(id: 2, rewardType: "scratch", expiresAt: nil),
                LotteryEntitlement(id: 3, rewardType: "roulette", expiresAt: nil),
            ],
            gachaPrizeLabels: [], scratchPrizeLabels: [], roulettePrizeLabels: ["ハズレ"],
            grantTrigger: "every_purchase", minimumPurchaseAmount: nil, entitlementExpiresHours: nil
        )
    }

    @MainActor
    func testScratchRevealsServerResultInsideTicketWithoutModal() async {
        let entitlement = LotteryEntitlement(id: 7, rewardType: "scratch", expiresAt: nil)
        let state = LotteryStateSnapshot(
            gachaEnabled: false,
            scratchEnabled: true,
            rouletteEnabled: false,
            entitlements: [entitlement],
            gachaPrizeLabels: [],
            scratchPrizeLabels: ["はずれ"],
            roulettePrizeLabels: [],
            grantTrigger: "min_amount",
            minimumPurchaseAmount: 3_000,
            entitlementExpiresHours: 24
        )
        let result = LotteryPlayResult(
            entitlementId: 7,
            rewardType: "scratch",
            isWin: false,
            coupon: nil
        )
        let viewModel = LotteryViewModel(service: LotteryGameServiceStub(state: state, result: result))
        await viewModel.load()

        XCTAssertTrue(viewModel.scratch(atX: 132, y: 79, width: 264, height: 158))
        scratchUntilThreshold(viewModel)
        XCTAssertFalse(viewModel.isScratchRevealed)
        await viewModel.resolveScratch()

        XCTAssertEqual(viewModel.scratchResult, result)
        XCTAssertTrue(viewModel.isScratchRevealed)
        XCTAssertNil(viewModel.revealedResult)
        let countBefore = viewModel.scratchedCells.count
        XCTAssertFalse(viewModel.scratch(atX: 220, y: 120, width: 264, height: 158))
        XCTAssertGreaterThan(viewModel.scratchedCells.count, countBefore)
        viewModel.resetScratch()
        XCTAssertFalse(viewModel.isScratchRevealed)
    }
}

private struct LotteryGameServiceStub: LotteryGameProviding {
    let state: LotteryStateSnapshot
    let result: LotteryPlayResult

    func fetchState() async throws -> LotteryStateSnapshot { state }
    func play(entitlementId _: Int) async throws -> LotteryPlayResult { result }
    func notifyWin(entitlementId _: Int) async throws {}
}
