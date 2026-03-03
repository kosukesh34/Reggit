import SwiftUI
import MobileOrderApp
#if canImport(OneSignalFramework)
import OneSignalFramework
#endif

private typealias AppRootView = MobileOrderAppRootView
private typealias AppDelegate = MobileOrderAppAppDelegate

/// Key for storing OneSignal subscription ID so WebView injection can send it to the backend. Must match Skip module's key.
private let kPushTokenUserDefaultsKey = "mobile_order_push_token"

/// The entry point to the app simply loads the App implementation from SPM module.
@main struct AppMain: App {
    @AppDelegateAdaptor(AppMainDelegate.self) var appDelegate
    @Environment(\.scenePhase) private var scenePhase

    var body: some Scene {
        WindowGroup {
            AppRootView()
        }
        .onChange(of: scenePhase) { oldPhase, newPhase in
            switch newPhase {
            case .active:
                AppDelegate.shared.onResume()
                #if canImport(OneSignalFramework)
                requestNotificationPermissionIfNeeded()
                #endif
            case .inactive:
                AppDelegate.shared.onPause()
            case .background:
                AppDelegate.shared.onStop()
            @unknown default:
                print("unknown app phase: \(newPhase)")
            }
        }
    }
}

#if canImport(OneSignalFramework)
func requestNotificationPermissionIfNeeded() {
    OneSignal.Notifications.requestPermission({ _ in }, fallbackToSettings: true)
}
#endif

#if canImport(UIKit)
typealias AppDelegateAdaptor = UIApplicationDelegateAdaptor
typealias AppMainDelegateBase = UIApplicationDelegate
typealias AppType = UIApplication
#elseif canImport(AppKit)
typealias AppDelegateAdaptor = NSApplicationDelegateAdaptor
typealias AppMainDelegateBase = NSApplicationDelegate
typealias AppType = NSApplication
#endif

@MainActor final class AppMainDelegate: NSObject, AppMainDelegateBase {
    let application = AppType.shared

    #if canImport(UIKit)
    func application(_ application: UIApplication, willFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey : Any]? = nil) -> Bool {
        AppDelegate.shared.onInit()
        return true
    }

    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey : Any]? = nil) -> Bool {
        #if canImport(OneSignalFramework)
        setupOneSignalIfAvailable(launchOptions: launchOptions)
        #endif
        AppDelegate.shared.onLaunch()
        return true
    }

    #if canImport(OneSignalFramework)
    private func setupOneSignalIfAvailable(launchOptions: [UIApplication.LaunchOptionsKey: Any]?) {
        guard let appId = Bundle.main.object(forInfoDictionaryKey: "OneSignalAppId") as? String,
              !appId.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty else { return }
        OneSignal.initialize(appId, withLaunchOptions: launchOptions)
        OneSignal.Notifications.requestPermission({ _ in }, fallbackToSettings: true)
        // トークン取得後に保存（Observer の代わりに遅延で取得）
        DispatchQueue.main.asyncAfter(deadline: .now() + 2.0) { [weak self] in
            self?.saveOneSignalSubscriptionIdIfNeeded()
        }
        DispatchQueue.main.asyncAfter(deadline: .now() + 5.0) { [weak self] in
            self?.saveOneSignalSubscriptionIdIfNeeded()
        }
    }

    private func saveOneSignalSubscriptionIdIfNeeded() {
        guard let id = OneSignal.User.pushSubscription.id, !id.isEmpty else { return }
        guard id != UserDefaults.standard.string(forKey: kPushTokenUserDefaultsKey) else { return }
        UserDefaults.standard.set(id, forKey: kPushTokenUserDefaultsKey)
        NotificationCenter.default.post(
            name: Notification.Name("OneSignalPushTokenDidUpdate"),
            object: nil,
            userInfo: ["token": id]
        )
    }
    #endif

    func applicationWillTerminate(_ application: UIApplication) {
        AppDelegate.shared.onDestroy()
    }

    func applicationDidReceiveMemoryWarning(_ application: UIApplication) {
        AppDelegate.shared.onLowMemory()
    }

    // support for SkipNotify.fetchNotificationToken()

    func application(_ application: UIApplication, didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
        NotificationCenter.default.post(name: NSNotification.Name("didRegisterForRemoteNotificationsWithDeviceToken"), object: application, userInfo: ["deviceToken": deviceToken])
    }

    func application(_ application: UIApplication, didFailToRegisterForRemoteNotificationsWithError error: any Error) {
        NotificationCenter.default.post(name: NSNotification.Name("didFailToRegisterForRemoteNotificationsWithError"), object: application, userInfo: ["error": error])
    }
    #elseif canImport(AppKit)
    func applicationWillFinishLaunching(_ notification: Notification) {
        AppDelegate.shared.onInit()
    }

    func applicationDidFinishLaunching(_ notification: Notification) {
        AppDelegate.shared.onLaunch()
    }

    func applicationWillTerminate(_ application: Notification) {
        AppDelegate.shared.onDestroy()
    }
    #endif

}
