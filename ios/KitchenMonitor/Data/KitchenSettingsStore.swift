import Foundation

final class KitchenSettingsStore {
    private let key = AppConstants.Storage.settingsKey
    private let encoder = JSONEncoder()
    private let decoder = JSONDecoder()

    func load() -> KitchenSettings {
        guard let data = UserDefaults.standard.data(forKey: key),
              let settings = try? decoder.decode(KitchenSettings.self, from: data) else {
            return .default
        }
        var updated = settings
        updated.baseURL = AppBuildConfig.baseURL
        return updated
    }

    func save(_ settings: KitchenSettings) {
        var updated = settings
        updated.baseURL = AppBuildConfig.baseURL
        guard let data = try? encoder.encode(updated) else { return }
        UserDefaults.standard.set(data, forKey: key)
    }
}
