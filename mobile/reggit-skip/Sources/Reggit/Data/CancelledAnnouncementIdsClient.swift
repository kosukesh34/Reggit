import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

/// 削除済みお知らせの通知取消し情報。
public struct CancelledAnnouncementItem: Equatable {
    public let id: String
    public let title: String

    public init(id: String, title: String = "") {
        self.id = id
        self.title = title
    }
}

/// 削除済みお知らせの通知取消し ID を取得する薄いクライアント。
public struct CancelledAnnouncementIdsClient {
    public let baseUrl: String
    public let projectKey: String

    public init(baseUrl: String? = nil, projectKey: String? = nil) {
        let shellConfig = BundleMobileWebConfigRepository().resolveConfig()
        self.baseUrl = (baseUrl ?? shellConfig.baseUrl).trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        self.projectKey = projectKey ?? shellConfig.projectKey
    }

    /// 削除済みお知らせ ID を取得する。
    public func fetchItems() async -> [CancelledAnnouncementItem] {
        let encodedKey = projectKey.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? projectKey
        let urlString = "\(baseUrl)/api/v1/announcements/cancelled-ids?project_key=\(encodedKey)"
        guard let url = URL(string: urlString) else { return [] }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("1", forHTTPHeaderField: "ngrok-skip-browser-warning")
        request.timeoutInterval = 8

        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            guard let httpResponse = response as? HTTPURLResponse else {
                return []
            }
            guard httpResponse.statusCode == 200 else {
                logger.warning("CancelledAnnouncementIds fetch status=\(httpResponse.statusCode)")
                return []
            }
            return Self.parseItems(data)
        } catch {
            logger.warning("CancelledAnnouncementIds fetch failed: \(error.localizedDescription)")
            return []
        }
    }

    public static func parseItems(_ data: Data) -> [CancelledAnnouncementItem] {
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            return []
        }

        if let rawItems = json["items"] as? [[String: Any]] {
            let items = rawItems.compactMap { raw -> CancelledAnnouncementItem? in
                let id = stringifyId(raw["id"])
                guard !id.isEmpty else { return nil }
                let title = (raw["title"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
                return CancelledAnnouncementItem(id: id, title: title)
            }
            if !items.isEmpty {
                return items
            }
        }

        let rawIds = json["cancelled_ids"] as? [Any] ?? []
        return rawIds.compactMap { value -> CancelledAnnouncementItem? in
            let id = stringifyId(value)
            return id.isEmpty ? nil : CancelledAnnouncementItem(id: id)
        }
    }

    private static func stringifyId(_ value: Any?) -> String {
        if let number = value as? NSNumber {
            let id = number.stringValue
            return id.isEmpty || id == "0" ? "" : id
        }
        if let string = value as? String {
            let trimmed = string.trimmingCharacters(in: .whitespacesAndNewlines)
            return trimmed.isEmpty || trimmed == "0" ? "" : trimmed
        }
        return ""
    }
}
