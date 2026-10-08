import XCTest
@testable import Reggit

private final class DeviceAuthenticationProtocol: URLProtocol, @unchecked Sendable {
    override class func canInit(with request: URLRequest) -> Bool { true }
    override class func canonicalRequest(for request: URLRequest) -> URLRequest { request }
    override func startLoading() {
        let registering = request.url?.path == "/api/devices/register"
        let authenticated = request.value(forHTTPHeaderField: "X-Device-Token") == "test-secret-token"
        let bootstrap = request.url?.path == "/api/devices/session"
        let stores = request.url?.path == "/api/v1/stores"
        let csrf = request.value(forHTTPHeaderField: "X-CSRF-TOKEN") == "test-csrf"
        let unscoped = request.value(forHTTPHeaderField: "X-Store-Id") == nil
        let status = bootstrap || stores ? (unscoped ? 200 : 422) : registering ? (csrf && unscoped ? 200 : 419) : authenticated ? 200 : 401
        let body = bootstrap ? "{\"csrf_token\":\"test-csrf\"}"
            : stores ? "{\"stores\":[{\"id\":12,\"name\":\"本店\"}]}"
            : registering
            ? "{\"success\":true,\"device_token\":\"test-secret-token\"}"
            : "{\"is_member\":true,\"profile\":{\"name\":\"Native Member\"},\"store_id\":\"\(request.value(forHTTPHeaderField: "X-Store-Id") ?? "")\"}"
        client?.urlProtocol(self, didReceive: HTTPURLResponse(url: request.url!, statusCode: status, httpVersion: nil, headerFields: nil)!, cacheStoragePolicy: .notAllowed)
        client?.urlProtocol(self, didLoad: Data(body.utf8))
        client?.urlProtocolDidFinishLoading(self)
    }
    override func stopLoading() {}
}

final class DeviceAuthenticationTests: XCTestCase {
    func testStoredStoreIsProjectScopedAndDoesNotBlockBootstrapOrStoreDiscovery() async throws {
        let baseURL = "https://auth.example"
        let project = UUID().uuidString
        MobileSelectedProjectStore.selectStore(999, baseURL: baseURL, projectKey: project)
        defer { MobileSelectedProjectStore.selectStore(nil, baseURL: baseURL, projectKey: project) }
        XCTAssertEqual(MobileSelectedProjectStore.selectedStoreID(baseURL: baseURL + "/", projectKey: project), 999)
        XCTAssertNil(MobileSelectedProjectStore.selectedStoreID(baseURL: baseURL, projectKey: project + "-other"))
        let configuration = URLSessionConfiguration.ephemeral
        configuration.protocolClasses = [DeviceAuthenticationProtocol.self]
        let device = UUID().uuidString
        defer { UserDefaults.standard.removeObject(forKey: "device-token:\(baseURL):\(project):\(device)") }
        let client = ReggitAPIClient(baseURL: baseURL, projectKey: project, deviceIdentifier: device, session: URLSession(configuration: configuration))
        try await client.registerDevice(platform: "ios", pushToken: nil, osVersion: nil, appVersion: nil)
        let stores = try await client.fetchStores()
        XCTAssertEqual(stores, [BusinessStore(id: 12, name: "本店")])
        XCTAssertEqual(client.selectedStoreID, 999)
        let data = try await client.fetchAnnouncementImage(id: 42)
        let response = try XCTUnwrap(JSONSerialization.jsonObject(with: data) as? [String: Any])
        XCTAssertEqual(response["store_id"] as? String, "999")
    }

    func testPrivateAnnouncementImageUsesDeviceCredential() async throws {
        let device = UUID().uuidString
        let configuration = URLSessionConfiguration.ephemeral
        configuration.protocolClasses = [DeviceAuthenticationProtocol.self]
        let session = URLSession(configuration: configuration)
        let client = ReggitAPIClient(baseURL: "https://auth.example", projectKey: "store-a", deviceIdentifier: device, session: session)
        let credentialKey = "device-token:https://auth.example:store-a:\(device)"
        defer { UserDefaults.standard.removeObject(forKey: credentialKey) }

        do {
            _ = try await client.fetchAnnouncementImage(id: 42)
            XCTFail("Private images must require authentication")
        } catch ReggitAPIError.httpStatus(let status, _) {
            XCTAssertEqual(status, 401)
        }
        try await client.registerDevice(platform: "ios", pushToken: nil, osVersion: nil, appVersion: nil)
        let data = try await client.fetchAnnouncementImage(id: 42)
        XCTAssertFalse(data.isEmpty)
    }

    func testRegistrationCredentialSurvivesClientRecreationAndIsProjectScoped() async throws {
        let device = UUID().uuidString
        let configuration = URLSessionConfiguration.ephemeral
        configuration.protocolClasses = [DeviceAuthenticationProtocol.self]
        let session = URLSession(configuration: configuration)
        func makeClient(project: String) -> ReggitAPIClient {
            ReggitAPIClient(baseURL: "https://auth.example", projectKey: project, deviceIdentifier: device, session: session)
        }
        defer { UserDefaults.standard.removeObject(forKey: "device-token:https://auth.example:store-a:\(device)") }
        try await makeClient(project: "store-a").registerDevice(platform: "ios", pushToken: nil, osVersion: nil, appVersion: nil)
        let member = try await makeClient(project: "store-a").fetchMemberProfile()
        XCTAssertEqual(member.name, "Native Member")
        do {
            _ = try await makeClient(project: "store-b").fetchMemberProfile()
            XCTFail("A credential must not be sent to another project")
        } catch ReggitAPIError.httpStatus(let status, _) {
            XCTAssertEqual(status, 401)
        }
    }
}
