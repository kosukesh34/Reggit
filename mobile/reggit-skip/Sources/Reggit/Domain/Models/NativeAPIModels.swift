import Foundation

enum MemberRegistrationMode: String, Equatable {
    case optional
    case popup
    case prompt
    case force

    /// 起動時に登録画面を出す必要があるか（未会員時）
    var requiresRegistrationGate: Bool {
        switch self {
        case .force, .prompt:
            return true
        case .optional, .popup:
            return false
        }
    }

    var canSkipRegistration: Bool {
        self != .force
    }

    static func parse(_ raw: String?) -> MemberRegistrationMode {
        guard let raw, let mode = MemberRegistrationMode(rawValue: raw) else {
            return .optional
        }
        return mode
    }
}

struct MemberProfileSnapshot: Equatable {
    let isMember: Bool
    let userId: Int?
    let memberId: Int?
    let name: String
    let phone: String
    let memberNumber: String
    let points: Int
    let rankLabel: String
    let nextRankLabel: String?
    let pointsToNextRank: Int?
    let showRankProgress: Bool
    let cardCodeType: String
    let message: String?
    let registrationMode: MemberRegistrationMode
    let cardLogoURL: String?
    let profileImageURL: String?
    let rankColorHex: String?
    let pointsExpiryLabel: String?
    /// PUT /member-profile 用。既存プロフィールをマージして必須項目欠落を防ぐ。
    let profilePayload: [String: String]
    let profileListPayload: [String: [String]]
    let profileFields: [ProfileFieldDefinition]
    let profileImageEnabled: Bool

    static func parse(_ data: Data) throws -> MemberProfileSnapshot {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let isMember = json["is_member"] as? Bool ?? false
        let profile = json["profile"] as? [String: Any] ?? [:]
        let member = json["member"] as? [String: Any] ?? [:]
        let user = json["user"] as? [String: Any] ?? [:]
        let name = (profile["name"] as? String)
            ?? (member["name"] as? String)
            ?? (user["name"] as? String)
            ?? ""
        let phone = (profile["phone"] as? String)
            ?? (user["phone"] as? String)
            ?? ""
        let memberNumber = (member["member_number"] as? String)
            ?? (member["member_code"] as? String)
            ?? (json["member_number"] as? String)
            ?? ""
        let points = json["points"] as? Int ?? 0
        let rankLabel = (json["current_rank_label"] as? String)
            ?? (json["current_rank"] as? String)
            ?? ""
        let nextRankLabel = (json["next_rank_label"] as? String)?.trimmedNonEmpty
        let pointsToNext: Int?
        if let intValue = json["points_to_next_rank"] as? Int {
            pointsToNext = intValue
        } else if let doubleValue = json["points_to_next_rank"] as? Double {
            pointsToNext = Int(doubleValue)
        } else {
            pointsToNext = nil
        }
        let showRankProgress = json["show_rank_progress"] as? Bool ?? (pointsToNext != nil)
        let codeType = ((json["member_card_code_type"] as? String) ?? "barcode").lowercased()
        let message = json["message"] as? String
        let mode = MemberRegistrationMode.parse(json["member_registration_mode"] as? String)
        let logo = (json["member_card_logo_url"] as? String)?.trimmedNonEmpty
        let profileImage = (profile["profile_image_url"] as? String)?.trimmedNonEmpty
        let rankColor = (json["current_rank_color"] as? String)?.trimmedNonEmpty
        let expiry = json["points_expiry"] as? [String: Any]
        let pointsExpiryLabel = (expiry?["expires_at_label"] as? String)?.trimmedNonEmpty
        let fields = (json["profile_field_settings"] as? [[String: Any]] ?? []).compactMap(ProfileFieldDefinition.parse)
        return MemberProfileSnapshot(
            isMember: isMember,
            userId: (json["user_id"] as? Int) ?? (user["id"] as? Int),
            memberId: member["id"] as? Int,
            name: name,
            phone: phone,
            memberNumber: memberNumber,
            points: points,
            rankLabel: rankLabel,
            nextRankLabel: nextRankLabel,
            pointsToNextRank: pointsToNext,
            showRankProgress: showRankProgress,
            cardCodeType: codeType,
            message: message,
            registrationMode: mode,
            cardLogoURL: logo,
            profileImageURL: profileImage,
            rankColorHex: rankColor,
            pointsExpiryLabel: pointsExpiryLabel,
            profilePayload: flattenProfilePayload(profile),
            profileListPayload: flattenProfileListPayload(profile),
            profileFields: fields,
            profileImageEnabled: json["profile_image_enabled"] as? Bool ?? false
        )
    }

    func withCard(_ card: PointCardSnapshot) -> MemberProfileSnapshot {
        MemberProfileSnapshot(
            isMember: isMember,
            userId: userId,
            memberId: memberId,
            name: name,
            phone: phone,
            memberNumber: card.memberNumber.isEmpty ? memberNumber : card.memberNumber,
            points: card.points > 0 ? card.points : points,
            rankLabel: card.rankLabel.isEmpty ? rankLabel : card.rankLabel,
            nextRankLabel: card.nextRankLabel ?? nextRankLabel,
            pointsToNextRank: card.pointsToNextRank ?? pointsToNextRank,
            showRankProgress: card.showRankProgress || showRankProgress,
            cardCodeType: card.cardCodeType.isEmpty ? cardCodeType : card.cardCodeType,
            message: message,
            registrationMode: registrationMode,
            cardLogoURL: cardLogoURL,
            profileImageURL: profileImageURL,
            rankColorHex: rankColorHex,
            pointsExpiryLabel: pointsExpiryLabel,
            profilePayload: profilePayload,
            profileListPayload: profileListPayload,
            profileFields: profileFields,
            profileImageEnabled: profileImageEnabled
        )
    }

    func withWalletPoints(_ latestPoints: Int) -> MemberProfileSnapshot {
        MemberProfileSnapshot(
            isMember: isMember,
            userId: userId,
            memberId: memberId,
            name: name,
            phone: phone,
            memberNumber: memberNumber,
            points: latestPoints,
            rankLabel: rankLabel,
            nextRankLabel: nextRankLabel,
            pointsToNextRank: pointsToNextRank.map { max(0, $0 - (latestPoints - points)) },
            showRankProgress: showRankProgress,
            cardCodeType: cardCodeType,
            message: message,
            registrationMode: registrationMode,
            cardLogoURL: cardLogoURL,
            profileImageURL: profileImageURL,
            rankColorHex: rankColorHex,
            pointsExpiryLabel: pointsExpiryLabel,
            profilePayload: profilePayload,
            profileListPayload: profileListPayload,
            profileFields: profileFields,
            profileImageEnabled: profileImageEnabled
        )
    }

    var editableProfileFields: [ProfileFieldDefinition] {
        // An explicit server definition is authoritative, including optional/disabled fields.
        if !profileFields.isEmpty { return profileFields }
        return [
            ProfileFieldDefinition(key: "name", label: "お名前", required: true, inputType: "text", values: [], description: nil),
            ProfileFieldDefinition(key: "phone", label: "電話番号", required: false, inputType: "tel", values: [], description: nil)
        ]
    }

    func profileValue(for field: ProfileFieldDefinition) -> String {
        if let list = profileListPayload[field.key], !list.isEmpty { return list.joined(separator: "、") }
        let value = profilePayload[field.key] ?? (field.key == "name" ? name : field.key == "phone" ? phone : "")
        return value.trimmed.isEmpty ? "未登録" : field.displayValue(value)
    }

    static func flattenProfilePayload(_ profile: [String: Any]) -> [String: String] {
        let skip: Set<String> = ["profile_image_url", "has_profile_image"]
        var out: [String: String] = [:]
        for (key, value) in profile {
            if skip.contains(key) { continue }
            if let string = value as? String {
                out[key] = string
            } else if let intValue = value as? Int {
                out[key] = String(intValue)
            } else if let doubleValue = value as? Double {
                out[key] = String(Int(doubleValue))
            } else if let boolValue = value as? Bool {
                out[key] = boolValue ? "1" : "0"
            }
        }
        return out
    }

    static func flattenProfileListPayload(_ profile: [String: Any]) -> [String: [String]] {
        profile.compactMapValues { value in
            guard let values = value as? [Any] else { return nil }
            return values.compactMap { $0 as? String }
        }
    }
}

struct ProfileFieldOption: Equatable {
    let value: String
    let label: String
}

struct ProfileFieldDefinition: Equatable, Identifiable {
    let key: String
    let label: String
    let required: Bool
    let inputType: String
    let values: [String]
    let description: String?
    let options: [ProfileFieldOption]

    init(key: String, label: String, required: Bool, inputType: String, values: [String], description: String?, options: [ProfileFieldOption] = []) {
        self.key = key
        self.label = label
        self.required = required
        self.inputType = inputType
        self.values = values
        self.description = description
        self.options = options
    }

    var id: String { key }

    var choices: [String] {
        if !options.isEmpty { return options.map(\.value) }
        if !values.isEmpty { return values }
        switch key {
        case "sex": return ["0", "1", "2"]
        case "mail_receive_flag": return ["1", "0"]
        case "notification_preference": return ["all", "coupon_only", "none"]
        default: return []
        }
    }

    func displayValue(_ value: String) -> String {
        if let option = options.first(where: { $0.value == value }) { return option.label }
        switch key {
        case "sex": return ["0": "未設定", "1": "男性", "2": "女性"][value] ?? value
        case "mail_receive_flag": return ["1": "許可する", "0": "許可しない"][value] ?? value
        case "notification_preference":
            return ["all": "すべてのお知らせ", "coupon_only": "クーポン情報のみ", "none": "受け取らない"][value] ?? value
        default: return value
        }
    }

    func validationMessage(value: String, selections: [String]) -> String? {
        if inputType == "checkbox" {
            return required && selections.isEmpty ? "\(label)を選択してください" : nil
        }
        let text = value.trimmed
        if required && text.isEmpty { return "\(label)を入力してください" }
        if !text.isEmpty && !choices.isEmpty && !choices.contains(text) { return "\(label)を選び直してください" }
        if !text.isEmpty && (key == "email" || inputType == "email") {
            let parts = text.split(separator: "@", omittingEmptySubsequences: false)
            if parts.count != 2 || parts[0].isEmpty || !parts[1].contains(".") || text.contains(" ") {
                return "メールアドレスを確認してください"
            }
        }
        return nil
    }

    static func parse(_ row: [String: Any]) -> ProfileFieldDefinition? {
        guard let key = row["key"] as? String, !key.isEmpty else { return nil }
        return ProfileFieldDefinition(
            key: key,
            label: (row["label"] as? String)?.trimmedNonEmpty ?? key,
            required: row["required"] as? Bool ?? false,
            inputType: (row["input_type"] as? String) ?? "text",
            values: (row["values"] as? [Any])?.compactMap { $0 as? String } ?? [],
            description: (row["description"] as? String)?.trimmedNonEmpty,
            options: (row["options"] as? [[String: Any]] ?? []).compactMap { option in
                let value = (option["value"] as? String) ?? (option["value"] as? Int).map { String($0) }
                guard let value, !value.isEmpty else { return nil }
                return ProfileFieldOption(value: value, label: (option["label"] as? String)?.trimmedNonEmpty ?? value)
            }
        )
    }
}

struct PointCardSnapshot: Equatable {
    let memberNumber: String
    let points: Int
    let rankLabel: String
    let nextRankLabel: String?
    let pointsToNextRank: Int?
    let showRankProgress: Bool
    let cardCodeType: String

    static func parse(_ data: Data) throws -> PointCardSnapshot {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let pointsToNext: Int?
        if let intValue = json["points_to_next_rank"] as? Int {
            pointsToNext = intValue
        } else if let doubleValue = json["points_to_next_rank"] as? Double {
            pointsToNext = Int(doubleValue)
        } else {
            pointsToNext = nil
        }
        return PointCardSnapshot(
            memberNumber: json["member_number"] as? String ?? "",
            points: json["points"] as? Int ?? 0,
            rankLabel: (json["rank_label"] as? String) ?? (json["rank"] as? String) ?? "",
            nextRankLabel: (json["next_rank_label"] as? String)?.trimmedNonEmpty,
            pointsToNextRank: pointsToNext,
            showRankProgress: json["show_rank_progress"] as? Bool ?? (pointsToNext != nil),
            cardCodeType: ((json["member_card_code_type"] as? String) ?? "barcode").lowercased()
        )
    }
}

struct PointHistoryItem: Identifiable, Equatable {
    let id: Int
    let type: String
    let points: Int
    let description: String
    let createdAt: String

    static func parseList(_ data: Data) throws -> [PointHistoryItem] {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let rows = json["transactions"] as? [[String: Any]] else {
            throw ReggitAPIError.invalidResponse
        }
        return rows.compactMap { row in
            guard let id = row["id"] as? Int else { return nil }
            let rawPoints = row["points"]
            let points = rawPoints as? Int ?? Int((rawPoints as? Double) ?? 0)
            return PointHistoryItem(
                id: id,
                type: row["type"] as? String ?? "",
                points: points,
                description: row["description"] as? String ?? "ポイント更新",
                createdAt: row["created_at"] as? String ?? ""
            )
        }
    }
}

struct CouponItem: Identifiable, Equatable {
    let id: Int
    let name: String
    let status: String
    let description: String
    let validFrom: String
    let validUntil: String
    let issuedAt: String
    let usedAt: String
    let useDisplay: String
    let imageURL: String?

    init(
        id: Int,
        name: String,
        status: String,
        description: String,
        validFrom: String = "",
        validUntil: String,
        issuedAt: String = "",
        usedAt: String = "",
        useDisplay: String,
        imageURL: String?
    ) {
        self.id = id
        self.name = name
        self.status = status
        self.description = description
        self.validFrom = validFrom
        self.validUntil = validUntil
        self.issuedAt = issuedAt
        self.usedAt = usedAt
        self.useDisplay = useDisplay
        self.imageURL = imageURL
    }

    var isUsable: Bool {
        status == "available" || status == "unused" || status == "issued"
    }

    var isAvailableTab: Bool {
        status == "available" || status == "unused" || status == "issued" || status == "upcoming"
    }

    var isArchiveTab: Bool {
        status == "used" || status == "expired"
    }

    var statusLabel: String {
        switch status {
        case "used": return "利用済み"
        case "expired": return "期限切れ"
        case "upcoming": return "まもなく利用可能"
        default: return "利用可能"
        }
    }
}

struct CouponListResponse {
    let coupons: [CouponItem]

    static func parse(_ data: Data) throws -> CouponListResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let raw = json["coupons"] as? [[String: Any]] else {
            throw ReggitAPIError.invalidResponse
        }
        let defaultImage = (json["default_image_url"] as? String)?.trimmedNonEmpty
        let coupons = raw.compactMap { item -> CouponItem? in
            guard let id = item["id"] as? Int else { return nil }
            let image = (item["image_url"] as? String)?.trimmedNonEmpty ?? defaultImage
            return CouponItem(
                id: id,
                name: item["coupon_name"] as? String ?? "クーポン",
                status: item["status"] as? String ?? "",
                description: item["description"] as? String ?? "",
                validFrom: item["valid_from"] as? String ?? "",
                validUntil: item["valid_until"] as? String ?? "",
                issuedAt: item["issued_at"] as? String ?? "",
                usedAt: item["used_at"] as? String ?? "",
                useDisplay: item["use_display"] as? String ?? "",
                imageURL: image
            )
        }
        return CouponListResponse(coupons: coupons)
    }
}

struct DeviceOnceCouponItem: Identifiable, Equatable {
    let id: Int
    let name: String
    let description: String
    let imageURL: String?
    let validUntil: String

    static func parseList(_ data: Data) throws -> [DeviceOnceCouponItem] {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let payload = json["data"] as? [String: Any] ?? json
        let rows = payload["coupons"] as? [[String: Any]] ?? []
        return rows.compactMap { row in
            guard let id = row["id"] as? Int else { return nil }
            return DeviceOnceCouponItem(
                id: id,
                name: row["name"] as? String ?? "クーポン",
                description: row["description"] as? String ?? "",
                imageURL: (row["image_url"] as? String)?.trimmedNonEmpty,
                validUntil: row["valid_until"] as? String ?? ""
            )
        }
    }
}

struct CouponRedemptionResult: Identifiable, Equatable, Hashable {
    let id: Int
    let couponName: String
    let status: String
    let usedAt: String
    let useDisplay: String
    let code: String

    var shouldShowBarcode: Bool {
        useDisplay.lowercased() == "barcode" && !code.isEmpty
    }

    static func parse(_ data: Data) throws -> CouponRedemptionResult {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let payload = json["data"] as? [String: Any] ?? json
        guard let id = payload["id"] as? Int else {
            throw ReggitAPIError.invalidResponse
        }
        return CouponRedemptionResult(
            id: id,
            couponName: payload["coupon_name"] as? String ?? "クーポン",
            status: payload["status"] as? String ?? "used",
            usedAt: payload["used_at"] as? String ?? "",
            useDisplay: payload["use_display"] as? String ?? "none",
            code: payload["code"] as? String ?? ""
        )
    }
}

struct StampCardItem: Identifiable, Equatable {
    let id: Int
    let name: String
    let currentStamps: Int
    let requiredStamps: Int
    let rewardDescription: String
    let imageURL: String?

    var progressLabel: String {
        "\(currentStamps)/\(requiredStamps)"
    }
}

struct StampListResponse {
    let stampCards: [StampCardItem]

    static func parse(_ data: Data) throws -> StampListResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let raw = json["stamp_cards"] as? [[String: Any]] else {
            throw ReggitAPIError.invalidResponse
        }
        let defaultImage = (json["default_image_url"] as? String)?.trimmedNonEmpty
        let cards = raw.compactMap { item -> StampCardItem? in
            guard let id = item["id"] as? Int else { return nil }
            let image = (item["image_url"] as? String)?.trimmedNonEmpty ?? defaultImage
            return StampCardItem(
                id: id,
                name: item["name"] as? String ?? "スタンプカード",
                currentStamps: item["current_stamps"] as? Int ?? 0,
                requiredStamps: item["required_stamps"] as? Int ?? 0,
                rewardDescription: item["reward_description"] as? String ?? "",
                imageURL: image
            )
        }
        return StampListResponse(stampCards: cards)
    }
}

struct ProductItem: Identifiable, Equatable, Hashable {
    let id: Int
    let name: String
    let description: String
    let price: Int
    let isAvailable: Bool
    let stock: Int?
    let category: String
    let imageURL: String?
    let optionGroups: [ProductOptionGroupItem]
}

struct ProductOptionGroupItem: Identifiable, Equatable, Hashable {
    let id: Int
    let name: String
    let selectionType: String
    let isRequired: Bool
    let options: [ProductOptionItem]
}

struct ProductOptionItem: Identifiable, Equatable, Hashable {
    let id: Int
    let name: String
    let priceDelta: Int
}

struct ProductListResponse {
    let products: [ProductItem]

    static func parse(_ data: Data) throws -> ProductListResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let raw = json["products"] as? [[String: Any]] else {
            throw ReggitAPIError.invalidResponse
        }
        let products = raw.compactMap { item -> ProductItem? in
            guard let id = item["id"] as? Int else { return nil }
            let priceValue: Int
            if let intPrice = item["price"] as? Int {
                priceValue = intPrice
            } else if let doublePrice = item["price"] as? Double {
                priceValue = Int(doublePrice)
            } else if let stringPrice = item["price"] as? String, let parsed = Double(stringPrice), parsed.isFinite, parsed >= 0, parsed < Double(Int.max) {
                priceValue = Int(parsed)
            } else {
                priceValue = 0
            }
            return ProductItem(
                id: id,
                name: item["name"] as? String ?? "商品",
                description: item["description"] as? String ?? "",
                price: priceValue,
                isAvailable: item["is_available"] as? Bool ?? ((item["is_available"] as? NSNumber)?.boolValue ?? true),
                stock: Self.optionalInt(item["stock"]),
                category: item["category"] as? String ?? "",
                imageURL: (item["image_url"] as? String)?.trimmedNonEmpty,
                optionGroups: (item["option_groups"] as? [[String: Any]] ?? []).compactMap { group in
                    guard let groupID = group["id"] as? Int else { return nil }
                    return ProductOptionGroupItem(
                        id: groupID,
                        name: group["name"] as? String ?? "オプション",
                        selectionType: group["selection_type"] as? String ?? "single",
                        isRequired: group["is_required"] as? Bool ?? false,
                        options: (group["options"] as? [[String: Any]] ?? []).compactMap { option in
                            guard let optionID = option["id"] as? Int else { return nil }
                            let rawDelta = option["price_delta"]
                            let delta: Int
                            if let value = rawDelta as? Int { delta = value }
                            else if let value = rawDelta as? Double { delta = Int(value) }
                            else if let value = rawDelta as? String, let parsed = Double(value) { delta = Int(parsed) }
                            else { delta = 0 }
                            return ProductOptionItem(id: optionID, name: option["name"] as? String ?? "選択肢", priceDelta: delta)
                        }
                    )
                }
            )
        }
        return ProductListResponse(products: products)
    }

    private static func optionalInt(_ value: Any?) -> Int? {
        if value == nil || value is NSNull { return nil }
        if let intValue = value as? Int { return intValue }
        if let number = value as? NSNumber { return number.intValue }
        return nil
    }
}

struct OrderLineRequest {
    let productId: Int
    let quantity: Int
    var optionIds: [Int] = []
    var notes: String? = nil

    func asDictionary() -> [String: Any] {
        var result: [String: Any] = ["product_id": productId, "quantity": quantity, "option_ids": optionIds]
        if let notes { result["notes"] = notes }
        return result
    }
}

struct OrderLineItem: Identifiable, Equatable, Hashable {
    let id: Int
    let productName: String
    let quantity: Int
    let price: Int
    var productId: Int? = nil
    var optionIds: [Int]? = nil
    var notes: String? = nil
}

struct OrderSnapshot: Identifiable, Equatable, Hashable {
    let id: Int
    let orderNumber: String
    let totalAmount: Int
    let status: String
    let paymentMethod: String
    let createdAt: String
    let items: [OrderLineItem]

    var isActive: Bool {
        OrderProgress.ACTIVE_STATUSES.contains(status)
    }

    var statusLabel: String {
        OrderProgress.label(for: status)
    }

    var isAwaitingCardPayment: Bool { paymentMethod == "stripe" && status == "pending" }

    var hasPickupNumber: Bool { !orderNumber.isEmpty && !isAwaitingCardPayment }

    var pickupNumberLabel: String {
        guard hasPickupNumber else { return "決済確認中" }
        // 保存形式 YYYYMMDD+通番 は、画面では日+通番で表示する。
        if orderNumber.range(of: #"^[0-9]{9,}$"#, options: .regularExpression) != nil,
           let day = Int(orderNumber.dropFirst(6).prefix(2)) {
            return String(day) + orderNumber.dropFirst(8)
        }
        return orderNumber
    }

    static func parse(_ data: Data) throws -> OrderSnapshot {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let payload = (json["order"] as? [String: Any]) ?? json
        guard let order = parseDictionary(payload) else {
            throw ReggitAPIError.invalidResponse
        }
        return order
    }

    static func parseDictionary(_ order: [String: Any]) -> OrderSnapshot? {
        guard let id = order["id"] as? Int else { return nil }
        let total = Self.parseIntAmount(order["total_amount"])
        let rawItems = order["items"] as? [[String: Any]] ?? []
        let items: [OrderLineItem] = rawItems.enumerated().compactMap { index, item in
            let product = item["product"] as? [String: Any]
            let name = (item["product_name"] as? String)
                ?? (product?["name"] as? String)
                ?? "商品"
            let qty = item["quantity"] as? Int ?? 1
            let price = Self.parseIntAmount(item["price"])
            return OrderLineItem(id: index, productName: name, quantity: qty, price: price,
                                 productId: item["product_id"] as? Int ?? product?["id"] as? Int,
                                 optionIds: item["option_ids"] as? [Int], notes: item["notes"] as? String)
        }
        return OrderSnapshot(
            id: id,
            orderNumber: (order["order_number"] as? String)?.trimmedNonEmpty ?? "",
            totalAmount: total,
            status: order["status"] as? String ?? "",
            paymentMethod: order["payment_method"] as? String ?? "",
            createdAt: order["created_at"] as? String ?? "",
            items: items
        )
    }

    private static func parseIntAmount(_ raw: Any?) -> Int {
        if let intValue = raw as? Int { return intValue }
        if let doubleValue = raw as? Double { return Int(doubleValue) }
        if let stringValue = raw as? String, let doubleValue = Double(stringValue) {
            return Int(doubleValue)
        }
        return 0
    }
}

struct PaymentCheckoutSession: Equatable {
    let checkoutURL: String
    let sessionId: String

    static func parse(_ data: Data) throws -> PaymentCheckoutSession {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let url = (json["checkout_url"] as? String)?.trimmedNonEmpty else {
            throw ReggitAPIError.invalidResponse
        }
        return PaymentCheckoutSession(
            checkoutURL: url,
            sessionId: json["session_id"] as? String ?? ""
        )
    }
}

enum OrderProgress {
    static let ACTIVE_STATUSES = ["pending", "confirmed", "preparing", "ready"]

    static let steps: [(key: String, label: String, detail: String)] = [
        ("confirmed", "受付", "注文を受け付けました"),
        ("preparing", "準備中", "キッチンで準備しています"),
        ("ready", "受取可", "受け取りの準備が整いました"),
        ("completed", "完了", "ご注文ありがとうございました"),
    ]

    static func index(for status: String) -> Int {
        switch status {
        case "cancelled": return -1
        case "pending", "confirmed": return 0
        case "preparing": return 1
        case "ready": return 2
        case "completed": return 3
        default: return 0
        }
    }

    static func label(for status: String) -> String {
        switch status {
        case "pending", "confirmed": return "受付"
        case "preparing": return "準備中"
        case "ready": return "受取可"
        case "completed": return "完了"
        case "cancelled": return "キャンセル"
        default: return status
        }
    }
}

struct OrderCreateResponse {
    let order: OrderSnapshot

    static func parse(_ data: Data) throws -> OrderCreateResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let raw = json["order"] as? [String: Any],
              let order = OrderSnapshot.parseDictionary(raw) else {
            throw ReggitAPIError.invalidResponse
        }
        return OrderCreateResponse(order: order)
    }
}

struct OrderListResponse {
    let orders: [OrderSnapshot]

    static func parse(_ data: Data) throws -> OrderListResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let raw: [[String: Any]]
        if let nested = json["data"] as? [[String: Any]] {
            raw = nested
        } else if let direct = json["orders"] as? [[String: Any]] {
            raw = direct
        } else {
            raw = []
        }
        return OrderListResponse(orders: raw.compactMap(OrderSnapshot.parseDictionary))
    }
}

struct QueueEntry: Identifiable, Equatable {
    let id: Int
    let queueNumber: String
    let status: String
    let partySize: Int
    let waitingCount: Int
    let estimatedWaitMinutes: Int?
    let qrToken: String?

    var statusLabel: String {
        switch status {
        case "waiting": return "待ち"
        case "called": return "呼び出し中"
        case "entered": return "入場済み"
        case "cancelled": return "キャンセル"
        default: return status
        }
    }
}

struct CurrentQueueSnapshot: Equatable {
    let entry: QueueEntry?
    let waitingCount: Int
    let estimatedWaitMinutes: Int?
    let message: String?

    static func parse(_ data: Data) throws -> CurrentQueueSnapshot {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let entry = (json["queue_entry"] as? [String: Any]).flatMap(Self.parseEntry)
        return CurrentQueueSnapshot(
            entry: entry,
            waitingCount: json["waiting_count"] as? Int ?? entry?.waitingCount ?? 0,
            estimatedWaitMinutes: json["estimated_wait_minutes"] as? Int ?? entry?.estimatedWaitMinutes,
            message: json["message"] as? String
        )
    }

    static func parseEntry(_ json: [String: Any]) -> QueueEntry? {
        guard let id = json["id"] as? Int else { return nil }
        let formatted = json["formatted_queue_number"] as? String
        let number: String
        if let formatted, !formatted.isEmpty {
            number = formatted
        } else if let raw = json["queue_number"] as? Int {
            number = "\(raw)"
        } else if let rawString = json["queue_number"] as? String {
            number = rawString
        } else {
            number = "\(id)"
        }
        return QueueEntry(
            id: id,
            queueNumber: number,
            status: json["status"] as? String ?? "",
            partySize: json["party_size"] as? Int ?? 1,
            waitingCount: json["waiting_count"] as? Int ?? 0,
            estimatedWaitMinutes: json["estimated_wait_minutes"] as? Int,
            qrToken: json["qr_token"] as? String
        )
    }
}

struct QueueCreateResponse {
    let entry: QueueEntry

    static func parse(_ data: Data) throws -> QueueCreateResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let raw = json["queue_entry"] as? [String: Any],
              let entry = CurrentQueueSnapshot.parseEntry(raw) else {
            throw ReggitAPIError.invalidResponse
        }
        return QueueCreateResponse(entry: entry)
    }
}

struct QueueQrResponse {
    let entry: QueueEntry

    static func parse(_ data: Data) throws -> QueueQrResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let raw = (json["queue_entry"] as? [String: Any]) ?? json
        guard let entry = CurrentQueueSnapshot.parseEntry(raw) else {
            throw ReggitAPIError.invalidResponse
        }
        return QueueQrResponse(entry: entry)
    }
}

// MARK: - Lottery

struct LotteryEntitlement: Equatable, Identifiable {
    let id: Int
    let rewardType: String
    let expiresAt: String?
}

struct LotteryStateSnapshot: Equatable {
    let gachaEnabled: Bool
    let scratchEnabled: Bool
    let rouletteEnabled: Bool
    let entitlements: [LotteryEntitlement]
    let gachaPrizeLabels: [String]
    let scratchPrizeLabels: [String]
    let roulettePrizeLabels: [String]
    let grantTrigger: String
    let minimumPurchaseAmount: Double?
    let entitlementExpiresHours: Int?

    var playableEntitlements: [LotteryEntitlement] {
        entitlements.filter {
            switch $0.rewardType {
            case "gacha": return gachaEnabled
            case "scratch": return scratchEnabled
            case "roulette": return rouletteEnabled
            default: return false
            }
        }
    }

    func pending(for rewardType: String) -> LotteryEntitlement? {
        entitlements.first { $0.rewardType == rewardType }
    }

    static func parse(_ data: Data) throws -> LotteryStateSnapshot {
        guard let root = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let json = (root["data"] as? [String: Any]) ?? root
        let entitlements = (json["entitlements"] as? [[String: Any]] ?? []).compactMap { row -> LotteryEntitlement? in
            guard let id = row["id"] as? Int,
                  let rewardType = row["reward_type"] as? String else { return nil }
            return LotteryEntitlement(
                id: id,
                rewardType: rewardType,
                expiresAt: row["expires_at"] as? String
            )
        }
        let labels = json["prize_labels"] as? [String: Any] ?? [:]
        let eligibility = json["eligibility"] as? [String: Any] ?? [:]
        return LotteryStateSnapshot(
            gachaEnabled: json["gacha_enabled"] as? Bool ?? false,
            scratchEnabled: json["scratch_enabled"] as? Bool ?? false,
            rouletteEnabled: json["roulette_enabled"] as? Bool ?? false,
            entitlements: entitlements,
            gachaPrizeLabels: labels["gacha"] as? [String] ?? [],
            scratchPrizeLabels: labels["scratch"] as? [String] ?? [],
            roulettePrizeLabels: labels["roulette"] as? [String] ?? [],
            grantTrigger: eligibility["grant_trigger"] as? String ?? "every_purchase",
            minimumPurchaseAmount: (eligibility["min_amount"] as? NSNumber)?.doubleValue,
            entitlementExpiresHours: eligibility["entitlement_expires_hours"] as? Int
        )
    }
}

struct LotteryCouponPrize: Equatable {
    let id: Int
    let name: String
    let description: String?
    let imageURL: String?
}

struct LotteryPlayResult: Equatable {
    let entitlementId: Int
    let rewardType: String
    let isWin: Bool
    let coupon: LotteryCouponPrize?

    static func parse(_ data: Data) throws -> LotteryPlayResult {
        guard let root = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let outer = (root["data"] as? [String: Any]) ?? root
        let json = (outer["data"] as? [String: Any]) ?? outer
        guard let entitlementId = json["entitlement_id"] as? Int else {
            throw ReggitAPIError.invalidResponse
        }
        let rawCoupon = json["coupon"] as? [String: Any]
        let coupon: LotteryCouponPrize?
        if let rawCoupon,
           let id = rawCoupon["id"] as? Int,
           let name = rawCoupon["name"] as? String {
            coupon = LotteryCouponPrize(
                id: id,
                name: name,
                description: rawCoupon["description"] as? String,
                imageURL: rawCoupon["image_url"] as? String
            )
        } else {
            coupon = nil
        }
        return LotteryPlayResult(
            entitlementId: entitlementId,
            rewardType: json["reward_type"] as? String ?? "",
            isWin: (json["is_win"] as? Bool) ?? (coupon != nil),
            coupon: coupon
        )
    }
}
