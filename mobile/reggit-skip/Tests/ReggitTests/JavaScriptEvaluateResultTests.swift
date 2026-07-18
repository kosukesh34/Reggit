import XCTest
@testable import Reggit

final class JavaScriptEvaluateResultTests: XCTestCase {
    func testNormalizeKeepsValidJSONObject() {
        let raw = #"{"project_name":"Test","tabs":[]}"#
        XCTAssertEqual(JavaScriptEvaluateResult.normalize(raw), raw)
    }

    func testNormalizeDecodesJSONStringLiteral() {
        let raw = #""{\"project_name\":\"Test\",\"tabs\":[]}""#
        XCTAssertEqual(
            JavaScriptEvaluateResult.normalize(raw),
            #"{"project_name":"Test","tabs":[]}"#
        )
    }

    func testNormalizeUnescapesBackslashEscapedJSONObject() {
        let raw = #"{\"project_name\":\"Test\",\"tabs\":[]}"#
        XCTAssertEqual(
            JavaScriptEvaluateResult.normalize(raw),
            #"{"project_name":"Test","tabs":[]}"#
        )
    }

    func testNormalizePreservesPageVisibilitySummary() {
        let raw = "interactive|https://example.com|1|0|0|1"
        XCTAssertEqual(JavaScriptEvaluateResult.normalize(raw), raw)
    }
}
