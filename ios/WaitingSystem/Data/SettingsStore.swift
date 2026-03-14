import Foundation

final class SettingsStore: SettingsStoreProtocol {
    private let key = AppConstants.Storage.settingsKey
    private let encoder = JSONEncoder()
    private let decoder = JSONDecoder()

    init() {
        decoder.dateDecodingStrategy = .iso8601
        encoder.dateEncodingStrategy = .iso8601
    }

    func load() -> AppSettings {
        guard let data = UserDefaults.standard.data(forKey: key),
              let settings = try? decoder.decode(AppSettings.self, from: data) else {
            return AppSettings.default
        }
        return normalize(settings)
    }

    func save(_ settings: AppSettings) {
        let normalized = normalize(settings)
        guard let data = try? encoder.encode(normalized) else { return }
        UserDefaults.standard.set(data, forKey: key)
    }

    private func normalize(_ settings: AppSettings) -> AppSettings {
        var updated = settings
        if updated.baseURL.contains("localhost") || updated.baseURL.contains("127.0.0.1") {
            updated.baseURL = AppSettings.default.baseURL
        }
        return updated
    }
}
