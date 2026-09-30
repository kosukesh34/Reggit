import XCTest
@testable import Reggit

/// Intercepts only the isolated session; no store or customer data is changed.
private final class ProfileUpdateProtocol: URLProtocol, @unchecked Sendable {
    override class func canInit(with request: URLRequest) -> Bool { true }
    override class func canonicalRequest(for request: URLRequest) -> URLRequest { request }
    override func startLoading() {
        var data = request.httpBody ?? Data()
        if let stream = request.httpBodyStream {
            stream.open()
            defer { stream.close() }
            var buffer = [UInt8](repeating: 0, count: 1024)
            while stream.hasBytesAvailable {
                let count = stream.read(&buffer, maxLength: buffer.count)
                if count <= 0 { break }
                data.append(contentsOf: buffer.prefix(count))
            }
        }
        let body = (try? JSONSerialization.jsonObject(with: data)) as? [String: Any] ?? [:]
        let isRegistration = request.httpMethod == "POST" && request.url?.path == "/api/members/register"
        let validRegistration = isRegistration
            && request.value(forHTTPHeaderField: "X-Project-Key") == "test-store"
            && body["name"] as? String == "登録する名前"
            && body["phone"] as? String == ""
            && body["email"] as? String == "new@example.com"
            && body["customer_store_id"] as? String == "12"
            && body["interests"] as? [String] == ["小売"]
        let valid = request.httpMethod == "PUT"
            && request.url?.path == "/api/member-profile"
            && request.value(forHTTPHeaderField: "X-Project-Key") == "test-store"
            && request.value(forHTTPHeaderField: "X-Device-Identifier") == "test-device"
            && body["name"] as? String == "更新した名前"
            && body["phone"] as? String == ""
            && body["email"] as? String == "a@example.com"
            && body["interests"] as? [String] == []
            && body["customer_store_id"] as? String == "12"
        let status = request.url?.host == "failure.example" ? 422 : (valid || validRegistration) ? 200 : 400
        let responseBody = validRegistration
            ? "{\"is_member\":true,\"profile\":{\"name\":\"登録する名前\",\"phone\":\"\",\"email\":\"new@example.com\",\"customer_store_id\":\"12\",\"interests\":[\"小売\"]}}"
            : status == 200
            ? "{\"is_member\":true,\"profile\":{\"name\":\"更新した名前\",\"phone\":\"\",\"email\":\"a@example.com\",\"interests\":[]}}"
            : "{\"errors\":{\"email\":[\"メールアドレスを確認してください\"]}}"
        client?.urlProtocol(self, didReceive: HTTPURLResponse(url: request.url!, statusCode: status, httpVersion: nil, headerFields: nil)!, cacheStoragePolicy: .notAllowed)
        client?.urlProtocol(self, didLoad: Data(responseBody.utf8))
        client?.urlProtocolDidFinishLoading(self)
    }
    override func stopLoading() {}
}

final class ProfileUpdateTests: XCTestCase {
    private func client(host: String = "success.example") -> ReggitAPIClient {
        let configuration = URLSessionConfiguration.ephemeral
        configuration.protocolClasses = [ProfileUpdateProtocol.self]
        return ReggitAPIClient(baseURL: "https://\(host)", projectKey: "test-store", deviceIdentifier: "test-device", session: URLSession(configuration: configuration))
    }

    func testUpdatePreservesFieldsAndClearsCheckboxesWithoutSecondGET() async throws {
        let member = try await client().updateMemberProfile(name: "更新した名前", phone: "", profilePayload: ["email": "a@example.com", "customer_store_id": "12"], profileListPayload: ["interests": []])
        XCTAssertEqual(member.name, "更新した名前")
        XCTAssertEqual(member.profilePayload["email"], "a@example.com")
        XCTAssertEqual(member.profileListPayload["interests"], [])
    }

    func testRegistrationSendsAllConfiguredFieldsAndSelectedStoreInOnePOST() async throws {
        let member = try await client().registerMember(
            name: "登録する名前", phone: "",
            profilePayload: ["email": "new@example.com", "customer_store_id": "12"],
            profileListPayload: ["interests": ["小売"]]
        )
        XCTAssertTrue(member.isMember)
        XCTAssertEqual(member.profilePayload["customer_store_id"], "12")
        XCTAssertEqual(member.profilePayload["email"], "new@example.com")
        XCTAssertEqual(member.profileListPayload["interests"], ["小売"])
    }

    @MainActor
    func testSaveFailureKeepsEditorAndDraft() async throws {
        let member = try MemberProfileSnapshot.parse(Data("""
        {"is_member":true,"profile":{"name":"元の名前","email":"old@example.com"},"profile_field_settings":[{"key":"email","required":false}]}
        """.utf8))
        let model = MembershipViewModel(apiClient: client(host: "failure.example"), initialMember: member)
        model.openProfileEdit()
        model.editPayload["email"] = "a@example.com"
        await model.saveProfile()
        XCTAssertTrue(model.showProfileEdit)
        XCTAssertEqual(model.member?.name, "元の名前")
        XCTAssertEqual(model.editPayload["email"], "a@example.com")
        XCTAssertEqual(model.profileError, "メールアドレスを確認してください")
        XCTAssertFalse(model.isSavingProfile)
    }
}
