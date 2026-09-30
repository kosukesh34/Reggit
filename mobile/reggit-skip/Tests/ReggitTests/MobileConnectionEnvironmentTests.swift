import Foundation
import XCTest
@testable import Reggit

final class MobileConnectionEnvironmentTests: XCTestCase {
    func testBuildEnvironmentsResolveTheirOwnURLAndProject() {
        withCleanSelection {
            let production = BundleMobileWebConfigRepository(environment: .production).resolveConfig()
            XCTAssertEqual(production.baseUrl, "https://reggit.jp")
            XCTAssertEqual(production.projectKey, "sb_skt733n4")
            let development = BundleMobileWebConfigRepository(environment: .development).resolveConfig()
            XCTAssertTrue(development.baseUrl.hasPrefix("https://"))
            XCTAssertNotEqual(development.baseUrl, production.baseUrl)
            XCTAssertEqual(development.projectKey, "sb_skp835t6")
        }
    }

    func testProjectSelectionIsIsolatedBetweenDevelopmentAndProduction() {
        withCleanSelection {
            UserDefaults.standard.set("selected-dev-store", forKey: "reggit.selected_project_key.development")
            XCTAssertEqual(BundleMobileWebConfigRepository(environment: .development).resolveConfig().projectKey, "selected-dev-store")
            XCTAssertEqual(BundleMobileWebConfigRepository(environment: .production).resolveConfig().projectKey, MobileConnectionEnvironment.production.projectKey)
            UserDefaults.standard.set("selected-prod-store", forKey: "reggit.selected_project_key.production")
            XCTAssertEqual(BundleMobileWebConfigRepository(environment: .production).resolveConfig().projectKey, "selected-prod-store")
            XCTAssertEqual(BundleMobileWebConfigRepository(environment: .development).resolveConfig().projectKey, "selected-dev-store")
        }
    }

    func testInvalidStoredProjectUsesTheEnvironmentDefault() {
        withCleanSelection {
            UserDefaults.standard.set("invalid/project", forKey: "reggit.selected_project_key.production")
            XCTAssertEqual(BundleMobileWebConfigRepository(environment: .production).resolveConfig().projectKey, MobileConnectionEnvironment.production.projectKey)
        }
    }

    private func withCleanSelection(_ action: () -> Void) {
        let keys = ["reggit.selected_project_key.development", "reggit.selected_project_key.production"]
        let previous = keys.map { UserDefaults.standard.object(forKey: $0) }
        keys.forEach { UserDefaults.standard.removeObject(forKey: $0) }
        defer {
            for (index, key) in keys.enumerated() {
                if let value = previous[index] { UserDefaults.standard.set(value, forKey: key) }
                else { UserDefaults.standard.removeObject(forKey: key) }
            }
        }
        action()
    }
}
