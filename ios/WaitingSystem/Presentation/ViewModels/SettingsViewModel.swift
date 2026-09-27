import Foundation

@MainActor
final class SettingsViewModel: ObservableObject {
    @Published var settings: AppSettings
    @Published var projects: [PublicProject] = []
    @Published var isLoadingProjects = false
    @Published var projectsError: String?
    @Published var isCallingNext = false
    @Published var callNextMessage: String?
    @Published var actingEntryId: Int?
    @Published var actionMessage: String?

    private let store: SettingsStoreProtocol
    private let apiClient: QueueAPIClientProtocol
    private let projectsClient: ProjectsAPIClient

    init(
        store: SettingsStoreProtocol = SettingsStore(),
        apiClient: QueueAPIClientProtocol = QueueAPIClient(),
        projectsClient: ProjectsAPIClient = ProjectsAPIClient()
    ) {
        self.store = store
        self.apiClient = apiClient
        self.projectsClient = projectsClient
        self.settings = store.load()
    }

    func reload() {
        settings = store.load()
    }

    func save() {
        store.save(settings)
    }

    func loadProjects() async {
        isLoadingProjects = true
        projectsError = nil
        defer { isLoadingProjects = false }
        do {
            let fetched = try await projectsClient.fetchProjects(baseURLOverride: settings.baseURL)
            projects = fetched
            if !fetched.contains(where: { $0.projectKey == settings.projectKey }) {
                settings.projectKey = fetched.first?.projectKey ?? settings.projectKey
            }
        } catch {
            projectsError = error.localizedDescription
        }
    }

    func callNext() async {
        isCallingNext = true
        callNextMessage = nil
        defer { isCallingNext = false }

        do {
            let ticket = try await apiClient.callNext()
            if let ticket {
                callNextMessage = String(format: Strings.Settings.callResultFormat, ticket.displayNumber)
            } else {
                callNextMessage = Strings.Settings.noWaiting
            }
        } catch {
            callNextMessage = error.localizedDescription
        }
    }

    func recall(id: Int) async {
        actingEntryId = id
        actionMessage = nil
        defer { actingEntryId = nil }

        do {
            let ticket = try await apiClient.recallTicket(id: id)
            actionMessage = String(format: Strings.Settings.recallResultFormat, ticket.displayNumber)
        } catch {
            actionMessage = error.localizedDescription
        }
    }

    func call(id: Int) async {
        actingEntryId = id
        actionMessage = nil
        defer { actingEntryId = nil }
        do {
            let ticket = try await apiClient.callTicket(id: id)
            actionMessage = "呼び出し: \(ticket.displayNumber)"
        } catch {
            actionMessage = error.localizedDescription
        }
    }

    func enter(id: Int) async {
        actingEntryId = id
        actionMessage = nil
        defer { actingEntryId = nil }

        do {
            let ticket = try await apiClient.enterTicket(id: id)
            actionMessage = String(format: Strings.Settings.enterResultFormat, ticket.displayNumber)
        } catch {
            actionMessage = error.localizedDescription
        }
    }
}
