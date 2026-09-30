import Foundation

/// タブを切り替えても保持するプロセス内キャッシュ。明示更新や関連イベントで無効化する。
@MainActor
enum SecondaryScreenCache {
    static var products: [ProductItem]?
    static var coupons: [CouponItem]?
    static var deviceOnceCoupons: [DeviceOnceCouponItem]?
    static var stamps: [StampCardItem]?
    static var orders: [OrderSnapshot]?
    static var announcements: [AnnouncementItem]?
    static var reservations: [ReservationItem]?
    static var reservationCourses: [ReservationCourseItem]?
    static var galleries: [String: GallerySnapshot] = [:]
    static var appConfig: AppConfig?
    static var pointHistory: [PointHistoryItem]?
    static var recentMemberCoupons: [CouponItem]?
    static var queue: CurrentQueueSnapshot?
    static var lottery: LotteryStateSnapshot?
    static var projects: [MobilePublicProject]?
    static var memberProfileNeedsRefresh = false
    static var handledPaymentReturnRevision = 0

    static func galleryKey(_ categoryId: Int?) -> String {
        categoryId.map(String.init) ?? "all"
    }

    static func invalidate(topic: String) {
        if topic == "all" {
            let paymentRevision = handledPaymentReturnRevision
            reset()
            handledPaymentReturnRevision = paymentRevision
            memberProfileNeedsRefresh = true
            return
        }
        switch topic {
        case "queue": queue = nil
        case "orders": orders = nil; lottery = nil
        case "coupons": coupons = nil; deviceOnceCoupons = nil; recentMemberCoupons = nil
        case "points": pointHistory = nil
        case "stamps": stamps = nil
        case "products": products = nil
        case "reservations": reservations = nil; reservationCourses = nil
        case "gallery": galleries.removeAll()
        case "announcements": announcements = nil
        default: break
        }
    }

    static func reset() {
        products = nil
        coupons = nil
        deviceOnceCoupons = nil
        stamps = nil
        orders = nil
        announcements = nil
        reservations = nil
        reservationCourses = nil
        galleries.removeAll()
        appConfig = nil
        pointHistory = nil
        recentMemberCoupons = nil
        queue = nil
        lottery = nil
        projects = nil
        memberProfileNeedsRefresh = false
        handledPaymentReturnRevision = 0
    }
}
