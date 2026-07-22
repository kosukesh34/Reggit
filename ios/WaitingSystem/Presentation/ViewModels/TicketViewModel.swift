import Foundation

@MainActor
final class TicketViewModel: ObservableObject {
    @Published var isShowingCancelConfirm = false
    @Published var currentTicket: QueueTicket

    private let appState: AppState
    private let apiClient: QueueAPIClientProtocol
    private let settings: AppSettings
    private let liffResolver: LiffURLResolving

    private var autoReturnWorkItem: DispatchWorkItem?
    private var refreshTask: Task<Void, Never>?
    private var isActive = false

    init(
        ticket: QueueTicket,
        settings: AppSettings,
        appState: AppState,
        apiClient: QueueAPIClientProtocol,
        liffResolver: LiffURLResolving = LiffURLResolver()
    ) {
        self.currentTicket = ticket
        self.settings = settings
        self.appState = appState
        self.apiClient = apiClient
        self.liffResolver = liffResolver
    }

    var backgroundColorHex: String { settings.backgroundColorHex }
    var passcode: String { settings.passcode }

    func liffURLString() -> String {
        liffResolver.resolve(settings: settings, qrToken: currentTicket.qrToken, ticketId: currentTicket.id)
    }

    // MARK: - Cancel

    func cancelTicket() async {
        appState.isLoading = true
        defer { appState.isLoading = false }
        do {
            try await apiClient.cancelTicket(id: currentTicket.id)
            appState.currentTicket = nil
        } catch {
            appState.errorMessage = error.localizedDescription
        }
    }

    // MARK: - Auto Return

    func scheduleAutoReturn() {
        autoReturnWorkItem?.cancel()
        let workItem = DispatchWorkItem { [weak self] in
            Task { @MainActor in
                self?.appState.currentTicket = nil
            }
        }
        autoReturnWorkItem = workItem
        DispatchQueue.main.asyncAfter(deadline: .now() + AppConstants.Timing.ticketAutoReturnSeconds, execute: workItem)
    }

    func cancelAutoReturn() {
        autoReturnWorkItem?.cancel()
        autoReturnWorkItem = nil
    }

    // MARK: - Auto Refresh

    func startAutoRefresh() {
        stopAutoRefresh()
        isActive = true
        refreshTask = Task { [weak self] in
            guard let self else { return }
            while !Task.isCancelled {
                let interval = self.currentTicket.isLinked
                    ? AppConstants.Timing.ticketRefreshIntervalSeconds
                    : AppConstants.Timing.ticketLinkPollIntervalSeconds
                try? await Task.sleep(nanoseconds: UInt64(interval * 1_000_000_000))
                if Task.isCancelled { break }
                await self.refreshTicket()
            }
        }
    }

    func stopAutoRefresh() {
        isActive = false
        refreshTask?.cancel()
        refreshTask = nil
    }

    private func refreshTicket() async {
        guard isActive else { return }
        do {
            if let latest = try await apiClient.fetchTicketStatus(id: currentTicket.id) {
                let wasLinked = currentTicket.isLinked
                currentTicket = latest
                if latest.isLinked && !wasLinked {
                    scheduleAutoReturn()
                }
                if latest.status == "cancelled" || latest.status == "entered" {
                    appState.currentTicket = nil
                }
                return
            }
            appState.currentTicket = nil
        } catch {
            // transient failure — retry on next interval
        }
    }
}
