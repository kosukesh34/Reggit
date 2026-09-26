import Foundation
import Observation

@MainActor
@Observable
final class StoreSearchViewModel {
    var projects: [MobilePublicProject] = []
    var query = ""
    var isLoading = false
    var errorMessage: String?

    private let baseURL: String
    private let selectedProjectKey: String
    private let projectService: any MobileProjectsServing

    init(
        baseURL: String,
        currentProjectKey: String,
        projectService: any MobileProjectsServing = MobileProjectsAPIClient()
    ) {
        self.baseURL = baseURL
        self.selectedProjectKey = currentProjectKey
        self.projectService = projectService
    }

    var currentProjectKey: String {
        selectedProjectKey
    }

    var filteredProjects: [MobilePublicProject] {
        let keyword = query.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !keyword.isEmpty else { return projects }
        return projects.filter {
            $0.name.localizedCaseInsensitiveContains(keyword)
                || $0.projectKey.localizedCaseInsensitiveContains(keyword)
        }
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            projects = try await projectService.fetchProjects(baseURL: baseURL)
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func select(_ project: MobilePublicProject) -> Bool {
        guard project.projectKey != currentProjectKey else { return false }
        MobileSelectedProjectStore.selectedProjectKey = project.projectKey
        MobileSelectedProjectStore.hasCompletedSelection = true
        SecondaryScreenCache.reset()
        return true
    }
}
