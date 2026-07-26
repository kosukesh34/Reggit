import SwiftUI
import SkipWeb

struct ContentView: View {
    @State var viewModel: MobileWebShellViewModel
    /// Skip Android では `@Observable` の変更が View に伝わらないことがあるため、
    /// 表示用の値は `@State` に載せ、ポーリングで必ず再描画する。
    @State var mirroredIsLoading = true
    @State var mirroredShowFallback = false
    @State var mirroredFallbackTitle = ShellUserFacingMessages.connectionTitle
    @State var mirroredFallbackMessage = ""
    @State var mirroredActiveTab = ""
    @State var mirroredShowTabBar = false
    @State var mirroredTabBarID = ""
    @State var showConnectionAlert = false
    /// Bool / String の代入だけでは Compose が再レイアウトしないことがあるため、毎ポールで進める。
    @State var renderTick = 0

    init(viewModel: MobileWebShellViewModel) {
        _viewModel = State(initialValue: viewModel)
        _mirroredActiveTab = State(initialValue: viewModel.activeTab)
    }

    var body: some View {
        // renderTick を読むことで、ポールごとの再描画を Skip Android に強制する。
        let _ = renderTick
        let live = MobileWebShellSession.viewModel()
        let showTabBar = live.showNativeTabBar && !live.tabs.isEmpty && !mirroredShowFallback

        VStack(spacing: 0) {
            webContent
                .frame(maxWidth: .infinity, maxHeight: .infinity)

            if showTabBar {
                NativeTabBar(
                    tabs: live.tabs,
                    selectedTab: Binding(
                        get: { mirroredActiveTab },
                        set: { key in
                            mirroredActiveTab = key
                            MobileWebShellSession.viewModel().selectTab(key)
                        }
                    )
                )
                .id(mirroredTabBarID)
                .frame(maxWidth: .infinity)
                .frame(height: 56)
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .alert(mirroredFallbackTitle, isPresented: $showConnectionAlert) {
            Button("再読み込み") {
                viewModel.retryLoad()
                syncMirroredShellState()
            }
        } message: {
            Text(mirroredFallbackMessage)
        }
        .onAppear {
            viewModel.onAppear()
            syncMirroredShellState()
        }
        .onDisappear { viewModel.onDisappear() }
        .onChange(of: viewModel.showNativeTabBar) { _, show in
            viewModel.onNativeTabBarVisibilityChanged(show)
            syncMirroredShellState()
        }
        .task {
            while !Task.isCancelled {
                syncMirroredShellState()
                renderTick &+= 1
                try? await Task.sleep(nanoseconds: 200_000_000)
            }
        }
    }

    @ViewBuilder
    private var webContent: some View {
        ZStack {
            if let liffURL = viewModel.liffURL {
                WebView(
                    configuration: viewModel.webConfiguration,
                    navigator: viewModel.navigator,
                    url: liffURL,
                    onNavigationFinished: {
                        viewModel.onNavigationFinished(applyInitialTab: viewModel.isWebContentReady)
                        viewModel.loadAppConfigFromWebIfNeeded()
                        syncMirroredShellState()
                    },
                    onDownloadRequested: { request in
                        NativeWebNavigationBridge.handleDownloadRequest(request)
                    },
                    shouldOverrideUrlLoading: { url in
                        NativeWebNavigationBridge.handle(url: url)
                    }
                )
                .id(viewModel.webViewReloadKey)
                .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else {
                InvalidURLView(urlDescription: viewModel.urlErrorDescription)
            }

            if mirroredShowFallback || mirroredIsLoading {
                ShellSplashView()
            }
        }
    }

    private func syncMirroredShellState() {
        let live = MobileWebShellSession.viewModel()

        mirroredIsLoading = live.isLoading
        mirroredShowFallback = live.showWhiteScreenFallback
        mirroredFallbackTitle = live.fallbackTitle
        mirroredFallbackMessage = live.fallbackMessage
        mirroredActiveTab = live.activeTab

        if live.showWhiteScreenFallback {
            if !showConnectionAlert {
                showConnectionAlert = true
            }
        } else {
            showConnectionAlert = false
        }

        let tabBarID = live.tabs.map(\.key).joined(separator: ",")
        let shouldShow = live.showNativeTabBar && !live.tabs.isEmpty
        if shouldShow != mirroredShowTabBar || tabBarID != mirroredTabBarID {
            logger.info(
                "TabBar mirror show=\(live.showNativeTabBar) tabs=\(live.tabs.count) mirrored=\(shouldShow) tick=\(self.renderTick)"
            )
        }
        mirroredTabBarID = shouldShow ? tabBarID : ""
        mirroredShowTabBar = shouldShow
    }
}
