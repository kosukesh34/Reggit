import Foundation

/// ホーム・会員証以外の一覧。プロセス内だけ持つので、アプリを起動し直すと空になり API を叩き直す。
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

    static func galleryKey(_ categoryId: Int?) -> String {
        categoryId.map(String.init) ?? "all"
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
    }
}
