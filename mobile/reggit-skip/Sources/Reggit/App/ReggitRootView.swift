import Foundation
import SwiftUI

/* SKIP @bridge */public struct ReggitRootView : View {
    @State var needsProjectSelection: Bool? = nil
    @State var reachabilityGate = ShellLaunchReachabilityGate()
    @State var mirroredPhase: ShellLaunchReachabilityGate.Phase = .idle
    @State var mirroredAlertTitle = ShellUserFacingMessages.connectionTitle
    @State var mirroredAlertMessage = ""
    @State var mirroredShellLoading = true
    @State var mirroredShellFallback = false
    @State var showUnreachableAlert = false
    @State var renderTick = 0

    /* SKIP @bridge */public init() {
    }

    public var body: some View {
        let _ = renderTick
        let showLaunchSplash = shouldShowLaunchSplash
        Group {
            if needsProjectSelection == true {
                MobileProjectSelectionView {
                    MobileWebShellSession.reset()
                    reachabilityGate = ShellLaunchReachabilityGate()
                    mirroredPhase = .idle
                    mirroredShellLoading = true
                    mirroredShellFallback = false
                    showUnreachableAlert = false
                    needsProjectSelection = false
                }
            } else if needsProjectSelection == false {
                ZStack {
                    if mirroredPhase == .reachable {
                        ContentView(viewModel: MobileWebShellSession.viewModel())
                    }
                    if showLaunchSplash {
                        ShellSplashView(showsActivityIndicator: shouldShowSplashActivity)
                    }
                }
            } else {
                ShellSplashView()
            }
        }
        .alert(mirroredAlertTitle, isPresented: $showUnreachableAlert) {
            Button("再読み込み") {
                Task { @MainActor in
                    mirroredPhase = .checking
                    showUnreachableAlert = false
                    renderTick &+= 1
                    await reachabilityGate.retry()
                    syncMirroredGateState()
                }
            }
        } message: {
            Text(mirroredAlertMessage)
        }
        .task {
            let bundleKey = BundleMobileWebConfigRepository().resolveConfig().projectKey
            needsProjectSelection = MobileSelectedProjectStore.needsSelection(bundleDefaultKey: bundleKey)
        }
        .task(id: needsProjectSelection) {
            guard needsProjectSelection == false else { return }
            if reachabilityGate.phase != .reachable {
                await reachabilityGate.start()
                syncMirroredGateState()
            }
            while !Task.isCancelled {
                syncMirroredGateState()
                renderTick &+= 1
                try? await Task.sleep(nanoseconds: 200_000_000)
            }
        }
        .onAppear {
            logger.info("Skip app logs are viewable in the Xcode console for iOS; Android logs can be viewed in Studio or using adb logcat")
        }
    }

    private var shouldShowLaunchSplash: Bool {
        if mirroredPhase != .reachable {
            return true
        }
        return mirroredShellLoading || mirroredShellFallback
    }

    private var shouldShowSplashActivity: Bool {
        if mirroredPhase == .unreachable {
            return false
        }
        if mirroredPhase != .reachable {
            return true
        }
        return mirroredShellLoading
    }

    private func syncMirroredGateState() {
        mirroredPhase = reachabilityGate.phase
        mirroredAlertTitle = reachabilityGate.alertTitle
        mirroredAlertMessage = reachabilityGate.alertMessage
        if reachabilityGate.phase == .unreachable && reachabilityGate.showAlert {
            if !showUnreachableAlert {
                showUnreachableAlert = true
            }
        } else {
            showUnreachableAlert = false
        }
        if reachabilityGate.phase == .reachable {
            let live = MobileWebShellSession.viewModel()
            mirroredShellLoading = live.isLoading
            mirroredShellFallback = live.showWhiteScreenFallback
        }
    }
}
