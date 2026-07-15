import Foundation

@MainActor
final class WhiteScreenMonitor: WhiteScreenMonitoring {
    private var task: Task<Void, Never>?
    private var didAutoReload = false
    private var didForceBootstrap = false

    func resetTracking() {
        didAutoReload = false
        didForceBootstrap = false
    }

    func stop() {
        task?.cancel()
        task = nil
    }

    func start(
        liffURLString: String,
        isContentReady: @escaping () -> Bool,
        inspectPage: @escaping () async -> PageInspection,
        forceBootstrap: @escaping () async -> Void,
        onEvent: @escaping (WhiteScreenMonitorEvent) -> Void
    ) {
        stop()
        resetTracking()
        task = Task { @MainActor in
            let startedAt = Date()

            while !Task.isCancelled {
                let elapsed = Date().timeIntervalSince(startedAt)
                let inspection = await inspectPage()

                if inspection.visible {
                    onEvent(.contentVisible)
                    return
                }

                if elapsed >= WebShellTiming.bootstrapDelay, !didForceBootstrap {
                    didForceBootstrap = true
                    if !isContentReady() {
                        await forceBootstrap()
                    }
                }

                if elapsed >= WebShellTiming.autoReloadDelay, !didAutoReload {
                    didAutoReload = true
                    logger.warning("WebView content still not visible after 9s, auto reloading: \(inspection.summary)")
                    onEvent(.autoReload)
                    return
                }

                if elapsed >= WebShellTiming.fallbackDelay {
                    logger.error("White screen fallback shown url=\(liffURLString) summary=\(inspection.summary)")
                    onEvent(.fallback(summary: ShellUserFacingMessages.whiteScreenMessage))
                    return
                }

                try? await Task.sleep(nanoseconds: WebShellTiming.pollIntervalNanoseconds)
            }
        }
    }
}
