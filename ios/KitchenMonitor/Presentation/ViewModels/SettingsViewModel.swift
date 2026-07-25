import Foundation

@MainActor
final class SettingsViewModel: ObservableObject {
    @Published var passcode: String
    @Published var deviceName: String
    @Published var message: String?

    private let store: KitchenSettingsStore
    private let api: KitchenAPIClient

    init(store: KitchenSettingsStore = KitchenSettingsStore(), api: KitchenAPIClient = KitchenAPIClient()) {
        self.store = store
        self.api = api
        let settings = store.load()
        self.passcode = settings.passcode
        self.deviceName = settings.deviceName
    }

    var serverLabel: String { AppBuildConfig.serverTargetLabel }
    var projectKey: String { AppBuildConfig.projectKey }

    func save() {
        var settings = store.load()
        settings.passcode = passcode.trimmingCharacters(in: .whitespacesAndNewlines)
        settings.deviceName = deviceName.trimmingCharacters(in: .whitespacesAndNewlines)
        store.save(settings)
        message = "保存しました"
    }

    func registerPushIfPossible() async {
        guard let token = UserDefaults.standard.string(forKey: AppConstants.Storage.pushTokenKey),
              !token.isEmpty else {
            message = "Push トークン未取得（実機・通知許可が必要）"
            return
        }
        do {
            try await api.registerDevice(pushToken: token, deviceName: deviceName)
            message = "端末を登録しました"
        } catch {
            message = error.localizedDescription
        }
    }
}
