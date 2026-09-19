import Foundation

struct AnnouncementItem: Identifiable, Equatable, Hashable {
    let id: Int
    let title: String
    let body: String
    let imageURL: String?
    let publishedAt: String
    let isPinned: Bool
    let isRead: Bool
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
                isRead: row["is_read"] as? Bool ?? false
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

    var statusLabel: String {
        switch status {
        case "pending", "confirmed": return "予約済み"
        case "cancelled": return "キャンセル"
        case "completed": return "完了"
        default: return status
        }
    }

    var isCancellable: Bool {
        status == "pending" || status == "confirmed"
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
                notes: (row["notes"] as? String) ?? ""
            )
        }
        return ReservationListResponse(reservations: items)
    }
}
