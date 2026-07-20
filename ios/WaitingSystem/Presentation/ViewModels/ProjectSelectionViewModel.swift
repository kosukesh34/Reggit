import Foundation
import SwiftUI

@MainActor
final class ProjectSelectionViewModel: ObservableObject {
    @Published var projects: [PublicProject] = []
    @Published var selectedProjectKey: String = ""
    @Published var baseURL: String
    @Published var isLoading = false
    @Published var errorMessage: String?

    private let settingsStore: SettingsStoreProtocol
    private let projectsClient: ProjectsAPIClient

    init(
        settingsStore: SettingsStoreProtocol = SettingsStore(),
        projectsClient: ProjectsAPIClient = ProjectsAPIClient()
    ) {
        self.settingsStore = settingsStore
        self.projectsClient = projectsClient
        let settings = settingsStore.load()
        self.baseURL = settings.baseURL
        self.selectedProjectKey = settings.projectKey
    }

    func loadProjects() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let fetched = try await projectsClient.fetchProjects(baseURLOverride: baseURL)
            projects = fetched
            if selectedProjectKey.isEmpty || !fetched.contains(where: { $0.projectKey == selectedProjectKey }) {
                selectedProjectKey = fetched.first?.projectKey ?? ""
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func confirmSelection() -> Bool {
        let key = selectedProjectKey.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !key.isEmpty else {
            errorMessage = "プロジェクトを選択してください"
            return false
        }
        var settings = settingsStore.load()
        settings.baseURL = baseURL.trimmingCharacters(in: .whitespacesAndNewlines)
        settings.projectKey = key
        settingsStore.save(settings)
        return true
    }
}
