import SwiftUI
import SkipWeb

struct ContentView: View {
    @State var viewModel = MobileWebShellViewModel.default

    var body: some View {
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
        .onAppear {
            viewModel.onAppear()
        }
        .onDisappear {
            viewModel.onDisappear()
        }
    }
}
