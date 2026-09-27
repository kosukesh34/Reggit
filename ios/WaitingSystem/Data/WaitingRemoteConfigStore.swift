import Foundation

/// API から取得した LINE / プロジェクト表示情報のキャッシュ。
enum WaitingRemoteConfigStore {
    private static let key = "waiting_system_remote_config"
    private static let encoder = JSONEncoder()
    private static let decoder = JSONDecoder()

    static var current: WaitingAppRemoteConfig {
        get {
            guard let data = UserDefaults.standard.data(forKey: key),
                  let value = try? decoder.decode(WaitingAppRemoteConfig.self, from: data) else {
                return .empty
            }
            return value
        }
        set {
            guard let data = try? encoder.encode(newValue) else { return }
            UserDefaults.standard.set(data, forKey: key)
        }
    }

    @MainActor
    static func refreshFromAPI() async {
        let client = WaitingAppConfigClient(
            baseURL: AppBuildConfig.baseURL,
            projectKey: SettingsStore().load().projectKey
        )
        if let config = await client.fetch() {
            current = config
        }
    }
}
