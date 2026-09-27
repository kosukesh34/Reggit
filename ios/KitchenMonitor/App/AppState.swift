import Foundation
import SwiftUI

@MainActor
final class AppState: ObservableObject {
    @Published var isShowingPasscode = false
    @Published var isAuthenticated = StoreAppCredentialStore.token(app: "kitchen") != nil
    private var unlockUntil: Date?
    private var pendingUnlockAction: (() -> Void)?
    private let settingsStore = KitchenSettingsStore()

    var isUnlocked: Bool {
        guard let unlockUntil else { return false }
        return unlockUntil > Date()
    }

    func requestSettingsUnlock(action: @escaping () -> Void) {
        if isUnlocked {
            action()
            return
        }
        pendingUnlockAction = action
        isShowingPasscode = true
    }

    func unlock() {
        unlockUntil = Date().addingTimeInterval(AppConstants.Timing.unlockDurationSeconds)
        isShowingPasscode = false
        pendingUnlockAction?()
        pendingUnlockAction = nil
    }

    func cancelPasscode() {
        isShowingPasscode = false
        pendingUnlockAction = nil
    }

    var expectedPasscode: String {
        settingsStore.load().passcode
    }

    func didLogin() { isAuthenticated = true }

    func logout() {
        StoreAppCredentialStore.clear(app: "kitchen")
        var settings = settingsStore.load()
        settings.projectKey = ""
        settingsStore.save(settings)
        isAuthenticated = false
    }
}
