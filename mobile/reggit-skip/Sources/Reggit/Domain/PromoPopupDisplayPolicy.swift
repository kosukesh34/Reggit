import Foundation

/// どのポップアップを今回の起動で出すかを決める。保存先や画面には依存しない。
enum PromoPopupDisplayPolicy {
    /// サーバーは優先度順に返すため、表示条件を満たす先頭の1件を選ぶ
    static func next(
        from popups: [PromoPopup],
        today: String,
        shownThisLaunch: Set<Int>,
        lastSeenDay: (PromoPopup) -> String?
    ) -> PromoPopup? {
        popups.first { popup in
            !shownThisLaunch.contains(popup.id) && shouldShow(popup.frequency, lastSeenDay: lastSeenDay(popup), today: today)
        }
    }

    static func shouldShow(_ frequency: PromoPopup.Frequency, lastSeenDay: String?, today: String) -> Bool {
        guard let lastSeenDay else { return true }
        switch frequency {
        case .everyOpen: return true
        case .daily: return lastSeenDay != today
        case .once: return false
        }
    }

    static func day(_ date: Date, calendar: Calendar = .current) -> String {
        let parts = calendar.dateComponents([.year, .month, .day], from: date)
        return "\(parts.year ?? 0)-\(parts.month ?? 0)-\(parts.day ?? 0)"
    }
}
