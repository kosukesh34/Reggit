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
        let valid = request.httpMethod == "PUT"
            && request.url?.path == "/api/member-profile"
            && request.value(forHTTPHeaderField: "X-Project-Key") == "test-store"
            && request.value(forHTTPHeaderField: "X-Device-Identifier") == "test-device"
            && body["name"] as? String == "更新した名前"
            && body["phone"] as? String == ""
            && body["email"] as? String == "a@example.com"
            && body["interests"] as? [String] == []
        let status = request.url?.host == "failure.example" ? 422 : valid ? 200 : 400
        let responseBody = status == 200
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
        let member = try await client().updateMemberProfile(name: "更新した名前", phone: "", profilePayload: ["email": "a@example.com"], profileListPayload: ["interests": []])
        XCTAssertEqual(member.name, "更新した名前")
        XCTAssertEqual(member.profilePayload["email"], "a@example.com")
        XCTAssertEqual(member.profileListPayload["interests"], [])
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
