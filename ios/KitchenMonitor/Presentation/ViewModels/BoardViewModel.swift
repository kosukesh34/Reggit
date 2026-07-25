import Foundation
import AudioToolbox

@MainActor
final class BoardViewModel: ObservableObject {
    @Published var orders: [KitchenOrder] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var lastUpdatedAt: Date?

    private let api: KitchenAPIClient
    private var pollTask: Task<Void, Never>?
    private var knownOrderIds: Set<Int> = []

    init(api: KitchenAPIClient = KitchenAPIClient()) {
        self.api = api
    }

    func startPolling() {
        stopPolling()
        pollTask = Task { [weak self] in
            while !Task.isCancelled {
                await self?.refresh(silent: true)
                try? await Task.sleep(nanoseconds: UInt64(AppConstants.Timing.pollIntervalSeconds * 1_000_000_000))
            }
        }
    }

    func stopPolling() {
        pollTask?.cancel()
        pollTask = nil
    }

    func refresh(silent: Bool = false) async {
        if !silent {
            isLoading = true
        }
        do {
            let fetched = try await api.fetchOrders()
            let newIds = Set(fetched.map(\.id))
            if !knownOrderIds.isEmpty {
                let added = newIds.subtracting(knownOrderIds)
                if !added.isEmpty {
                    AudioServicesPlaySystemSound(1007)
                }
            }
            knownOrderIds = newIds
            orders = fetched
            lastUpdatedAt = Date()
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
        isLoading = false
    }

    func advance(_ order: KitchenOrder) async {
        guard let next = order.nextStatus else { return }
        do {
            _ = try await api.updateStatus(orderId: order.id, status: next)
            await refresh(silent: true)
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
