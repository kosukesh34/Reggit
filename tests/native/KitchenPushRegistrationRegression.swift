import Foundation
import Combine

enum AppConstants {
    enum Storage { static let pushTokenKey = "codex.kitchen.review.pushToken" }
    enum Timing { static let unlockDurationSeconds: Double = 10 }
}
enum AppBuildConfig { static let serverTargetLabel = "test" }
struct FakeSettings { var deviceName = "Test"; var passcode = "test"; var projectKey = "test" }
class KitchenSettingsStore {
    func load() -> FakeSettings { FakeSettings() }
    func save(_ settings: FakeSettings) {}
}
enum StoreAppCredentialStore {
    static var authenticated = true
    static func token(app: String) -> String? { authenticated ? "test" : nil }
    static func clear(app: String) { authenticated = false }
}
@MainActor enum FakeNetwork {
    static var events: [String] = []
    static var hold = false
    static var logoutStarted = false
    static var failRegister = false
    static var failUnregister = false
    static var continuation: CheckedContinuation<Void, Never>?
}
class KitchenAPIClient {
    @MainActor func registerDevice(pushToken: String, deviceName: String) async throws {
        FakeNetwork.events.append("register:" + pushToken)
        if FakeNetwork.hold { await withCheckedContinuation { FakeNetwork.continuation = $0 } }
        if FakeNetwork.failRegister { throw URLError(.notConnectedToInternet) }
        FakeNetwork.events.append("registered:" + pushToken)
    }
    @MainActor func unregisterDevice(pushToken: String) async throws {
        FakeNetwork.events.append("unregister:" + pushToken)
        if FakeNetwork.failUnregister { throw URLError(.notConnectedToInternet) }
    }
}
@main struct Regression {
    @MainActor static func main() async throws {
        let defaults = UserDefaults.standard
        defer { defaults.removeObject(forKey: AppConstants.Storage.pushTokenKey) }
        defaults.removeObject(forKey: AppConstants.Storage.pushTokenKey)
        let manager = PushRegistrationManager.shared
        let settings = SettingsViewModel()
        try await manager.registerDevice(token: "same", deviceName: "Test")
        await settings.registerPushIfPossible()
        precondition(FakeNetwork.events == ["register:same", "registered:same", "register:same", "registered:same"])
        precondition(settings.message == "端末を登録しました")
        defaults.removeObject(forKey: AppConstants.Storage.pushTokenKey)
        FakeNetwork.failRegister = true
        do { try await manager.registerDevice(token: "failure", deviceName: "Test"); fatalError("Failure lost") }
        catch { precondition(defaults.string(forKey: AppConstants.Storage.pushTokenKey) == nil) }
        FakeNetwork.failRegister = false
        FakeNetwork.events = []
        defaults.set("same", forKey: AppConstants.Storage.pushTokenKey)
        FakeNetwork.hold = true
        let manual = Task { @MainActor in await settings.registerPushIfPossible() }
        while FakeNetwork.continuation == nil { await Task.yield() }
        let logout = Task { @MainActor in
            FakeNetwork.logoutStarted = true
            await manager.unregisterDevice()
        }
        while !FakeNetwork.logoutStarted { await Task.yield() }
        do { try await manager.registerDevice(token: "late", deviceName: "Test"); fatalError("Registered after logout began") }
        catch is CancellationError {} catch { fatalError("Wrong cancellation error") }
        FakeNetwork.continuation?.resume()
        FakeNetwork.continuation = nil
        await manual.value
        await logout.value
        precondition(FakeNetwork.events == ["register:same", "registered:same", "unregister:same"])
        precondition(defaults.string(forKey: AppConstants.Storage.pushTokenKey) == nil)
        FakeNetwork.hold = false
        manager.resumeRegistration()
        try await manager.registerDevice(token: "new", deviceName: "Test")
        precondition(defaults.string(forKey: AppConstants.Storage.pushTokenKey) == "new")
        FakeNetwork.failRegister = true
        await settings.registerPushIfPossible()
        precondition(settings.message == URLError(.notConnectedToInternet).localizedDescription)
        FakeNetwork.failRegister = false
        FakeNetwork.failUnregister = true
        let appState = AppState()
        await appState.logout()
        precondition(!appState.isAuthenticated && !StoreAppCredentialStore.authenticated)
        appState.didLogin()
        StoreAppCredentialStore.authenticated = true
        try await manager.registerDevice(token: "after-login", deviceName: "Test")
        print("PASS: same-token settings registration, failure persistence/message, save/logout ordering, logout cancellation, login resume, offline local logout")
    }
}
