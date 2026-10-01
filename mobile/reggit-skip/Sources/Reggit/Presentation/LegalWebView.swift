import SwiftUI
@preconcurrency import SkipWeb
#if SKIP || os(iOS) || os(Android)
#if os(iOS) && !SKIP
import WebKit
#endif
#endif

struct LegalWebView: View {
    let title: String
    let urlString: String
    let baseURL: String
    #if SKIP || os(iOS) || os(Android)
    @State var state = WebViewState()
    @State var browser = WebPageBrowser()
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
                HStack {
                    Button("戻る") { browser.navigator.goBack() }
                        .disabled(!state.canGoBack)
                    Spacer()
                    Button("再読み込み") { browser.navigator.reload() }
                }
                .padding(.horizontal, 20)
                SkipWeb.WebView(configuration: browser.configuration, navigator: browser.navigator, url: url, state: $state)
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                    .overlay {
                        if let popup = browser.popups.last {
                            VStack(spacing: 0) {
                                HStack {
                                    Text("新規ウィンドウ")
                                    Spacer()
                                    Button("閉じる") { browser.close(popup.engine) }
                                }
                                .padding()
                                HStack {
                                    Button("戻る") { popup.engine.goBack() }
                                    Spacer()
                                    Button("進む") { popup.engine.goForward() }
                                    Spacer()
                                    Button("再読み込み") { popup.engine.reload() }
                                }
                                .padding(.horizontal, 20)
                                #if os(iOS) && !SKIP
                                PopupPlatformView(engine: popup.engine)
                                    .id(popup.id)
                                #else
                                SkipWeb.WebView(configuration: browser.configuration, navigator: popup.navigator)
                                    .id(popup.id)
                                #endif
                            }
                            .background(AppDesign.surface)
                        }
                    }
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

@MainActor
@Observable
final class WebPageBrowser: @preconcurrency SkipWebUIDelegate {
    let navigator = WebViewNavigator()
    var popups: [WebPageWindow] = []

    var configuration: WebEngineConfiguration {
        #if os(Android) || SKIP
        // Fuse bridges closures to Kotlin; keep the browser state owned by Swift.
        let config = WebEngineConfiguration(javaScriptCanOpenWindowsAutomatically: true)
        config.androidCreateWindowHandler = { [weak self] _, _, _ in
            MainActor.assumeIsolated {
                guard let self else { return nil }
                let child = WebEngine(configuration: self.configuration)
                self.popups.append(WebPageWindow(engine: child))
                return child
            }
        }
        config.androidCloseWindowHandler = { [weak self] _, child in
            MainActor.assumeIsolated { self?.close(child) }
        }
        return config
        #else
        return WebEngineConfiguration(javaScriptCanOpenWindowsAutomatically: true, uiDelegate: self)
        #endif
    }

    func webView(_ webView: SkipWeb.WebView, createWebViewWith request: WebWindowRequest, platformContext: PlatformCreateWindowContext) -> WebEngine? {
        #if os(iOS) && !SKIP
        let child = platformContext.makeChildWebEngine(configuration: configuration)
        // Preserve window.opener and the configuration supplied by WebKit.
        child.webView.uiDelegate = navigator.webEngine?.webView.uiDelegate
        child.webView.navigationDelegate = navigator.webEngine?.webView.navigationDelegate
        #else
        let child = WebEngine(configuration: configuration)
        #endif
        popups.append(WebPageWindow(engine: child))
        return child
    }

    func webViewDidClose(_ webView: SkipWeb.WebView, child: WebEngine) {
        close(child)
    }

    func close(_ child: WebEngine) {
        child.stopLoading()
        popups.removeAll { $0.engine === child }
    }
}

@MainActor
final class WebPageWindow: Identifiable {
    let id = UUID()
    let engine: WebEngine
    let navigator = WebViewNavigator()

    init(engine: WebEngine) {
        self.engine = engine
        navigator.webEngine = engine
    }
}

#if os(iOS) && !SKIP
private struct PopupPlatformView: UIViewRepresentable {
    let engine: WebEngine

    func makeUIView(context: Context) -> WKWebView { engine.webView }
    func updateUIView(_ uiView: WKWebView, context: Context) {}
}
#endif
