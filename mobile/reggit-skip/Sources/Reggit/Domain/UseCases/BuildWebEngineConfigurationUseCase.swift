import SkipWeb

struct BuildWebEngineConfigurationUseCase {
    @MainActor
    func execute(platform: NativePlatform) -> WebEngineConfiguration {
        var config = WebEngineConfiguration()
        config.javaScriptEnabled = true
        config.allowsPullToRefresh = false
        config.isScrollEnabled = false
        config.userScripts = [
            WebViewUserScript(
                source: MobileWebShellScripts.ngrokInterstitialBypass(),
                injectionTime: .atDocumentEnd,
                forMainFrameOnly: true
            )
        ]
        let appToken = "\(NativeAppIdentity.userAgentAppToken) (\(platform.rawValue))"
        switch platform {
        case .ios:
            config.customUserAgent = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 \(appToken)"
        case .android:
            config.customUserAgent = "Mozilla/5.0 (Linux; Android 14; \(NativeAppIdentity.userAgentToken)) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.0.0 Mobile Safari/537.36 \(appToken)"
        case .unknown:
            config.customUserAgent = "Mozilla/5.0 \(appToken)"
        }
        return config
    }
}
