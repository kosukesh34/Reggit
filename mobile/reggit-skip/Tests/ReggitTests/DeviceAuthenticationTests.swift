import XCTest
@testable import Reggit

private final class DeviceAuthenticationProtocol: URLProtocol, @unchecked Sendable {
    override class func canInit(with request: URLRequest) -> Bool { true }
    override class func canonicalRequest(for request: URLRequest) -> URLRequest { request }
    override func startLoading() {
        let registering = request.url?.path == "/api/devices/register"
        let authenticated = request.value(forHTTPHeaderField: "X-Device-Token") == "test-secret-token"
        let status = registering || authenticated ? 200 : 401
        let body = registering
            ? "{\"success\":true,\"device_token\":\"test-secret-token\"}"
            : "{\"is_member\":true,\"profile\":{\"name\":\"Native Member\"}}"
        client?.urlProtocol(self, didReceive: HTTPURLResponse(url: request.url!, statusCode: status, httpVersion: nil, headerFields: nil)!, cacheStoragePolicy: .notAllowed)
        client?.urlProtocol(self, didLoad: Data(body.utf8))
        client?.urlProtocolDidFinishLoading(self)
    }
    override func stopLoading() {}
}

final class DeviceAuthenticationTests: XCTestCase {
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
