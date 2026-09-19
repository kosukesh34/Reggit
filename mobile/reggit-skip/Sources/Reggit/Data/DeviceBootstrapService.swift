import Foundation

/// 起動時に端末を登録し、以降の API 呼び出しで使うクライアントを用意する。
@MainActor
struct DeviceBootstrapService {
    private let configRepository: any MobileWebConfigRepository
    private let deviceProvider: any NativeDeviceContextProvider

    init(
        configRepository: any MobileWebConfigRepository = BundleMobileWebConfigRepository(),
        deviceProvider: any NativeDeviceContextProvider = DefaultNativeDeviceContextProvider()
    ) {
        self.configRepository = configRepository
        self.deviceProvider = deviceProvider
    }

    func makeAPIClient() -> any ReggitAPIServing {
        let config = configRepository.resolveConfig()
        return ReggitAPIClient(
            baseURL: config.baseUrl,
            projectKey: config.projectKey,
            deviceIdentifier: deviceProvider.deviceIdentifier
        )
    }

    func registerDevice(using client: (any ReggitAPIServing)? = nil) async throws -> any ReggitAPIServing {
        let api = client ?? makeAPIClient()
        try await api.registerDevice(
            platform: deviceProvider.platform.rawValue,
            pushToken: deviceProvider.pushToken,
            osVersion: deviceProvider.osVersion,
            appVersion: deviceProvider.appVersion
        )
        return api
    }

    func healthURLString() -> String {
        let config = configRepository.resolveConfig()
        return ReggitAPIClient.apiHealthURL(baseURL: config.baseUrl, projectKey: config.projectKey)
    }
}
