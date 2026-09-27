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
        // 接続先は固定し、プロジェクトはログイン結果を使う。LINE URL は API 取得値を使う。
        updated.baseURL = AppBuildConfig.baseURL
        let remoteLiff = WaitingRemoteConfigStore.current.lineLiffUrl
        if !remoteLiff.isEmpty {
            updated.liffURL = remoteLiff
        }
        // 空や無効な背景はローカルと同じ既定色へ
        let bg = updated.backgroundColorHex.trimmingCharacters(in: .whitespacesAndNewlines)
        if bg.isEmpty {
            updated.backgroundColorHex = AppConstants.DefaultSettings.backgroundColorHex
        }
        return updated
    }
}
