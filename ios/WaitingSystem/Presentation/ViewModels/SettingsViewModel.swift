import Foundation

@MainActor
final class SettingsViewModel: ObservableObject {
    @Published var settings: AppSettings
    @Published var isCallingNext = false
    @Published var callNextMessage: String?

    private let store: SettingsStoreProtocol
    private let apiClient: QueueAPIClientProtocol

    init(store: SettingsStoreProtocol = SettingsStore(), apiClient: QueueAPIClientProtocol = QueueAPIClient()) {
        self.store = store
        self.apiClient = apiClient
        self.settings = store.load()
    }

    func reload() {
        settings = store.load()
    }

    func save() {
        store.save(settings)
    }

    func callNext() async {
        isCallingNext = true
        callNextMessage = nil
        defer { isCallingNext = false }

        do {
            let ticket = try await apiClient.callNext()
            if let ticket {
                callNextMessage = String(format: Strings.Settings.callResultFormat, ticket.queueNumber)
            } else {
                callNextMessage = Strings.Settings.noWaiting
            }
        } catch {
            callNextMessage = error.localizedDescription
        }
    }
}
