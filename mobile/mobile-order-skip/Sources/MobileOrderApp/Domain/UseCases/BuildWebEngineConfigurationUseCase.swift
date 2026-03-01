import SkipWeb

struct BuildWebEngineConfigurationUseCase {
    func execute(liffURLString: String, platform: NativePlatform) -> WebEngineConfiguration {
        _ = liffURLString
        var config = WebEngineConfiguration()
        config.javaScriptEnabled = true
        config.allowsPullToRefresh = false
        let appToken = "MobileOrderApp/1.0 (\(platform.rawValue))"
        switch platform {
        case .ios:
            config.customUserAgent = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 \(appToken)"
        case .android:
            config.customUserAgent = "Mozilla/5.0 (Linux; Android 14; MobileOrderApp) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.0.0 Mobile Safari/537.36 \(appToken)"
        case .unknown:
            config.customUserAgent = "Mozilla/5.0 \(appToken)"
        }
        return config
    }
}
