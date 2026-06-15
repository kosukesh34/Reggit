import Foundation

@MainActor
protocol WhiteScreenMonitoring: AnyObject {
    func resetTracking()
    func stop()
    func start(
        liffURLString: String,
        isContentReady: @escaping () -> Bool,
        inspectPage: @escaping () async -> PageInspection,
        forceBootstrap: @escaping () async -> Void,
        onEvent: @escaping (WhiteScreenMonitorEvent) -> Void
    )
}

enum WhiteScreenMonitorEvent {
    case contentVisible
    case autoReload
    case fallback(summary: String)
}
