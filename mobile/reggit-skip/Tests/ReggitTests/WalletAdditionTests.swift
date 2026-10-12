import XCTest
@testable import Reggit

private final class WalletAdditionProtocol: URLProtocol, @unchecked Sendable {
    override class func canInit(with request: URLRequest) -> Bool { true }
    override class func canonicalRequest(for request: URLRequest) -> URLRequest { request }
    override func startLoading() {
        let body = request.url?.path == "/api/devices/session"
            ? "{\"csrf_token\":\"wallet-test\"}"
            : "{\"url\":\"https://wallet.example/wallet-add?pass_token=test-token&project_key=test-store\"}"
        client?.urlProtocol(self, didReceive: HTTPURLResponse(url: request.url!, statusCode: 200, httpVersion: nil, headerFields: ["Content-Type": "application/json"])!, cacheStoragePolicy: .notAllowed)
        client?.urlProtocol(self, didLoad: Data(body.utf8))
        client?.urlProtocolDidFinishLoading(self)
    }
    override func stopLoading() {}
}

final class WalletAdditionTests: XCTestCase {
    @MainActor
    func testPassPresentationFailureIsShownAndAllowsRetry() async {
        let configuration = URLSessionConfiguration.ephemeral
        configuration.protocolClasses = [WalletAdditionProtocol.self]
        let api = ReggitAPIClient(baseURL: "https://wallet.example", projectKey: "test-store", deviceIdentifier: "wallet-test", session: URLSession(configuration: configuration))
        let model = MembershipViewModel(apiClient: api)
        let previous = NativeWalletBridge.addAppleWalletPass
        defer { NativeWalletBridge.addAppleWalletPass = previous }
        NativeWalletBridge.addAppleWalletPass = { _ in throw ReggitAPIError.server("Walletの取得に失敗しました") }
        await model.addToAppleWallet()
        XCTAssertEqual(model.walletError, "Walletの取得に失敗しました")
        XCTAssertFalse(model.isAddingWallet)
        XCTAssertNil(model.walletMessage)
        var presentedURL: String?
        NativeWalletBridge.addAppleWalletPass = { presentedURL = $0 }
        await model.addToAppleWallet()
        XCTAssertEqual(presentedURL, "https://wallet.example/api/member-wallet/pass?token=test-token&project_key=test-store")
        XCTAssertNil(model.walletError)
        XCTAssertFalse(model.isAddingWallet)
        XCTAssertNil(model.walletMessage)
    }
}
