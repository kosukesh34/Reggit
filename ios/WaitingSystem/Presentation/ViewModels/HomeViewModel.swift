import Foundation
import UIKit

@MainActor
final class HomeViewModel: ObservableObject {
    @Published var isShowingPartySizeSheet = false
    @Published var partySize = AppConstants.PartySize.default
    @Published var waitingCount = 0
    @Published var estimatedWaitMinutes = 0
    @Published var queueListEntries: [QueueListEntry] = []
    @Published var isRefreshingOverview = false
    @Published var overviewErrorMessage: String?

    private let appState: AppState
    private let apiClient: QueueAPIClientProtocol
    private let printerService: PrinterServiceProtocol
    private let settingsStore: SettingsStoreProtocol
    private var overviewRefreshTask: Task<Void, Never>?

    init(
        appState: AppState,
        apiClient: QueueAPIClientProtocol,
        printerService: PrinterServiceProtocol,
        settingsStore: SettingsStoreProtocol
    ) {
        self.appState = appState
        self.apiClient = apiClient
        self.printerService = printerService
        self.settingsStore = settingsStore
    }

    var settings: AppSettings {
        settingsStore.load()
    }

    func reloadSettings() {
        objectWillChange.send()
    }

    // MARK: - Overview Polling

    func startOverviewRefresh() {
        stopOverviewRefresh()
        overviewRefreshTask = Task { [weak self] in
            guard let self else { return }
            await self.refreshQueueOverview()
            while !Task.isCancelled {
                try? await Task.sleep(nanoseconds: UInt64(AppConstants.Timing.ticketRefreshIntervalSeconds * 1_000_000_000))
                if Task.isCancelled { return }
                await self.refreshQueueOverview()
            }
        }
    }

    func stopOverviewRefresh() {
        overviewRefreshTask?.cancel()
        overviewRefreshTask = nil
    }

    func refreshQueueOverview() async {
        isRefreshingOverview = true
        defer { isRefreshingOverview = false }
        do {
            async let overviewTask = apiClient.fetchQueueOverview()
            async let listTask = apiClient.fetchQueueList()
            let (overview, list) = try await (overviewTask, listTask)
            waitingCount = overview.waitingCount
            estimatedWaitMinutes = overview.estimatedWaitMinutes
            queueListEntries = list
            overviewErrorMessage = nil
        } catch {
            overviewErrorMessage = error.localizedDescription
            queueListEntries = []
        }
    }

    // MARK: - Ticket Creation

    func createTicket() async {
        appState.isLoading = true
        defer { appState.isLoading = false }

        let settings = settingsStore.load()
        do {
            let ticket = try await apiClient.createTicket(partySize: partySize)
            appState.currentTicket = ticket

            if settings.shouldPrintTicket, let presenter = UIApplication.shared.topViewController {
                printerService.printTicket(ticket, baseURL: settings.baseURL, projectKey: settings.projectKey, from: presenter)
            }
        } catch {
            if let urlError = error as? URLError,
               settings.baseURL.contains("localhost") || settings.baseURL.contains("127.0.0.1") {
                appState.errorMessage = String(format: Strings.Error.localhostHintFormat, urlError.localizedDescription)
            } else {
                appState.errorMessage = error.localizedDescription
            }
        }
    }

    func selectPrinter(completion: @escaping () -> Void) {
        guard let presenter = UIApplication.shared.topViewController else { return }
        printerService.selectPrinter(from: presenter) { _ in
            completion()
        }
    }
}
