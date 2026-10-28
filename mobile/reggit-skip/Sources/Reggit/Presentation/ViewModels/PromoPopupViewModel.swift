import Foundation
import Observation

/// 起動時の宣伝ポップアップ。取得・表示判定・表示済みの記録を依存先へ任せ、表示中の1件だけを持つ。
@MainActor
@Observable
final class PromoPopupViewModel {
    private(set) var current: PromoPopup?

    private let seenStore: any PromoPopupSeenStoring
    private let now: () -> Date
    /// 「起動のたび」も同じ起動中は1回だけにする
    private var shownThisLaunch: Set<Int> = []
    private var loadedProjectKey: String?
    private var source: (any PromoPopupFetching)?

    init(seenStore: any PromoPopupSeenStoring = UserDefaultsPromoPopupSeenStore(), now: @escaping () -> Date = { Date() }) {
        self.seenStore = seenStore
        self.now = now
    }

    /// プロジェクトごとに起動中1回だけ取得する。取得に失敗しても画面は止めない
    func load(from source: any PromoPopupFetching, projectKey: String) async {
        guard loadedProjectKey != projectKey else { return }
        loadedProjectKey = projectKey
        self.source = source
        guard let popups = try? await source.fetchPromoPopups() else { return }
        let today = PromoPopupDisplayPolicy.day(now())
        let store = seenStore
        guard let next = PromoPopupDisplayPolicy.next(
            from: popups,
            today: today,
            shownThisLaunch: shownThisLaunch,
            lastSeenDay: { store.lastSeenDay(projectKey: projectKey, popupKey: $0.seenKey) }
        ) else { return }
        shownThisLaunch.insert(next.id)
        store.markSeen(projectKey: projectKey, popupKey: next.seenKey, day: today)
        current = next
        report(.view)
    }

    func dismiss() {
        report(.close)
        current = nil
    }

    /// 押したボタンの遷移先を返して閉じる。遷移は呼び出し側（画面の切り替えを持つ側）が行う
    func takeAction(secondary: Bool = false) -> PromoPopup.Link {
        let link = (secondary ? current?.secondaryLink : current?.link) ?? .none
        // 遷移先のないボタン（「閉じる」）は、タップ率に入れず閉じたとして数える
        report(link == .none ? .close : secondary ? .secondaryClick : .click)
        current = nil
        return link
    }

    /// 集計用なので、送れなくても画面は止めない
    private func report(_ event: PromoPopup.Event) {
        guard let id = current?.id, let source else { return }
        Task { try? await source.recordPromoPopupEvent(id: id, event: event) }
    }
}
