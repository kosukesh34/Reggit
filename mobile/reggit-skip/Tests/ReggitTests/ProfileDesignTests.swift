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

    func testDesignatedStoreOptionsUseIDsAndDisplayStoreNames() throws {
        let member = try MemberProfileSnapshot.parse(Data("""
        {"is_member":true,"profile":{"customer_store_id":12},"profile_field_settings":[
          {"key":"customer_store_id","label":"対象店舗","input_type":"selectbox","required":true,
           "options":[{"value":"12","label":"渋谷店"},{"value":"25","label":"新宿店"}]}]}
        """.utf8))
        let field = try XCTUnwrap(member.editableProfileFields.first)
        XCTAssertEqual(field.choices, ["12", "25"])
        XCTAssertEqual(field.displayValue("25"), "新宿店")
        XCTAssertEqual(member.profileValue(for: field), "渋谷店")
        XCTAssertNil(field.validationMessage(value: "12", selections: []))
        XCTAssertNotNil(field.validationMessage(value: "99", selections: []))
        XCTAssertNotNil(field.validationMessage(value: "", selections: []))
        XCTAssertEqual(member.withWalletPoints(100).editableProfileFields.first?.options, field.options)
    }

    func testRegistrationUsesConfiguredFieldsAndAllowsOptionalPhone() throws {
        let member = try MemberProfileSnapshot.parse(Data("""
        {"is_member":false,"member_registration_mode":"force","profile_field_settings":[
          {"key":"email","label":"メール","input_type":"email","required":true},
          {"key":"phone","label":"電話番号","input_type":"tel","required":false},
          {"key":"customer_store_id","label":"対象店舗","input_type":"selectbox","required":true,
           "options":[{"value":12,"label":"渋谷店"}]},
          {"key":"interests","label":"興味","input_type":"checkbox","required":true,"values":["小売"]}]}
        """.utf8))
        XCTAssertEqual(member.editableProfileFields.map(\.key), ["email", "phone", "customer_store_id", "interests"])
        XCTAssertFalse(member.registrationMode.canSkipRegistration)
        let values = ["email": "new@example.com", "phone": "", "customer_store_id": "12"]
        for field in member.editableProfileFields {
            XCTAssertNil(field.validationMessage(value: values[field.key] ?? "", selections: field.key == "interests" ? ["小売"] : []))
        }
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
