import Foundation

/// 表示済みの記録を端末の UserDefaults に保存する。
struct UserDefaultsPromoPopupSeenStore: PromoPopupSeenStoring {
    private let defaults: UserDefaults

    init(defaults: UserDefaults = .standard) {
        self.defaults = defaults
    }

    func lastSeenDay(projectKey: String, popupKey: String) -> String? {
        defaults.string(forKey: Self.key(projectKey: projectKey, popupKey: popupKey))
    }

    func markSeen(projectKey: String, popupKey: String, day: String) {
        defaults.set(day, forKey: Self.key(projectKey: projectKey, popupKey: popupKey))
    }

    private static func key(projectKey: String, popupKey: String) -> String {
        "promo-popup-seen:\(projectKey):\(popupKey)"
    }
}
