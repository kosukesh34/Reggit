import Foundation

struct AnnouncementItem: Identifiable, Equatable, Hashable {
    let id: Int
    let title: String
    let body: String
    let imageURL: String?
    let publishedAt: String
    let isPinned: Bool
    let isRead: Bool
    var imageURLs: [String] = []
    /// お知らせから開くアプリ内パス（アンケートの依頼・予約リマインドなど）
    var actionPath: String? = nil
    var actionLabel: String? = nil
}

struct AnnouncementListResponse {
    let announcements: [AnnouncementItem]

    static func parse(_ data: Data) throws -> AnnouncementListResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let raw = (json["announcements"] as? [[String: Any]]) ?? []
        let items = raw.compactMap { row -> AnnouncementItem? in
            let idValue = row["id"]
            let id: Int
            if let intId = idValue as? Int {
                id = intId
            } else if let stringId = idValue as? String, let parsed = Int(stringId) {
                id = parsed
            } else {
                return nil
            }
            return AnnouncementItem(
                id: id,
                title: (row["title"] as? String) ?? "",
                body: (row["body"] as? String) ?? "",
                imageURL: (row["image_url"] as? String)?.trimmedNonEmpty,
                publishedAt: (row["published_at"] as? String) ?? "",
                isPinned: row["is_pinned"] as? Bool ?? false,
                isRead: row["is_read"] as? Bool ?? false,
                imageURLs: (row["image_urls"] as? [String] ?? []).compactMap { $0.trimmedNonEmpty },
                actionPath: (row["action_path"] as? String)?.trimmedNonEmpty,
                actionLabel: (row["action_label"] as? String)?.trimmedNonEmpty
            )
        }
        return AnnouncementListResponse(announcements: items)
    }
}

struct GalleryCategoryItem: Identifiable, Equatable {
    let id: Int
    let name: String
}

struct GalleryItem: Identifiable, Equatable, Hashable {
    let id: Int
    let title: String
    let description: String
    let imageURL: String?
    let categoryId: Int?
    let displaySize: String

    var isFeatured: Bool { displaySize == "large" }
}

struct GallerySnapshot {
    let categories: [GalleryCategoryItem]
    let items: [GalleryItem]

    static func parse(_ data: Data) throws -> GallerySnapshot {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let categories = ((json["categories"] as? [[String: Any]]) ?? []).compactMap { row -> GalleryCategoryItem? in
            guard let id = row["id"] as? Int else { return nil }
            return GalleryCategoryItem(id: id, name: (row["name"] as? String) ?? "カテゴリ")
        }
        let items = ((json["items"] as? [[String: Any]]) ?? []).compactMap { row -> GalleryItem? in
            guard let id = row["id"] as? Int else { return nil }
            return GalleryItem(
                id: id,
                title: (row["title"] as? String) ?? "",
                description: (row["description"] as? String) ?? "",
                imageURL: (row["image_url"] as? String)?.trimmedNonEmpty,
                categoryId: row["category_id"] as? Int,
                displaySize: (row["size"] as? String) ?? "medium"
            )
        }
        return GallerySnapshot(categories: categories, items: items)
    }
}

struct ReservationItem: Identifiable, Equatable {
    let id: Int
    let reservedAt: String
    let status: String
    let reservationNumber: String
    let numberOfPeople: Int
    let notes: String
    let courseName: String?
    var courseId: Int? = nil
    /// 会員が変更・キャンセルできる最後の時刻（店舗のタイムゾーン付き ISO）
    var changeDeadline: String? = nil
    /// いま変更・キャンセルできるか。古いサーバーでは nil
    var canChange: Bool? = nil

    var statusLabel: String {
        switch status {
        case "pending", "confirmed": return "予約済み"
        case "cancelled": return "キャンセル"
        case "completed": return "完了"
        default: return status
        }
    }

    var isActive: Bool {
        status == "pending" || status == "confirmed"
    }

    var isCancellable: Bool {
        isActive && canChange != false
    }

    /// 予約カードに出す変更・キャンセルの案内。終わった予約は nil
    var changeNote: String? {
        guard isActive, let canChange else { return nil }
        guard canChange else { return "変更・キャンセルは店舗へ直接ご連絡ください" }
        let deadline = ReservationStoreDateTime(iso: changeDeadline)
        guard !deadline.date.isEmpty else { return nil }
        let parts = deadline.date.split(separator: "-").compactMap { Int($0) }
        guard parts.count == 3 else { return nil }
        return "\(parts[1])月\(parts[2])日 \(deadline.time) まで変更・キャンセルできます"
    }
}

/// サーバーの ISO 日時（店舗のタイムゾーン付き）から、端末の時刻に関係なく店舗の日付と時刻を取り出す
struct ReservationStoreDateTime: Equatable {
    let date: String
    let time: String

    init(iso: String?) {
        let value = iso ?? ""
        guard value.count >= 16, Array(value)[10] == "T" else {
            date = ""
            time = ""
            return
        }
        date = String(value.prefix(10))
        time = String(value.dropFirst(11).prefix(5))
    }

    /// 予約 API に送る値。店舗のタイムゾーンとして扱われる
    static func requestValue(date: String, time: String) -> String {
        "\(date)T\(time)"
    }
}

struct ReservationCourseItem: Identifiable, Equatable {
    let id: Int
    let name: String
    let price: Int
}

struct ReservationDateOption: Identifiable, Equatable {
    let date: String
    let display: String
    let timeSlots: [String]

    var id: String { date }
}

/// GET /api/reservations/available-dates。指定した人数が入る空き枠だけが返る
struct ReservationAvailability: Equatable {
    let dates: [ReservationDateOption]
    let courses: [ReservationCourseItem]
    let maxPartySize: Int

    static func parse(_ data: Data) throws -> ReservationAvailability {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let dates = ((json["available_dates"] as? [[String: Any]]) ?? []).compactMap { row -> ReservationDateOption? in
            guard let date = row["date"] as? String else { return nil }
            let display = (row["display"] as? String) ?? date
            // 「2026年07月11日(土)」の年は省いて短く見せる
            let short = display.count > 5 && display.contains("年") ? String(display.split(separator: "年", maxSplits: 1)[1]) : display
            return ReservationDateOption(date: date, display: short, timeSlots: (row["time_slots"] as? [String]) ?? [])
        }
        let courses = ((json["courses"] as? [[String: Any]]) ?? []).compactMap { row -> ReservationCourseItem? in
            guard let id = row["id"] as? Int else { return nil }
            return ReservationCourseItem(id: id, name: row["name"] as? String ?? "コース", price: row["price"] as? Int ?? 0)
        }
        return ReservationAvailability(dates: dates, courses: courses, maxPartySize: max(1, json["max_party_size"] as? Int ?? 10))
    }
}

struct ReservationListResponse {
    let reservations: [ReservationItem]

    static func parse(_ data: Data) throws -> ReservationListResponse {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        let raw = (json["reservations"] as? [[String: Any]]) ?? []
        let items = raw.compactMap { row -> ReservationItem? in
            guard let id = row["id"] as? Int else { return nil }
            let number: String
            if let n = row["reservation_number"] as? String {
                number = n
            } else if let n = row["reservation_number"] as? Int {
                number = "\(n)"
            } else {
                number = "#\(id)"
            }
            return ReservationItem(
                id: id,
                reservedAt: (row["reserved_at"] as? String) ?? "",
                status: (row["status"] as? String) ?? "",
                reservationNumber: number,
                numberOfPeople: row["number_of_people"] as? Int ?? 1,
                notes: (row["notes"] as? String) ?? "",
                courseName: row["course_name"] as? String,
                courseId: row["reservation_course_id"] as? Int,
                changeDeadline: row["change_deadline"] as? String,
                canChange: row["can_change"] as? Bool
            )
        }
        return ReservationListResponse(reservations: items)
    }
}
