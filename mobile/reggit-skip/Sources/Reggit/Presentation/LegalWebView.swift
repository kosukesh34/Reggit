import SwiftUI
#if SKIP || os(iOS) || os(Android)
import SkipWeb
#endif

struct LegalWebView: View {
    let title: String
    let urlString: String
    let baseURL: String
    #if SKIP || os(iOS) || os(Android)
    @State var state = WebViewState()
    #endif

    private var url: URL? {
        MediaURL.resolve(urlString, baseURL: baseURL)
    }

    var body: some View {
        VStack(spacing: 0) {
            HStack {
                Text(title)
                    .font(.headline.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                Spacer()
            }
            .padding(.horizontal, 20)
            .frame(height: 48)
            .background(AppDesign.surface)
            Divider()
            if let url {
                #if SKIP || os(iOS) || os(Android)
                WebView(url: url, state: $state)
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                #else
                VStack(spacing: 16) {
                    FontAwesomeIcon(systemName: "safari")
                    Link("ブラウザで開く", destination: url)
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                #endif
            } else {
                AppEmptyState(
                    title: "ページを開けませんでした",
                    subtitle: "URLを確認して、もう一度お試しください",
                    systemImage: "exclamationmark.triangle"
                )
            }
        }
        .background(AppDesign.canvas)
    }
}
