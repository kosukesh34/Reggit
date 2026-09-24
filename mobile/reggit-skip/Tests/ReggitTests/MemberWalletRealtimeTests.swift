import XCTest
@testable import Reggit

final class MemberWalletRealtimeTests: XCTestCase {
    func testConnectionAndWalletEvents() {
        XCTAssertEqual(
            MemberWalletRealtime.parse(#"{"event":"pusher:connection_established","data":"{\"socket_id\":\"123.456\"}"}"#),
            .connected("123.456")
        )
        XCTAssertEqual(
            MemberWalletRealtime.parse(#"{"event":"wallet.updated","data":"{\"pending_lottery_count\":2}"}"#),
            .walletUpdated(2)
        )
        XCTAssertNil(MemberWalletRealtime.parse(#"{"event":"project.updated","data":"{}"}"#))
    }
}
