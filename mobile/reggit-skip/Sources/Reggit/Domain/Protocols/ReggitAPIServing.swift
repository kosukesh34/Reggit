import Foundation

struct ExistingMemberLinkFieldItem: Equatable, Sendable, Identifiable {
    let key: String
    let label: String
    var id: String { key }
}

struct ExistingMemberLinkForm: Equatable, Sendable {
    let isEnabled: Bool
    let description: String
    let fields: [ExistingMemberLinkFieldItem]
}

struct ExistingMemberLinkResult: Equatable, Sendable {
    let linked: Bool
    let message: String
}

struct BusinessStore: Equatable, Sendable, Identifiable {
    let id: Int
    let name: String
}

struct TransferCode: Equatable, Sendable {
    let memberId: Int
    let code: String
    let expiresAt: String
}

/// 顧客向け REST API の境界。Presentation は具象クライアントではなくこのプロトコルに依存する。
protocol ReggitAPIServing: Sendable {
    var baseURL: String { get }

    func fetchStores() async throws -> [BusinessStore]

    func registerDevice(
        platform: String,
        pushToken: String?,
        osVersion: String?,
        appVersion: String?
    ) async throws

    func fetchMemberProfile() async throws -> MemberProfileSnapshot
    func withdrawMember() async throws
    func issueTransferCode() async throws -> TransferCode
    func claimTransferCode(memberId: Int, code: String) async throws
    func fetchExistingMemberLinkForm() async throws -> ExistingMemberLinkForm
    func submitExistingMemberLink(values: [String: String]) async throws -> ExistingMemberLinkResult
    func confirmExistingMemberLink(token: String) async throws -> ExistingMemberLinkResult
    func registerMember(name: String, phone: String) async throws -> MemberProfileSnapshot
    func registerMember(name: String, phone: String, profilePayload: [String: String], profileListPayload: [String: [String]]) async throws -> MemberProfileSnapshot
    func updateMemberProfile(name: String, phone: String, profilePayload: [String: String], profileListPayload: [String: [String]]) async throws -> MemberProfileSnapshot
    func fetchPointCard() async throws -> PointCardSnapshot
    func fetchPointHistory() async throws -> [PointHistoryItem]

    func fetchMemberCoupons() async throws -> [CouponItem]
    func fetchDeviceOnceCoupons() async throws -> [DeviceOnceCouponItem]
    func claimDeviceOnceCoupon(id: Int) async throws
    func redeemCoupon(id: Int) async throws -> CouponRedemptionResult
    func fetchStampCards() async throws -> [StampCardItem]
    func fetchLotteryState() async throws -> LotteryStateSnapshot
    func playLottery(entitlementId: Int) async throws -> LotteryPlayResult
    func notifyLotteryWin(entitlementId: Int) async throws

    func fetchPaymentMethods() async throws -> [String]
    func fetchProducts() async throws -> [ProductItem]
    func createOrder(items: [OrderLineRequest], paymentMethod: String, notes: String?) async throws -> OrderSnapshot
    func createPaymentCheckout(orderId: Int) async throws -> PaymentCheckoutSession
    func confirmPaymentCheckout(orderId: Int) async throws -> OrderSnapshot
    func cancelUnpaidOrder(id: Int) async throws
    func fetchOrders() async throws -> [OrderSnapshot]
    func fetchOrder(id: Int) async throws -> OrderSnapshot
    func fetchWalletPassDownloadURL() async throws -> String

    func fetchCurrentQueueStatus() async throws -> CurrentQueueSnapshot
    func joinQueue(partySize: Int) async throws -> QueueEntry
    func cancelQueueEntry(id: Int) async throws
    func fetchQueueByToken(_ token: String) async throws -> QueueEntry

    func fetchAnnouncementImage(id: Int) async throws -> Data
    func fetchPublishedAnnouncements() async throws -> [AnnouncementItem]
    func fetchGalleryContent(categoryId: Int?) async throws -> GallerySnapshot
    func fetchMemberReservations() async throws -> [ReservationItem]
    func fetchReservationCourses() async throws -> [ReservationCourseItem]
    func bookReservation(reservedAt: Date, numberOfPeople: Int, notes: String?, courseId: Int?) async throws
    func cancelReservationBooking(id: Int) async throws
}

extension ReggitAPIServing {
    func fetchStores() async throws -> [BusinessStore] { [] }
    func fetchPaymentMethods() async throws -> [String] { ["card", "cash"] }

    func confirmExistingMemberLink(token: String) async throws -> ExistingMemberLinkResult {
        throw ReggitAPIError.invalidResponse
    }

    func fetchAnnouncementImage(id: Int) async throws -> Data {
        throw ReggitAPIError.invalidResponse
    }
}
