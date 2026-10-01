import Foundation

enum LotteryGameKind: String, Identifiable {
    case gacha
    case scratch
    case roulette

    var id: String { rawValue }

    var title: String {
        switch self {
        case .gacha: return "ガチャ"
        case .scratch: return "スクラッチ"
        case .roulette: return "ルーレット"
        }
    }

    var icon: String {
        switch self {
        case .gacha: return "circle.hexagongrid.fill"
        case .scratch: return "sparkles"
        case .roulette: return "circle.circle.fill"
        }
    }

    var shortTitle: String {
        switch self {
        case .gacha: return "ガチャ"
        case .scratch: return "スクラッチ"
        case .roulette: return "ルーレット"
        }
    }

    static let all: [LotteryGameKind] = [.gacha, .scratch, .roulette]
}

protocol LotteryGameProviding: Sendable {
    func fetchState() async throws -> LotteryStateSnapshot
    func play(entitlementId: Int) async throws -> LotteryPlayResult
    func notifyWin(entitlementId: Int) async throws
}

struct ReggitLotteryGameService: LotteryGameProviding {
    let apiClient: any ReggitAPIServing

    func fetchState() async throws -> LotteryStateSnapshot {
        try await apiClient.fetchLotteryState()
    }

    func play(entitlementId: Int) async throws -> LotteryPlayResult {
        try await apiClient.playLottery(entitlementId: entitlementId)
    }

    func notifyWin(entitlementId: Int) async throws {
        try await apiClient.notifyLotteryWin(entitlementId: entitlementId)
    }
}

struct ScratchRevealEngine {
    static let columns = 18
    static let rows = 12
    static let cellCount = columns * rows

    func scratchedIndices(
        atX x: CGFloat,
        y: CGFloat,
        width: CGFloat,
        height: CGFloat,
        current: [Int]
    ) -> [Int] {
        guard width > 0, height > 0 else { return current }
        let column = max(0, min(Self.columns - 1, Int(x / (width / CGFloat(Self.columns)))))
        let row = max(0, min(Self.rows - 1, Int(y / (height / CGFloat(Self.rows)))))
        var result = current
        for rowOffset in -1 ... 1 {
            for columnOffset in -1 ... 1 {
                let targetRow = row + rowOffset
                let targetColumn = column + columnOffset
                guard targetRow >= 0, targetRow < Self.rows,
                      targetColumn >= 0, targetColumn < Self.columns else { continue }
                let index = targetRow * Self.columns + targetColumn
                if !result.contains(index) { result.append(index) }
            }
        }
        return result
    }

    func progress(for scratchedIndices: [Int]) -> Double {
        min(1, Double(scratchedIndices.count) / Double(Self.cellCount))
    }
}

struct RouletteSpinPlan {
    let pickedIndex: Int
    let travel: Double
}

struct RouletteSpinPlanner {
    func labels(from configuredLabels: [String]) -> [String] {
        let base = configuredLabels.isEmpty ? ["ドリンク", "デザート", "50pt", "ハズレ"] : configuredLabels
        var labels: [String] = []
        while labels.count < 8 { labels.append(base[labels.count % base.count]) }
        return Array(labels.prefix(8))
    }

    func plan(labelCount: Int, currentRotation: Double, reduceMotion: Bool) -> RouletteSpinPlan {
        let safeCount = max(1, labelCount)
        let picked = Int.random(in: 0 ..< safeCount)
        return plan(
            pickedIndex: picked,
            labelCount: safeCount,
            currentRotation: currentRotation,
            reduceMotion: reduceMotion
        )
    }

    func plan(pickedIndex: Int, labelCount: Int, currentRotation: Double, reduceMotion: Bool) -> RouletteSpinPlan {
        let safeCount = max(1, labelCount)
        let picked = max(0, min(safeCount - 1, pickedIndex))
        let segmentAngle = 360 / Double(safeCount)
        let currentAngle = currentRotation.truncatingRemainder(dividingBy: 360)
        let targetAngle = 360 - (Double(picked) * segmentAngle + segmentAngle / 2)
        let turns = reduceMotion ? 1 : 6 + picked
        return RouletteSpinPlan(
            pickedIndex: picked,
            travel: Double(turns) * 360 + targetAngle - currentAngle
        )
    }
}
