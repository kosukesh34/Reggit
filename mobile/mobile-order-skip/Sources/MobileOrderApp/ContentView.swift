import SwiftUI
import SkipWeb

struct ContentView: View {
    @State var viewModel = MobileWebShellViewModel.default
    @State var tabs: [AppTabItem] = []
    @State var configLoaded: Bool = false
    @State var pendingAppConfig: AppConfig?

    var body: some View {
        VStack(spacing: 0) {
            ZStack {
                Group {
                    if let liffURL = viewModel.liffURL {
                        WebView(
                            configuration: viewModel.webConfiguration,
                            navigator: viewModel.navigator,
                            url: liffURL,
                            onNavigationFinished: {
                                viewModel.onNavigationFinished(applyInitialTab: viewModel.isWebContentReady)
                                loadAppConfigFromWebIfNeeded()
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
                        VStack(spacing: 8) {
                            Text("URL設定が不正です")
                                .font(.headline)
                            Text(viewModel.urlErrorDescription)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                                .multilineTextAlignment(.center)
                        }
                    }
                }

                if viewModel.showWhiteScreenFallback {
                    VStack(spacing: 10) {
                        Text("接続できません")
                            .font(.headline)
                        Text(viewModel.fallbackMessage)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                            .multilineTextAlignment(.center)
                        Button("再読み込み") {
                            viewModel.retryLoad()
                        }
                        .buttonStyle(.borderedProminent)
                    }
                    .padding(16)
                    .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 14))
                    .padding(20)
                }
            }
            .frame(maxWidth: .infinity, maxHeight: .infinity)

            if viewModel.showNativeTabBar {
                NativeTabBar(
                    tabs: tabs,
                    selectedTab: Binding(
                        get: { viewModel.activeTab },
                        set: { tabKey in
                            viewModel.activeTab = tabKey
                            viewModel.switchToTab(tabKey)
                        }
                    ),
                    onTabSelected: { tabKey in
                        viewModel.activeTab = tabKey
                        viewModel.switchToTab(tabKey)
                    }
                )
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .onAppear {
            viewModel.onAppear()
        }
        .onDisappear {
            viewModel.onDisappear()
        }
        .onChange(of: viewModel.showNativeTabBar) { _, show in
            guard show else { return }
            if let pending = pendingAppConfig {
                applyAppConfig(pending)
                pendingAppConfig = nil
            } else if !configLoaded {
                loadAppConfigEarlyIfAvailable()
            }
        }
    }

    /// iOS: WebView 起動前に URLSession でタブ取得（Android は WebView 読み込み後に取得）
    func loadAppConfigEarlyIfAvailable() {
        guard viewModel.showNativeTabBar, !configLoaded else { return }
        #if !os(Android)
        let client = AppConfigClient(
            baseUrl: viewModel.config.baseUrl,
            projectKey: viewModel.config.projectKey
        )
        Task {
            if let config = await client.fetchConfig() {
                await applyAppConfig(config)
            }
        }
        #endif
    }

    /// WebView 読み込み後: iOS/Android 共通で app-config を取得
    func loadAppConfigFromWebIfNeeded() {
        guard viewModel.showNativeTabBar, !configLoaded else { return }
        Task {
            if let config = await viewModel.fetchAppConfigViaWeb() {
                await applyAppConfig(config)
            }
        }
    }

    @MainActor
    func applyAppConfig(_ config: AppConfig) {
        guard viewModel.showNativeTabBar else {
            pendingAppConfig = config
            return
        }
        let isFirstLoad = !configLoaded
        tabs = config.tabs
        configLoaded = true
        if !config.tabs.contains(where: { $0.key == viewModel.activeTab }) {
            viewModel.activeTab = config.tabs.first?.key ?? viewModel.config.fixedTab
        }
        if isFirstLoad {
            viewModel.switchToTab(viewModel.activeTab)
        }
    }
}
