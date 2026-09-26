import Foundation
import XCTest
@testable import Reggit

@MainActor
final class StoreSearchViewModelTests: XCTestCase {
    func testLoadAndFilterProjectsByNameOrKey() async {
        let projects = [
            MobilePublicProject(projectKey: "tokyo", name: "東京店"),
            MobilePublicProject(projectKey: "osaka", name: "大阪店"),
        ]
        let viewModel = StoreSearchViewModel(
            baseURL: "https://example.com",
            currentProjectKey: "tokyo",
            projectService: StubMobileProjectsService(projects: projects)
        )

        await viewModel.load()

        XCTAssertEqual(viewModel.projects, projects)
        viewModel.query = "大阪"
        XCTAssertEqual(viewModel.filteredProjects.map(\.projectKey), ["osaka"])
        viewModel.query = "tok"
        XCTAssertEqual(viewModel.filteredProjects.map(\.projectKey), ["tokyo"])
    }

    func testLoadFailureExposesErrorWithoutFakeStores() async {
        let viewModel = StoreSearchViewModel(
            baseURL: "https://example.com",
            currentProjectKey: "tokyo",
            projectService: StubMobileProjectsService(shouldFail: true)
        )

        await viewModel.load()

        XCTAssertTrue(viewModel.projects.isEmpty)
        XCTAssertNotNil(viewModel.errorMessage)
    }
}

private struct StubMobileProjectsService: MobileProjectsServing {
    let projects: [MobilePublicProject]
    let shouldFail: Bool

    init(projects: [MobilePublicProject] = [], shouldFail: Bool = false) {
        self.projects = projects
        self.shouldFail = shouldFail
    }

    func fetchProjects(baseURL _: String) async throws -> [MobilePublicProject] {
        if shouldFail { throw URLError(.badServerResponse) }
        return projects
    }
}
