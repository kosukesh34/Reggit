import XCTest
@testable import Reggit

final class FirebaseChatClientTests: XCTestCase {
    /// Laravel と同じ形のカスタムトークン（署名なし。Auth エミュレーターは署名を検証しない）
    private func customToken(uid: String, claims: [String: Any]) throws -> String {
        func encode(_ value: [String: Any]) throws -> String {
            try JSONSerialization.data(withJSONObject: value).base64EncodedString()
                .replacingOccurrences(of: "+", with: "-").replacingOccurrences(of: "/", with: "_").replacingOccurrences(of: "=", with: "")
        }
        let now = Int(Date().timeIntervalSince1970)
        return try "\(encode(["alg": "none", "typ": "JWT"])).\(encode(["uid": uid, "iat": now, "exp": now + 3600, "aud": "https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit", "claims": claims]))."
    }

    private func sessionData(memberId: Int, label: String, stores: [Int], storageBucket: String? = nil, emulatorHost: String? = nil, storeIcon: String? = nil) throws -> Data {
        let claims: [String: Any] = ["role": "member", "pid": "7", "mid": String(memberId), "label": label, "stores": stores, "until": (Date().timeIntervalSince1970 + 3600) * 1000]
        var firebase: [String: Any] = ["apiKey": "fake", "projectId": "demo-reggit", "appId": "fake"]
        firebase["storageBucket"] = storageBucket
        firebase["emulatorHost"] = emulatorHost
        return try JSONSerialization.data(withJSONObject: ["success": true, "data": [
            "token": try customToken(uid: "m-7-\(memberId)", claims: claims), "firebase": firebase, "project_id": 7, "member_id": memberId,
            "store_icon_url": storeIcon ?? NSNull(),
        ]])
    }

    func testSessionReadsSignedLabelAndStoresFromToken() throws {
        let session = try ChatSessionInfo.parse(try sessionData(memberId: 5, label: "佐藤", stores: [3, 4]))
        XCTAssertEqual(session.memberId, "5")
        XCTAssertEqual(session.projectId, "7")
        XCTAssertEqual(session.label, "佐藤")
        XCTAssertEqual(session.storeIds, [3, 4])
        XCTAssertFalse(session.imagesEnabled)
        XCTAssertNil(session.storeIconURL)
        XCTAssertEqual(try ChatSessionInfo.parse(try sessionData(memberId: 5, label: "佐藤", stores: [], storeIcon: "/storage/logo.png")).storeIconURL, "/storage/logo.png")
        XCTAssertTrue(try ChatSessionInfo.parse(try sessionData(memberId: 5, label: "佐藤", stores: [], storageBucket: "b")).imagesEnabled)
        XCTAssertThrowsError(try ChatSessionInfo.parse(Data(#"{"success":false,"message":"x"}"#.utf8)))
    }

    func testParsesFirestoreTimestampsWithNanoseconds() throws {
        let date = try XCTUnwrap(FirebaseChatClient.parseTimestamp("2026-10-28T05:46:49.137123456Z"))
        XCTAssertEqual(date.timeIntervalSince1970, 1_793_166_409.137_123, accuracy: 0.000_01)
        XCTAssertEqual(FirebaseChatClient.parseTimestamp("2026-10-28T05:46:49Z")?.timeIntervalSince1970, 1_793_166_409)
    }

    func testUnreadOnlyForNewerStaffReply() {
        let thread = FirebaseChatClient.parseThread(["fields": [
            "last_message_at": ["timestampValue": "2026-10-28T05:00:02Z"],
            "last_sender": ["stringValue": "staff"],
            "member_read_at": ["timestampValue": "2026-10-28T05:00:01Z"],
        ]])
        XCTAssertEqual(thread?.hasUnreadReply, true)
        let read = FirebaseChatClient.parseThread(["fields": [
            "last_message_at": ["timestampValue": "2026-10-28T05:00:02Z"],
            "last_sender": ["stringValue": "staff"],
            "member_read_at": ["timestampValue": "2026-10-28T05:00:03Z"],
        ]])
        XCTAssertEqual(read?.hasUnreadReply, false)
        let mine = FirebaseChatClient.parseThread(["fields": ["last_message_at": ["timestampValue": "2026-10-28T05:00:02Z"], "last_sender": ["stringValue": "member"]]])
        XCTAssertEqual(mine?.hasUnreadReply, false)
    }

    @MainActor
    func testDayLabelsAndStaffReadTime() throws {
        let now = try XCTUnwrap(FirebaseChatClient.parseTimestamp("2026-10-28T03:00:00Z"))
        XCTAssertEqual(ChatViewModel.dayLabel(now.addingTimeInterval(-60), now: now), "今日")
        XCTAssertEqual(ChatViewModel.dayLabel(now.addingTimeInterval(-86_400), now: now), "昨日")
        XCTAssertTrue(ChatViewModel.dayLabel(now.addingTimeInterval(-86_400 * 5), now: now).hasPrefix("10月"))
        XCTAssertTrue(ChatViewModel.dayLabel(now.addingTimeInterval(-86_400 * 400), now: now).hasPrefix("2025年"))
        let thread = FirebaseChatClient.parseThread(["fields": ["staff_read_at": ["timestampValue": "2026-10-28T05:00:03Z"]]])
        XCTAssertEqual(thread?.staffReadAt, FirebaseChatClient.parseTimestamp("2026-10-28T05:00:03Z"))
    }

    @MainActor
    func testLinksOnlyHTTPURLsAndParsesCards() {
        let text = ChatViewModel.linkedText("予約はこちら https://reggit.jp/r?a=1 です。javascript:alert(1)")
        let links = text.runs.compactMap(\.link)
        XCTAssertEqual(links, [URL(string: "https://reggit.jp/r?a=1")!])
        XCTAssertEqual(String(text.characters), "予約はこちら https://reggit.jp/r?a=1 です。javascript:alert(1)")

        let card = FirebaseChatClient.parseCard(["mapValue": ["fields": [
            "type": ["stringValue": "coupon"], "title": ["stringValue": "10%OFF"], "subtitle": ["stringValue": "11/30まで"],
        ]]])
        XCTAssertEqual(card?.tabKey, "coupons")
        XCTAssertEqual(card?.title, "10%OFF")
        XCTAssertNil(FirebaseChatClient.parseCard(["mapValue": ["fields": ["type": ["stringValue": "link"], "title": ["stringValue": "x"]]]]))
        let stamp = FirebaseChatClient.parseCard(["mapValue": ["fields": ["type": ["stringValue": "stamp"], "title": ["stringValue": "スタンプを2個プレゼント"]]]])
        XCTAssertEqual(stamp?.tabKey, "stamps")
        XCTAssertEqual(stamp?.actionLabel, "スタンプを見る")
    }

    /// エミュレーターの firestore.rules に対して送受信する。
    /// 実行: `npx firebase-tools@15.33.0 emulators:start --only auth,firestore --project demo-reggit` を起動し、
    /// TEST_RUNNER_REGGIT_FIREBASE_EMULATOR_HOST=127.0.0.1 を付けて xcodebuild test する
    func testSendsAndReadsOnlyOwnConversationOnEmulator() async throws {
        guard let host = ProcessInfo.processInfo.environment["REGGIT_FIREBASE_EMULATOR_HOST"] else {
            throw XCTSkip("Firebase エミュレーター未起動")
        }
        let suffix = Int.random(in: 1000 ... 999_999)
        let mine = try ChatSessionInfo.parse(try sessionData(memberId: suffix, label: "佐藤", stores: [3], emulatorHost: host))
        let other = try ChatSessionInfo.parse(try sessionData(memberId: suffix + 1, label: "他人", stores: [3], emulatorHost: host))
        let client = FirebaseChatClient(session: mine) { mine }
        try await client.openThread()
        try await client.send(text: "  予約の変更はできますか？  ")
        let messages = try await client.fetchMessages(after: nil)
        XCTAssertEqual(messages.map(\.text), ["予約の変更はできますか？"])
        XCTAssertEqual(messages.first?.senderName, "佐藤")
        XCTAssertTrue(messages.first?.isMine == true)
        let thread = try await client.fetchThread()
        XCTAssertEqual(thread?.lastMessageRaw, messages.first?.createdAtRaw)
        XCTAssertEqual(thread?.hasUnreadReply, false)
        let newer = try await client.fetchMessages(after: messages.first?.createdAtRaw)
        XCTAssertTrue(newer.isEmpty)

        // 自分の送信は編集でき、「編集済み」になる
        try await client.editMessage(try XCTUnwrap(messages.first), text: "予約の時間を変えられますか？")
        let afterEdit = try await client.fetchMessages(after: nil)
        XCTAssertEqual(afterEdit.first?.text, "予約の時間を変えられますか？")
        XCTAssertEqual(afterEdit.first?.edited, true)
        XCTAssertEqual(afterEdit.first?.createdAtRaw, messages.first?.createdAtRaw)

        // 自分の送信を取り消すと本文が消え、会話の edited_at が更新される
        try await client.deleteMessage(try XCTUnwrap(afterEdit.first))
        let afterDelete = try await client.fetchMessages(after: nil)
        XCTAssertEqual(afterDelete.first?.deleted, true)
        XCTAssertEqual(afterDelete.first?.text, "")
        let edited = try await client.fetchThread()
        XCTAssertNotNil(edited?.editedRaw)
        XCTAssertEqual(edited?.blocked, false)

        // 他の会員のトークンで同じ会話を読もうとしても拒否される
        let otherForMine = ChatSessionInfo(token: other.token, apiKey: other.apiKey, firebaseProjectId: other.firebaseProjectId, storageBucket: nil, emulatorHost: host, projectId: mine.projectId, memberId: mine.memberId, label: other.label, storeIds: other.storeIds)
        let intruder = FirebaseChatClient(session: otherForMine) { otherForMine }
        do {
            _ = try await intruder.fetchMessages(after: nil)
            XCTFail("他の会員の会話を読めてしまった")
        } catch ReggitAPIError.httpStatus(let status, _) {
            XCTAssertEqual(status, 403)
        }
    }
}
