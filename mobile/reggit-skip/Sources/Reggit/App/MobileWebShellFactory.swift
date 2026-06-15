import SkipWeb

enum MobileWebShellFactory {
    @MainActor
    static func makeViewModel() -> MobileWebShellViewModel {
        let configRepository: any MobileWebConfigRepository = BundleMobileWebConfigRepository()
        let config = configRepository.resolveConfig()
        let device: any NativeDeviceContextProvider = DefaultNativeDeviceContextProvider()
        let webConfiguration = BuildWebEngineConfigurationUseCase().execute(platform: device.platform)
        let navigator = WebViewNavigator()
        let scriptExecutor: any WebViewScriptExecuting = WebViewScriptExecutor(navigator: navigator)
        let appConfigLoader: any AppConfigLoading = AppConfigLoader(
            shellConfig: config,
            scriptExecutor: scriptExecutor
        )
        return MobileWebShellViewModel(
            config: config,
            webConfiguration: webConfiguration,
            navigator: navigator,
            deviceProvider: device,
            buildScriptUseCase: BuildNativeInjectionScriptUseCase(),
            scriptExecutor: scriptExecutor,
            appConfigLoader: appConfigLoader,
            whiteScreenMonitor: WhiteScreenMonitor(),
            pushNotificationObserver: PushNotificationObserver(),
            reachabilityChecker: NetworkShellURLReachabilityChecker()
        )
    }
}
