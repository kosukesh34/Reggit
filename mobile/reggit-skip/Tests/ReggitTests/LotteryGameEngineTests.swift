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
        await viewModel.resolveScratch()

        XCTAssertEqual(viewModel.scratchResult, result)
        XCTAssertNil(viewModel.revealedResult)
        let countBefore = viewModel.scratchedCells.count
        XCTAssertFalse(viewModel.scratch(atX: 220, y: 120, width: 264, height: 158))
        XCTAssertGreaterThan(viewModel.scratchedCells.count, countBefore)
    }
}

private struct LotteryGameServiceStub: LotteryGameProviding {
    let state: LotteryStateSnapshot
    let result: LotteryPlayResult

    func fetchState() async throws -> LotteryStateSnapshot { state }
    func play(entitlementId _: Int) async throws -> LotteryPlayResult { result }
    func notifyWin(entitlementId _: Int) async throws {}
}
