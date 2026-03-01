import SwiftUI
import SkipWeb

struct ContentView: View {
    @State var viewModel = MobileWebShellViewModel.default

    var body: some View {
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
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        // WebView のビューポートと実フレームを一致させるため、safe area 内にレイアウト（ignoresSafeArea しない）
        .onAppear {
            viewModel.onAppear()
        }
        .onDisappear {
            viewModel.onDisappear()
        }
    }
}
