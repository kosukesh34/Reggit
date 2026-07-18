import SwiftUI
import SkipWeb

struct ContentView: View {
    @State var viewModel: MobileWebShellViewModel
    /// Skip Android では `@Observable` の変更が View に伝わらないことがあるため、
    /// 表示に必要な状態だけ `@State` へ同期する。
    @State var mirroredIsLoading = true
    @State var mirroredShowFallback = false
    @State var mirroredShowTabBar = false
    @State var mirroredActiveTab = ""
    @State var mirroredTabBarID = ""

    init(viewModel: MobileWebShellViewModel) {
        _viewModel = State(initialValue: viewModel)
        _mirroredActiveTab = State(initialValue: viewModel.activeTab)
    }

    var body: some View {
        VStack(spacing: 0) {
            webContent
                .frame(maxWidth: .infinity, maxHeight: .infinity)

            if mirroredShowTabBar {
                // 配列の @State ミラーは Skip Android で空のままになることがあるため、
                // 再描画トリガだけ @State にし、中身は Session から都度読む。
                NativeTabBar(
                    tabs: MobileWebShellSession.viewModel().tabs,
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

            if mirroredShowFallback {
                ConnectionFallbackView(
                    title: viewModel.fallbackTitle,
                    message: viewModel.fallbackMessage
                ) {
                    viewModel.retryLoad()
                    syncMirroredShellState()
                }
            } else if mirroredIsLoading {
                ShellLoadingOverlay()
            }
        }
    }

    private func syncMirroredShellState() {
        let live = MobileWebShellSession.viewModel()

        mirroredIsLoading = live.isLoading
        mirroredShowFallback = live.showWhiteScreenFallback
        mirroredActiveTab = live.activeTab

        let tabBarID = live.tabs.map(\.key).joined(separator: ",")
        let shouldShow = live.showNativeTabBar && !live.tabs.isEmpty
        if shouldShow != mirroredShowTabBar || tabBarID != mirroredTabBarID {
            logger.info(
                "TabBar mirror show=\(live.showNativeTabBar) tabs=\(live.tabs.count) mirrored=\(shouldShow)"
            )
        }
        mirroredTabBarID = tabBarID
        mirroredShowTabBar = shouldShow
    }
}
