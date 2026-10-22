import XCTest
@testable import Reggit

@MainActor
final class LoginDeepLinkHandlerTests: XCTestCase {
    func testIgnoresOtherHostsAndIncompleteLinks() throws {
        let urls = [
            "reggit://payment?project_key=default&member_id=1&code=ABCDEFGH",
            "https://login?project_key=default&member_id=1&code=ABCDEFGH",
            "reggit://login?member_id=1&code=ABCDEFGH",
            "reggit://login?project_key=default&member_id=0&code=ABCDEFGH",
            "reggit://login?project_key=default&member_id=1",
        ]
        for raw in urls {
            XCTAssertFalse(LoginDeepLinkHandler.handle(try XCTUnwrap(URL(string: raw))), raw)
        }
    }
}
