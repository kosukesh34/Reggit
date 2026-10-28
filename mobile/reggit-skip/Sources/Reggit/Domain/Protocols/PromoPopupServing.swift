import Foundation

/// 宣伝ポップアップの取得。ポップアップの画面はこの境界だけに依存する。
protocol PromoPopupFetching: Sendable {
    func fetchPromoPopups() async throws -> [PromoPopup]
    /// 表示・タップの記録（集計用）。失敗しても画面には出さない
    func recordPromoPopupEvent(id: Int, event: PromoPopup.Event) async throws
}

/// 端末ごとの表示済み記録（最後に表示した日）。
protocol PromoPopupSeenStoring {
    func lastSeenDay(projectKey: String, popupKey: String) -> String?
    func markSeen(projectKey: String, popupKey: String, day: String)
}
