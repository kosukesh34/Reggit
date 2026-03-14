import SwiftUI
import SkipWeb

struct ContentView: View {
    @State var viewModel = MobileWebShellViewModel.default
    @State var tabs: [AppTabItem] = AppTabItem.fallbackTabs
    @State var selectedTab: String = ""
    @State var configLoaded: Bool = false

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
                                viewModel.onNavigationFinished()
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
                        Text("画面を再読み込みしてください")
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

            NativeTabBar(
                tabs: tabs,
                selectedTab: $selectedTab,
                onTabSelected: { tabKey in
                    viewModel.switchToTab(tabKey)
                }
            )
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .onAppear {
            viewModel.onAppear()
            if selectedTab.isEmpty {
                selectedTab = viewModel.config.fixedTab
            }
            loadAppConfig()
        }
        .onDisappear {
            viewModel.onDisappear()
        }
    }

    func loadAppConfig() {
        guard !configLoaded else { return }
        let client = AppConfigClient(
            baseUrl: viewModel.config.baseUrl,
            projectKey: viewModel.config.projectKey
        )
        Task {
            if let config = await client.fetchConfig() {
                await MainActor.run {
                    tabs = config.tabs
                    configLoaded = true
                    if !config.tabs.contains(where: { $0.key == selectedTab }) {
                        selectedTab = config.tabs.first?.key ?? viewModel.config.fixedTab
                    }
                }
            }
        }
    }
}
