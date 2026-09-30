import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

enum ReggitAPIError: Error, LocalizedError {
    case invalidURL
    case invalidResponse
    case server(String)
    case httpStatus(Int, String)
    case validation([String: String])

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "URLが不正です"
        case .invalidResponse:
            return "応答を解析できませんでした"
        case .server(let message):
            return message
        case .httpStatus(let code, let message):
            return message.isEmpty ? "HTTP \(code)" : message
        case .validation(let errors):
            return errors.map { "\($0.key): \($0.value)" }.sorted().joined(separator: "\n")
        }
    }
}

struct ReggitAPIClient: ReggitAPIServing {
    let baseURL: String
    let projectKey: String
    let deviceIdentifier: String
    private let session: URLSession
    private var credentialKey: String { "device-token:\(baseURL):\(projectKey):\(deviceIdentifier)" }

    init(
        baseURL: String,
        projectKey: String,
        deviceIdentifier: String,
        session: URLSession = .shared
    ) {
        self.baseURL = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        self.projectKey = projectKey
        self.deviceIdentifier = deviceIdentifier
        self.session = session
    }

    // MARK: - Device

    func registerDevice(
        platform: String,
        pushToken: String?,
        osVersion: String?,
        appVersion: String?
    ) async throws {
        var body: [String: Any] = [
            "platform": platform,
            "device_identifier": deviceIdentifier,
        ]
        if let pushToken, !pushToken.isEmpty {
            body["push_token"] = pushToken
        }
        if let osVersion, !osVersion.isEmpty {
            body["os_version"] = String(osVersion.prefix(50))
        }
        if let appVersion, !appVersion.isEmpty {
            body["app_version"] = String(appVersion.prefix(50))
        }
        let result = try await requestJSON(path: "/api/devices/register", method: "POST", body: body)
        guard let token = result["device_token"] as? String, !token.isEmpty else {
            throw ReggitAPIError.invalidResponse
        }
        UserDefaults.standard.set(token, forKey: credentialKey)
    }

    // MARK: - Member

    func fetchMemberProfile() async throws -> MemberProfileSnapshot {
        let data = try await requestData(path: "/api/member-profile", method: "GET")
        return try MemberProfileSnapshot.parse(data)
    }

    func withdrawMember() async throws {
        _ = try await requestData(path: "/api/members/withdraw", method: "POST")
    }

    func issueTransferCode() async throws -> TransferCode {
        let json = try await requestJSON(path: "/api/members/transfer-code", method: "POST")
        guard let data = json["data"] as? [String: Any],
              let code = data["code"] as? String, !code.isEmpty else {
            throw ReggitAPIError.invalidResponse
        }
        let memberId = data["member_id"] as? Int ?? (data["member_id"] as? NSNumber)?.intValue ?? 0
        guard memberId > 0 else { throw ReggitAPIError.invalidResponse }
        return TransferCode(memberId: memberId, code: code, expiresAt: data["expires_at"] as? String ?? "")
    }

    func claimTransferCode(memberId: Int, code: String) async throws {
        _ = try await requestJSON(
            path: "/api/members/transfer",
            method: "POST",
            body: ["member_id": memberId, "code": code]
        )
    }

    func fetchExistingMemberLinkForm() async throws -> ExistingMemberLinkForm {
        let json = try await requestJSON(path: "/api/members/existing-link", method: "GET")
        guard let data = json["data"] as? [String: Any] else { throw ReggitAPIError.invalidResponse }
        let rawFields = data["fields"] as? [[String: Any]] ?? []
        let fields = rawFields.compactMap { row -> ExistingMemberLinkFieldItem? in
            guard let key = row["key"] as? String, let label = row["label"] as? String else { return nil }
            return ExistingMemberLinkFieldItem(key: key, label: label)
        }
        return ExistingMemberLinkForm(
            isEnabled: data["is_enabled"] as? Bool ?? false,
            description: data["description"] as? String ?? "",
            fields: fields
        )
    }

    func submitExistingMemberLink(values: [String: String]) async throws -> ExistingMemberLinkResult {
        let json = try await requestJSON(path: "/api/members/existing-link", method: "POST", body: values)
        let data = json["data"] as? [String: Any]
        return ExistingMemberLinkResult(
            linked: data?["linked"] as? Bool ?? false,
            message: json["message"] as? String ?? "既存会員を連携しました"
        )
    }

    func authorizeWalletChannel(socketID: String, memberID: Int) async throws -> String {
        let response = try await requestJSON(
            path: "/api/broadcasting/auth",
            method: "POST",
            body: ["socket_id": socketID, "channel_name": "private-liff.member.\(memberID)"]
        )
        guard let auth = response["auth"] as? String, !auth.isEmpty else {
            throw ReggitAPIError.invalidResponse
        }
        return auth
    }

    func registerMember(name: String, phone: String) async throws -> MemberProfileSnapshot {
        try await registerMember(name: name, phone: phone, profilePayload: [:], profileListPayload: [:])
    }

    func registerMember(name: String, phone: String, profilePayload: [String: String], profileListPayload: [String: [String]]) async throws -> MemberProfileSnapshot {
        var body: [String: Any] = profilePayload
        for (key, values) in profileListPayload { body[key] = values }
        body["name"] = name
        body["phone"] = phone
        let data = try await requestData(
            path: "/api/members/register",
            method: "POST",
            body: body
        )
        return try MemberProfileSnapshot.parse(data)
    }

    func updateMemberProfile(name: String, phone: String, profilePayload: [String: String], profileListPayload: [String: [String]]) async throws -> MemberProfileSnapshot {
        var body: [String: Any] = profilePayload
        for (key, values) in profileListPayload { body[key] = values }
        body["name"] = name
        body["phone"] = phone
        let data = try await requestData(
            path: "/api/member-profile",
            method: "PUT",
            body: body
        )
        // PUT returns the saved profile. A second GET could fail after a successful save.
        return try MemberProfileSnapshot.parse(data)
    }

    func fetchPointCard() async throws -> PointCardSnapshot {
        let data = try await requestData(path: "/api/members/points/card", method: "GET")
        return try PointCardSnapshot.parse(data)
    }

    func fetchPointHistory() async throws -> [PointHistoryItem] {
        let data = try await requestData(path: "/api/members/points/history", method: "GET")
        return try PointHistoryItem.parseList(data)
    }

    // MARK: - Coupons / Stamps

    func fetchMemberCoupons() async throws -> [CouponItem] {
        let data = try await requestData(path: "/api/member-coupons", method: "GET")
        return try CouponListResponse.parse(data).coupons
    }

    func fetchDeviceOnceCoupons() async throws -> [DeviceOnceCouponItem] {
        let data = try await requestData(path: "/api/device-coupons", method: "GET")
        return try DeviceOnceCouponItem.parseList(data)
    }

    func claimDeviceOnceCoupon(id: Int) async throws {
        _ = try await requestJSON(path: "/api/device-coupons/\(id)/claim", method: "POST")
    }

    func redeemCoupon(id: Int) async throws -> CouponRedemptionResult {
        let data = try await requestData(path: "/api/member-coupons/\(id)/redeem", method: "POST")
        return try CouponRedemptionResult.parse(data)
    }

    func fetchStampCards() async throws -> [StampCardItem] {
        let data = try await requestData(path: "/api/stamps", method: "GET")
        return try StampListResponse.parse(data).stampCards
    }

    func fetchLotteryState() async throws -> LotteryStateSnapshot {
        let data = try await requestData(path: "/api/member-lottery", method: "GET")
        return try LotteryStateSnapshot.parse(data)
    }

    func playLottery(entitlementId: Int) async throws -> LotteryPlayResult {
        let data = try await requestData(path: "/api/member-lottery/\(entitlementId)/play", method: "POST")
        return try LotteryPlayResult.parse(data)
    }

    func notifyLotteryWin(entitlementId: Int) async throws {
        _ = try await requestJSON(path: "/api/member-lottery/\(entitlementId)/notify-win", method: "POST")
    }

    // MARK: - Products / Orders

    func fetchProducts() async throws -> [ProductItem] {
        let data = try await requestData(path: "/api/products", method: "GET")
        return try ProductListResponse.parse(data).products
    }

    func createOrder(
        items: [OrderLineRequest],
        paymentMethod: String,
        notes: String?
    ) async throws -> OrderSnapshot {
        var body: [String: Any] = [
            "items": items.map { $0.asDictionary() },
            "payment_method": paymentMethod,
            "order_type": "mobile",
        ]
        if let notes, !notes.isEmpty {
            body["notes"] = notes
        }
        let data = try await requestData(path: "/api/orders", method: "POST", body: body)
        return try OrderCreateResponse.parse(data).order
    }

    func createPaymentCheckout(orderId: Int) async throws -> PaymentCheckoutSession {
        let data = try await requestData(
            path: "/api/payment/checkout",
            method: "POST",
            body: ["order_id": orderId]
        )
        return try PaymentCheckoutSession.parse(data)
    }

    func confirmPaymentCheckout(orderId: Int) async throws -> OrderSnapshot {
        let data = try await requestData(
            path: "/api/payment/checkout/confirm",
            method: "POST",
            body: ["order_id": orderId]
        )
        return try OrderSnapshot.parse(data)
    }

    func cancelUnpaidOrder(id: Int) async throws {
        _ = try await requestData(path: "/api/orders/\(id)/cancel-unpaid", method: "POST")
    }

    func fetchOrders() async throws -> [OrderSnapshot] {
        let data = try await requestData(path: "/api/orders", method: "GET")
        return try OrderListResponse.parse(data).orders
    }

    func fetchOrder(id: Int) async throws -> OrderSnapshot {
        let data = try await requestData(path: "/api/orders/\(id)", method: "GET")
        return try OrderSnapshot.parse(data)
    }

    /// Apple Wallet 用 .pkpass のワンタイムダウンロード URL を返す。
    func fetchWalletPassDownloadURL() async throws -> String {
        let data = try await requestData(path: "/api/member-wallet/pass-url", method: "GET")
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        if let error = (json["error"] as? String)?.trimmedNonEmpty {
            throw ReggitAPIError.server(error)
        }
        guard let issueURL = (json["url"] as? String)?.trimmedNonEmpty else {
            throw ReggitAPIError.server("Wallet Pass の URL を取得できませんでした")
        }
        return try WalletPassURLBuilder.downloadURL(
            fromIssueURL: issueURL,
            baseURL: baseURL,
            projectKey: projectKey
        )
    }

    // MARK: - Queue

    func fetchCurrentQueueStatus() async throws -> CurrentQueueSnapshot {
        let data = try await requestData(path: "/api/queue/current", method: "GET")
        return try CurrentQueueSnapshot.parse(data)
    }

    func joinQueue(partySize: Int) async throws -> QueueEntry {
        let data = try await requestData(
            path: "/api/queue",
            method: "POST",
            body: ["party_size": partySize]
        )
        return try QueueCreateResponse.parse(data).entry
    }

    func cancelQueueEntry(id: Int) async throws {
        _ = try await requestJSON(path: "/api/queue/\(id)/cancel", method: "POST")
    }

    func fetchQueueByToken(_ token: String) async throws -> QueueEntry {
        let encoded = token.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? token
        let data = try await requestData(path: "/api/queue/qr/\(encoded)", method: "GET")
        return try QueueQrResponse.parse(data).entry
    }

    // MARK: - Home / Gallery / Reservations

    func fetchPublishedAnnouncements() async throws -> [AnnouncementItem] {
        let data = try await requestData(path: "/api/announcements", method: "GET")
        return try AnnouncementListResponse.parse(data).announcements
    }

    func fetchGalleryContent(categoryId: Int?) async throws -> GallerySnapshot {
        var path = "/api/gallery"
        if let categoryId {
            path += "?category_id=\(categoryId)"
        }
        let data = try await requestData(path: path, method: "GET")
        return try GallerySnapshot.parse(data)
    }

    func fetchMemberReservations() async throws -> [ReservationItem] {
        let data = try await requestData(path: "/api/reservations", method: "GET")
        return try ReservationListResponse.parse(data).reservations
    }

    func fetchReservationCourses() async throws -> [ReservationCourseItem] {
        let data = try await requestData(path: "/api/reservations/available-dates", method: "GET")
        return try ReservationCourseItem.parseList(data)
    }

    func bookReservation(reservedAt: Date, numberOfPeople: Int, notes: String?, courseId: Int?) async throws {
        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withInternetDateTime]
        var body: [String: Any] = [
            "reserved_at": formatter.string(from: reservedAt),
            "number_of_people": numberOfPeople,
        ]
        if let notes, !notes.isEmpty {
            body["notes"] = notes
        }
        if let courseId {
            body["reservation_course_id"] = courseId
        }
        _ = try await requestJSON(path: "/api/reservations", method: "POST", body: body)
    }

    func cancelReservationBooking(id: Int) async throws {
        _ = try await requestJSON(path: "/api/reservations/\(id)/cancel", method: "POST")
    }

    // MARK: - HTTP

    private func requestJSON(
        path: String,
        method: String,
        body: [String: Any]? = nil
    ) async throws -> [String: Any] {
        let data = try await requestData(path: path, method: method, body: body)
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        return json
    }

    private func requestData(
        path: String,
        method: String,
        body: [String: Any]? = nil
    ) async throws -> Data {
        guard let url = URL(string: "\(baseURL)\(path)") else {
            throw ReggitAPIError.invalidURL
        }
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue(projectKey, forHTTPHeaderField: "X-Project-Key")
        request.setValue(deviceIdentifier, forHTTPHeaderField: "X-Device-Identifier")
        if let token = UserDefaults.standard.string(forKey: credentialKey) {
            request.setValue(token, forHTTPHeaderField: "X-Device-Token")
        }
        request.setValue(WebShellHTTP.ngrokSkipBrowserWarning, forHTTPHeaderField: "ngrok-skip-browser-warning")
        request.timeoutInterval = 30
        if let body {
            request.httpBody = try JSONSerialization.data(withJSONObject: body)
        }

        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw ReggitAPIError.invalidResponse
        }
        guard (200 ..< 300).contains(http.statusCode) else {
            if http.statusCode == 422, let errors = Self.extractValidationErrors(from: data), !errors.isEmpty {
                throw ReggitAPIError.validation(errors)
            }
            let message = Self.extractErrorMessage(from: data)
            throw ReggitAPIError.httpStatus(http.statusCode, message)
        }
        return data
    }

    static func extractErrorMessage(from data: Data) -> String {
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            return String(data: data, encoding: .utf8) ?? ""
        }
        if let errors = json["errors"] as? [String: Any] {
            let messages = errors.values.compactMap { value -> String? in
                if let list = value as? [String], let first = list.first, !first.isEmpty {
                    return first
                }
                if let string = value as? String, !string.isEmpty {
                    return string
                }
                return nil
            }
            if let first = messages.first {
                return first
            }
        }
        if let message = json["message"] as? String, !message.isEmpty {
            return message
        }
        if let error = json["error"] as? String, !error.isEmpty {
            return error
        }
        return ""
    }

    static func extractValidationErrors(from data: Data) -> [String: String]? {
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let errors = json["errors"] as? [String: Any] else { return nil }
        var result: [String: String] = [:]
        for (key, value) in errors {
            if let list = value as? [String], let first = list.first { result[key] = first }
            else if let message = value as? String { result[key] = message }
        }
        return result
    }

    static func apiHealthURL(baseURL: String, projectKey: String) -> String {
        let base = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        return "\(base)/api/v1/app-config?project_key=\(projectKey)"
    }
}
