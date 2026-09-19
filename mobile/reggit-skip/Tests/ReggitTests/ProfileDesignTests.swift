import XCTest
@testable import Reggit

final class ProfileDesignTests: XCTestCase {
    func testDecimalPriceFromLaravelIsNotShownAsFree() throws {
        let products = try ProductListResponse.parse(Data("""
        {"products":[{"id":1,"name":"サービス","price":"1200.00"}]}
        """.utf8)).products
        XCTAssertEqual(products.first?.price, 1200)
    }

    func testThemeContrastAndInvalidInput() {
        XCTAssertTrue(BrandPalette(hex: "#FFFFFF").usesDarkForeground)
        XCTAssertTrue(BrandPalette(hex: "#FFDD00").usesDarkForeground)
        XCTAssertFalse(BrandPalette(hex: "#111111").usesDarkForeground)
        XCTAssertEqual(BrandPalette(hex: "invalid abc123"), BrandPalette(hex: "#111111"))
    }

    func testServerFieldsControlVisibilityAndOptionality() throws {
        let member = try MemberProfileSnapshot.parse(Data("""
        {"is_member":true,"user":{"name":"山田","phone":"09000000000"},
         "profile":{"email":"","notification_preference":"coupon_only","interests":["美容","小売"]},
         "profile_field_settings":[
          {"key":"email","label":"メール","required":false},
          {"key":"notification_preference","label":"通知","input_type":"radio"},
          {"key":"interests","label":"興味","input_type":"checkbox","values":["美容","小売"]}]}
        """.utf8))
        XCTAssertEqual(member.editableProfileFields.map(\.key), ["email", "notification_preference", "interests"])
        XCTAssertEqual(member.profileValue(for: member.editableProfileFields[0]), "未登録")
        XCTAssertEqual(member.profileValue(for: member.editableProfileFields[1]), "クーポン情報のみ")
        XCTAssertEqual(member.profileValue(for: member.editableProfileFields[2]), "美容、小売")
        XCTAssertNil(member.editableProfileFields[0].validationMessage(value: "", selections: []))
    }

    func testFallbackProfileUsesUserValues() throws {
        let member = try MemberProfileSnapshot.parse(Data("""
        {"is_member":true,"user":{"name":"山田","phone":"09000000000"}}
        """.utf8))
        XCTAssertEqual(member.profileValue(for: member.editableProfileFields[0]), "山田")
        XCTAssertEqual(member.profileValue(for: member.editableProfileFields[1]), "09000000000")
    }

    func testBuiltInChoicesHaveHumanReadableLabels() throws {
        let sex = try XCTUnwrap(ProfileFieldDefinition.parse(["key": "sex", "input_type": "radio"]))
        XCTAssertEqual(sex.choices, ["0", "1", "2"])
        XCTAssertEqual(sex.displayValue("2"), "女性")
        let mail = try XCTUnwrap(ProfileFieldDefinition.parse(["key": "mail_receive_flag"]))
        XCTAssertEqual(mail.displayValue("0"), "許可しない")
    }

    func testRequiredCheckboxAndEmailValidation() throws {
        let check = try XCTUnwrap(ProfileFieldDefinition.parse(["key":"interests", "label":"興味", "input_type":"checkbox", "required":true]))
        XCTAssertNotNil(check.validationMessage(value: "", selections: []))
        XCTAssertNil(check.validationMessage(value: "", selections: ["美容"]))
        let email = try XCTUnwrap(ProfileFieldDefinition.parse(["key":"email", "required":false]))
        XCTAssertNotNil(email.validationMessage(value: "invalid", selections: []))
        XCTAssertNil(email.validationMessage(value: "a@example.com", selections: []))
        XCTAssertNil(email.validationMessage(value: "", selections: []))
    }
}
