import SwiftUI

/* SKIP @bridge */public struct ReggitRootView : View {
    @State var needsProjectSelection: Bool? = nil
    @State var reachabilityGate = ShellLaunchReachabilityGate()
    @State var showUnreachableAlert = false

    /* SKIP @bridge */public init() {
    }

    public var body: some View {
        SkipRefreshing {
            rootContent
                .alert(reachabilityGate.alertTitle, isPresented: $showUnreachableAlert) {
                    Button("再読み込み") {
                        Task { @MainActor in
                            showUnreachableAlert = false
                            await reachabilityGate.retry()
                            syncAlert()
                        }
                    }
                } message: {
                    Text(reachabilityGate.alertMessage)
                }
        }
        .task {
            let bundleKey = BundleMobileWebConfigRepository().resolveConfig().projectKey
            needsProjectSelection = MobileSelectedProjectStore.needsSelection(bundleDefaultKey: bundleKey)
        }
        .task(id: needsProjectSelection) {
            guard needsProjectSelection == false else { return }
            if reachabilityGate.phase != .reachable {
                await reachabilityGate.start()
                syncAlert()
            }
        }
        .onAppear {
            FontAwesomeFont.registerIfNeeded()
            logger.info("Skip app logs are viewable in the Xcode console for iOS; Android logs can be viewed in Studio or using adb logcat")
        }
    }

    @ViewBuilder
    private var rootContent: some View {
        let showLaunchSplash = reachabilityGate.phase != .reachable
        Group {
            if needsProjectSelection == true {
                MobileProjectSelectionView {
                    NativeAppSession.reset()
                    reachabilityGate = ShellLaunchReachabilityGate()
                    showUnreachableAlert = false
                    needsProjectSelection = false
                }
            } else if needsProjectSelection == false {
                ZStack {
                    if reachabilityGate.phase == .reachable {
                        NativeRootView(viewModel: NativeAppSession.rootViewModel())
                    }
                    if showLaunchSplash {
                        ShellSplashView(showsActivityIndicator: reachabilityGate.phase != .unreachable)
                    }
                }
            } else {
                ShellSplashView()
            }
        }
    }

    private func syncAlert() {
        if reachabilityGate.phase == .unreachable && reachabilityGate.showAlert {
            showUnreachableAlert = true
        } else {
            showUnreachableAlert = false
        }
    }
}
