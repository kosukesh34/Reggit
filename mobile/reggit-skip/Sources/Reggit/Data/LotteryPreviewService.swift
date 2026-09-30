import Foundation

/// Temporary menu previews use local fixtures and never call purchase APIs.
struct LotteryPreviewService: LotteryGameProviding {
    let game: LotteryGameKind

    func fetchState() async throws -> LotteryStateSnapshot {
        LotteryStateSnapshot(
            gachaEnabled: game == .gacha,
            scratchEnabled: game == .scratch,
            rouletteEnabled: game == .roulette,
            entitlements: [LotteryEntitlement(id: -1, rewardType: game.rawValue, expiresAt: nil)],
            gachaPrizeLabels: ["ドリンク1杯", "デザート", "50円引き"],
            scratchPrizeLabels: ["ドリンク1杯", "はずれ"],
            roulettePrizeLabels: ["ドリンク1杯", "50円引き", "はずれ", "デザート"],
            grantTrigger: "every_purchase",
            minimumPurchaseAmount: nil,
            entitlementExpiresHours: nil
        )
    }

    func play(entitlementId: Int) async throws -> LotteryPlayResult {
        LotteryPlayResult(
            entitlementId: entitlementId,
            rewardType: game.rawValue,
            isWin: true,
            coupon: LotteryCouponPrize(id: -1, name: "ドリンク1杯プレゼント", description: "テスト用の抽選結果です", imageURL: nil)
        )
    }

    func notifyWin(entitlementId _: Int) async throws {}
}
