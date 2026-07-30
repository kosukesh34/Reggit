import Foundation
import SwiftUI

/* SKIP @bridge */public struct ReggitRootView : View {
    @State var needsProjectSelection: Bool? = nil
    @State var reachabilityGate = ShellLaunchReachabilityGate()
    @State var mirroredPhase: ShellLaunchReachabilityGate.Phase = .idle
    @State var mirroredAlertTitle = ShellUserFacingMessages.connectionTitle
    @State var mirroredAlertMessage = ""
    @State var showUnreachableAlert = false
    @State var renderTick = 0

    /* SKIP @bridge */public init() {
    }

    public var body: some View {
        let _ = renderTick
        Group {
            if needsProjectSelection == true {
                MobileProjectSelectionView {
                    MobileWebShellSession.reset()
                    reachabilityGate = ShellLaunchReachabilityGate()
                    mirroredPhase = .idle
                    showUnreachableAlert = false
                    needsProjectSelection = false
                }
            } else if needsProjectSelection == false {
                if mirroredPhase == .reachable {
                    ContentView(viewModel: MobileWebShellSession.viewModel())
                } else {
                    ShellSplashView(showsActivityIndicator: mirroredPhase != .unreachable)
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
            while !Task.isCancelled && reachabilityGate.phase != .reachable {
                syncMirroredGateState()
                renderTick &+= 1
                try? await Task.sleep(nanoseconds: 200_000_000)
            }
            syncMirroredGateState()
            renderTick &+= 1
        }
        .onAppear {
            logger.info("Skip app logs are viewable in the Xcode console for iOS; Android logs can be viewed in Studio or using adb logcat")
        }
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
    }
}
