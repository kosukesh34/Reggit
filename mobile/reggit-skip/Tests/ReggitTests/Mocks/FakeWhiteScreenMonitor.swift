import Foundation

@MainActor
final class FakeWhiteScreenMonitor: WhiteScreenMonitoring {
    private(set) var startCount = 0
    private(set) var stopCount = 0
    var onEvent: ((WhiteScreenMonitorEvent) -> Void)?

    func resetTracking() {}

    func stop() {
        stopCount += 1
    }

    func start(
        liffURLString: String,
        isContentReady: @escaping () -> Bool,
        inspectPage: @escaping () async -> PageInspection,
        forceBootstrap: @escaping () async -> Void,
        onEvent: @escaping (WhiteScreenMonitorEvent) -> Void
    ) {
        startCount += 1
        self.onEvent = onEvent
    }

    func simulateContentVisible() {
        onEvent?(.contentVisible)
    }
}
