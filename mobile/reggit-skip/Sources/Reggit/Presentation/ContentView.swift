import SwiftUI
import SkipWeb

struct ContentView: View {
    @State var viewModel: MobileWebShellViewModel

    init(viewModel: MobileWebShellViewModel) {
        _viewModel = State(initialValue: viewModel)
    }

    var body: some View {
        VStack(spacing: 0) {
            webContent
                .frame(maxWidth: .infinity, maxHeight: .infinity)

            if viewModel.showNativeTabBar {
                NativeTabBar(
                    tabs: viewModel.tabs,
                    selectedTab: Binding(
                        get: { viewModel.activeTab },
                        set: { viewModel.selectTab($0) }
                    )
                )
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .onAppear { viewModel.onAppear() }
        .onDisappear { viewModel.onDisappear() }
        .onChange(of: viewModel.showNativeTabBar) { _, show in
            viewModel.onNativeTabBarVisibilityChanged(show)
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

            if viewModel.showWhiteScreenFallback {
                ConnectionFallbackView(
                    title: viewModel.fallbackTitle,
                    message: viewModel.fallbackMessage
                ) {
                    viewModel.retryLoad()
                }
            } else if viewModel.isLoading {
                ShellLoadingOverlay()
            }
        }
    }
}
