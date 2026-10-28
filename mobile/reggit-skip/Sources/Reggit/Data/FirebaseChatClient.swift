import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

/// `POST /api/member-chat/token` の応答。読み書きできる会話はトークンのクレームだけで決まる（firestore.rules）。
struct ChatSessionInfo: Equatable, Sendable {
    let token: String
    let apiKey: String
    let firebaseProjectId: String
    let storageBucket: String?
    let emulatorHost: String?
    let projectId: String
    let memberId: String
    /// トークンに署名済みの表示名・利用店舗。会話ドキュメントにはこの値しか書けない
    let label: String
    let storeIds: [Int]

    static func parse(_ data: Data) throws -> ChatSessionInfo {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let body = json["data"] as? [String: Any],
              let token = body["token"] as? String,
              let firebase = body["firebase"] as? [String: Any],
              let apiKey = firebase["apiKey"] as? String,
              let firebaseProjectId = firebase["projectId"] as? String,
              let projectId = Self.int(body["project_id"]),
              let memberId = Self.int(body["member_id"]),
              let claims = Self.claims(fromCustomToken: token) else {
            throw ReggitAPIError.invalidResponse
        }
        return ChatSessionInfo(
            token: token,
            apiKey: apiKey,
            firebaseProjectId: firebaseProjectId,
            storageBucket: (firebase["storageBucket"] as? String)?.trimmedNonEmpty,
            emulatorHost: (firebase["emulatorHost"] as? String)?.trimmedNonEmpty,
            projectId: String(projectId),
            memberId: String(memberId),
            label: claims["label"] as? String ?? "",
            storeIds: (claims["stores"] as? [Any] ?? []).compactMap { Self.int($0) }
        )
    }

    var imagesEnabled: Bool { storageBucket != nil }

    private static func claims(fromCustomToken token: String) -> [String: Any]? {
        let parts = token.split(separator: ".", omittingEmptySubsequences: false)
        guard parts.count >= 2 else { return nil }
        var base64 = parts[1].replacingOccurrences(of: "-", with: "+").replacingOccurrences(of: "_", with: "/")
        base64 += String(repeating: "=", count: (4 - base64.count % 4) % 4)
        guard let data = Data(base64Encoded: base64),
              let payload = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else { return nil }
        return payload["claims"] as? [String: Any]
    }

    private static func int(_ value: Any?) -> Int? {
        if let value = value as? Int { return value }
        if let value = value as? NSNumber { return value.intValue }
        if let value = value as? String { return Int(value) }
        return nil
    }
}

/// 店舗が送るクーポン・予約のカード
struct ChatCardItem: Equatable, Sendable {
    let type: String
    let title: String
    let subtitle: String

    /// ボタンで開くアプリのタブ
    var tabKey: String { type == "coupon" ? "coupons" : "reservations" }
    var actionLabel: String { type == "coupon" ? "クーポンを見る" : "予約を確認" }
    var icon: String { type == "coupon" ? "ticket.fill" : "calendar" }
}

struct ChatMessageItem: Identifiable, Equatable, Sendable {
    let id: String
    let sender: String
    let senderName: String
    let text: String
    let imagePath: String?
    var card: ChatCardItem? = nil
    var deleted = false
    var edited = false
    let createdAt: Date?
    /// 新着の取得に使う Firestore の時刻文字列（ナノ秒まで保持する）
    let createdAtRaw: String?

    var isMine: Bool { sender == "member" }
}

struct ChatThreadState: Equatable, Sendable {
    let lastMessageRaw: String?
    let lastSender: String?
    let lastMessageAt: Date?
    let memberReadAt: Date?
    /// 店舗が最後に会話を開いた時刻。会員のメッセージの「既読」に使う
    var staffReadAt: Date? = nil
    /// 店舗にブロックされている間は送信できない
    var blocked = false
    /// メッセージが削除された時刻。変わったら一覧を取り直す
    var editedRaw: String? = nil

    var hasUnreadReply: Bool {
        guard lastSender == "staff", let lastMessageAt else { return false }
        guard let memberReadAt else { return true }
        return memberReadAt < lastMessageAt
    }
}

/// 会員側のチャット。Firebase SDK を使わず REST API を呼ぶため、iOS・Android で同じコードが動く。
/// メッセージは Laravel を経由しない。Laravel は本人確認とトークン発行だけを行う。
actor FirebaseChatClient {
    static let textLimit = 2000

    private var session: ChatSessionInfo
    private let renewSession: @Sendable () async throws -> ChatSessionInfo
    private let urlSession: URLSession
    private var idToken: String?
    private var idTokenExpiresAt = Date.distantPast

    init(session: ChatSessionInfo, urlSession: URLSession = .shared, renewSession: @escaping @Sendable () async throws -> ChatSessionInfo) {
        self.session = session
        self.urlSession = urlSession
        self.renewSession = renewSession
    }

    var imagesEnabled: Bool { session.imagesEnabled }

    // MARK: - 会話

    /// 開いたとき・既読にするとき。表示名・利用店舗はトークンの値で更新する（ルールが一致を検証する）
    func openThread() async throws {
        var stores: [String: Any] = [:]
        if !session.storeIds.isEmpty {
            stores["values"] = session.storeIds.map { ["integerValue": String($0)] }
        }
        try await commit([[
            "update": ["name": documentName(threadPath), "fields": [
                "member_id": Self.string(session.memberId),
                "member_name": Self.string(session.label),
                "store_ids": ["arrayValue": stores],
            ]],
            "updateMask": ["fieldPaths": ["member_id", "member_name", "store_ids"]],
            "updateTransforms": [Self.requestTime("member_read_at")],
        ]])
    }

    func fetchThread() async throws -> ChatThreadState? {
        let data = try await request(method: "GET", url: try documentsURL(threadPath))
        guard let document = try JSONSerialization.jsonObject(with: data) as? [String: Any] else { return nil }
        return Self.parseThread(document)
    }

    /// after を指定すると、その時刻より後のメッセージだけを取得する
    func fetchMessages(after raw: String?) async throws -> [ChatMessageItem] {
        var query: [String: Any] = [
            "from": [["collectionId": "messages"]],
            "orderBy": [["field": ["fieldPath": "created_at"], "direction": raw == nil ? "DESCENDING" : "ASCENDING"]],
            "limit": 200,
        ]
        if let raw {
            query["where"] = ["fieldFilter": ["field": ["fieldPath": "created_at"], "op": "GREATER_THAN", "value": ["timestampValue": raw]]]
        }
        let data = try await request(method: "POST", url: try documentsURL(threadPath, suffix: ":runQuery"), body: ["structuredQuery": query])
        let rows = try JSONSerialization.jsonObject(with: data) as? [[String: Any]] ?? []
        let messages = rows.compactMap { ($0["document"] as? [String: Any]).flatMap(Self.parseMessage) }
        return raw == nil ? messages.reversed() : messages
    }

    func send(text: String) async throws {
        let body = text.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !body.isEmpty else { return }
        guard body.count <= Self.textLimit else { throw ReggitAPIError.server("メッセージは\(Self.textLimit)文字以内にしてください") }
        try await commit([
            [
                "update": ["name": documentName("\(threadPath)/messages/\(Self.documentID())"), "fields": [
                    "sender": Self.string("member"),
                    "sender_name": Self.string(session.label),
                    "text": Self.string(body),
                ]],
                "updateTransforms": [Self.requestTime("created_at")],
                "currentDocument": ["exists": false],
            ],
            [
                "update": ["name": documentName(threadPath), "fields": ["last_sender": Self.string("member")]],
                "updateMask": ["fieldPaths": ["last_sender"]],
                "updateTransforms": [Self.requestTime("last_message_at"), Self.requestTime("member_read_at")],
                "currentDocument": ["exists": true],
            ],
        ])
    }

    /// 自分の送信を取り消す。本文を消して「削除済み」にする書き込みだけがルールで許可される
    func deleteMessage(_ message: ChatMessageItem) async throws {
        guard message.isMine, let createdAtRaw = message.createdAtRaw else { throw ReggitAPIError.server("このメッセージは取り消せません") }
        try await commit([
            [
                "update": ["name": documentName("\(threadPath)/messages/\(message.id)"), "fields": [
                    "sender": Self.string(message.sender),
                    "sender_name": Self.string(message.senderName),
                    "text": Self.string(""),
                    "created_at": ["timestampValue": createdAtRaw],
                    "deleted": ["booleanValue": true],
                    "deleted_by": Self.string("member"),
                ]],
                "currentDocument": ["exists": true],
            ],
            ["transform": ["document": documentName(threadPath), "fieldTransforms": [Self.requestTime("edited_at")]]],
        ])
    }

    /// 自分の送信の本文を直す。送信者・時刻・画像は変えられない（firestore.rules）
    func editMessage(_ message: ChatMessageItem, text: String) async throws {
        let body = text.trimmingCharacters(in: .whitespacesAndNewlines)
        guard message.isMine, !message.deleted, message.card == nil, let createdAtRaw = message.createdAtRaw else {
            throw ReggitAPIError.server("このメッセージは編集できません")
        }
        guard !body.isEmpty else { throw ReggitAPIError.server("メッセージを入力してください") }
        guard body.count <= Self.textLimit else { throw ReggitAPIError.server("メッセージは\(Self.textLimit)文字以内にしてください") }
        guard body != message.text else { return }
        var fields: [String: Any] = [
            "sender": Self.string(message.sender),
            "sender_name": Self.string(message.senderName),
            "text": Self.string(body),
            "created_at": ["timestampValue": createdAtRaw],
        ]
        if let imagePath = message.imagePath { fields["image_path"] = Self.string(imagePath) }
        try await commit([
            [
                "update": ["name": documentName("\(threadPath)/messages/\(message.id)"), "fields": fields],
                "updateTransforms": [Self.requestTime("edited_at")],
                "currentDocument": ["exists": true],
            ],
            ["transform": ["document": documentName(threadPath), "fieldTransforms": [Self.requestTime("edited_at")]]],
        ])
    }

    /// 公開 URL は作らず、ログイン中のトークンで取得する
    func fetchImage(path: String) async throws -> Data {
        guard let bucket = session.storageBucket else { throw ReggitAPIError.server("画像は表示できません") }
        let encoded = path.addingPercentEncoding(withAllowedCharacters: .alphanumerics.union(CharacterSet(charactersIn: "-_.~"))) ?? path
        let host = session.emulatorHost.map { "http://\($0):9199" } ?? "https://firebasestorage.googleapis.com"
        guard let url = URL(string: "\(host)/v0/b/\(bucket)/o/\(encoded)?alt=media") else { throw ReggitAPIError.invalidURL }
        return try await request(method: "GET", url: url)
    }

    // MARK: - Firestore REST

    private var threadPath: String { "projects/\(session.projectId)/chats/\(session.memberId)" }

    private var databasePath: String { "projects/\(session.firebaseProjectId)/databases/(default)/documents" }

    private func documentName(_ path: String) -> String { "\(databasePath)/\(path)" }

    private func documentsURL(_ path: String? = nil, suffix: String = "") throws -> URL {
        let host = session.emulatorHost.map { "http://\($0):8086" } ?? "https://firestore.googleapis.com"
        guard let url = URL(string: "\(host)/v1/\(databasePath)\(path.map { "/\($0)" } ?? "")\(suffix)") else { throw ReggitAPIError.invalidURL }
        return url
    }

    private func commit(_ writes: [[String: Any]]) async throws {
        _ = try await request(method: "POST", url: try documentsURL(suffix: ":commit"), body: ["writes": writes])
    }

    private func request(method: String, url: URL, body: [String: Any]? = nil, retry: Bool = true) async throws -> Data {
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.timeoutInterval = 30
        request.setValue("Bearer \(try await validIDToken())", forHTTPHeaderField: "Authorization")
        if let body {
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
            request.httpBody = try JSONSerialization.data(withJSONObject: body)
        }
        let (data, response) = try await urlSession.data(for: request)
        let status = (response as? HTTPURLResponse)?.statusCode ?? 0
        // トークンの期限切れ・退会・機能停止。Laravel で本人確認をやり直して一度だけ再試行する
        if (status == 401 || status == 403) && retry {
            idToken = nil
            session = try await renewSession()
            return try await self.request(method: method, url: url, body: body, retry: false)
        }
        guard (200 ..< 300).contains(status) else {
            throw ReggitAPIError.httpStatus(status, status == 403 ? "チャットを利用できません" : "チャットに接続できませんでした")
        }
        return data
    }

    private func validIDToken() async throws -> String {
        if let idToken, idTokenExpiresAt > Date().addingTimeInterval(60) { return idToken }
        let host = session.emulatorHost.map { "http://\($0):9099/identitytoolkit.googleapis.com" } ?? "https://identitytoolkit.googleapis.com"
        guard let url = URL(string: "\(host)/v1/accounts:signInWithCustomToken?key=\(session.apiKey)") else { throw ReggitAPIError.invalidURL }
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: ["token": session.token, "returnSecureToken": true])
        let (data, response) = try await urlSession.data(for: request)
        guard (response as? HTTPURLResponse)?.statusCode == 200,
              let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let token = json["idToken"] as? String else {
            throw ReggitAPIError.server("チャットに接続できませんでした")
        }
        idToken = token
        idTokenExpiresAt = Date().addingTimeInterval(TimeInterval(Int(json["expiresIn"] as? String ?? "") ?? 3600))
        return token
    }

    // MARK: - 変換

    static func parseThread(_ document: [String: Any]) -> ChatThreadState? {
        guard let fields = document["fields"] as? [String: Any] else { return nil }
        let lastRaw = timestampRaw(fields["last_message_at"])
        return ChatThreadState(
            lastMessageRaw: lastRaw,
            lastSender: stringValue(fields["last_sender"]),
            lastMessageAt: lastRaw.flatMap(parseTimestamp),
            memberReadAt: timestampRaw(fields["member_read_at"]).flatMap(parseTimestamp),
            staffReadAt: timestampRaw(fields["staff_read_at"]).flatMap(parseTimestamp),
            blocked: (fields["blocked"] as? [String: Any])?["booleanValue"] as? Bool ?? false,
            editedRaw: timestampRaw(fields["edited_at"])
        )
    }

    static func parseMessage(_ document: [String: Any]) -> ChatMessageItem? {
        guard let name = document["name"] as? String, let fields = document["fields"] as? [String: Any],
              let id = name.split(separator: "/").last.map(String.init) else { return nil }
        let raw = timestampRaw(fields["created_at"])
        return ChatMessageItem(
            id: id,
            sender: stringValue(fields["sender"]) == "staff" ? "staff" : "member",
            senderName: stringValue(fields["sender_name"]) ?? "",
            text: stringValue(fields["text"]) ?? "",
            imagePath: stringValue(fields["image_path"]),
            card: parseCard(fields["card"]),
            deleted: (fields["deleted"] as? [String: Any])?["booleanValue"] as? Bool ?? false,
            edited: fields["edited_at"] != nil,
            createdAt: raw.flatMap(parseTimestamp),
            createdAtRaw: raw
        )
    }

    static func parseCard(_ value: Any?) -> ChatCardItem? {
        guard let fields = ((value as? [String: Any])?["mapValue"] as? [String: Any])?["fields"] as? [String: Any],
              let type = stringValue(fields["type"]), ["coupon", "reservation"].contains(type),
              let title = stringValue(fields["title"]) else { return nil }
        return ChatCardItem(type: type, title: title, subtitle: stringValue(fields["subtitle"]) ?? "")
    }

    /// Firestore の時刻（RFC3339・小数部は最大9桁）を読む
    static func parseTimestamp(_ raw: String) -> Date? {
        let trimmed = raw.hasSuffix("Z") ? String(raw.dropLast()) : raw
        let parts = trimmed.split(separator: ".", maxSplits: 1)
        guard let base = ISO8601DateFormatter().date(from: "\(parts[0])Z") else { return nil }
        guard parts.count == 2, let fraction = Double("0.\(parts[1])") else { return base }
        return base.addingTimeInterval(fraction)
    }

    private static func stringValue(_ value: Any?) -> String? {
        (value as? [String: Any])?["stringValue"] as? String
    }

    private static func timestampRaw(_ value: Any?) -> String? {
        (value as? [String: Any])?["timestampValue"] as? String
    }

    private static func string(_ value: String) -> [String: Any] { ["stringValue": value] }

    private static func requestTime(_ field: String) -> [String: Any] {
        ["fieldPath": field, "setToServerValue": "REQUEST_TIME"]
    }

    private static func documentID() -> String {
        let characters = Array("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789")
        return String((0 ..< 20).map { _ in characters.randomElement()! })
    }
}
