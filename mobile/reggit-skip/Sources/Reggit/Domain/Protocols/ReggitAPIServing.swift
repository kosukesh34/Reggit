import Foundation

/// 顧客向け REST API の境界。Presentation は具象クライアントではなくこのプロトコルに依存する。
protocol ReggitAPIServing: Sendable {
    var baseURL: String { get }

    func registerDevice(
        platform: String,
        pushToken: String?,
        osVersion: String?,
        appVersion: String?
    ) async throws

    func fetchMemberProfile() async throws -> MemberProfileSnapshot
    func registerMember(name: String, phone: String) async throws -> MemberProfileSnapshot
    func updateMemberProfile(name: String, phone: String, profilePayload: [String: String], profileListPayload: [String: [String]]) async throws -> MemberProfileSnapshot
    func fetchPointCard() async throws -> PointCardSnapshot
    func fetchPointHistory() async throws -> [PointHistoryItem]

    func fetchMemberCoupons() async throws -> [CouponItem]
    func fetchDeviceOnceCoupons() async throws -> [DeviceOnceCouponItem]
    func claimDeviceOnceCoupon(id: Int) async throws
    func redeemCoupon(id: Int) async throws -> CouponRedemptionResult
    func fetchStampCards() async throws -> [StampCardItem]

    func fetchProducts() async throws -> [ProductItem]
    func createOrder(items: [OrderLineRequest], paymentMethod: String, notes: String?) async throws -> OrderSnapshot
    func createPaymentCheckout(orderId: Int) async throws -> PaymentCheckoutSession
    func cancelUnpaidOrder(id: Int) async throws
    func fetchOrders() async throws -> [OrderSnapshot]
    func fetchOrder(id: Int) async throws -> OrderSnapshot
    func fetchWalletPassDownloadURL() async throws -> String

    func fetchCurrentQueueStatus() async throws -> CurrentQueueSnapshot
    func joinQueue(partySize: Int) async throws -> QueueEntry
    func cancelQueueEntry(id: Int) async throws
    func fetchQueueByToken(_ token: String) async throws -> QueueEntry

    func fetchPublishedAnnouncements() async throws -> [AnnouncementItem]
    func fetchGalleryContent(categoryId: Int?) async throws -> GallerySnapshot
    func fetchMemberReservations() async throws -> [ReservationItem]
    func bookReservation(reservedAt: Date, numberOfPeople: Int, notes: String?) async throws
    func cancelReservationBooking(id: Int) async throws
}
