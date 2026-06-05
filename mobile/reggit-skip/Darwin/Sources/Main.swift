import SwiftUI
import Reggit
#if canImport(OneSignalFramework)
import OneSignalFramework
#endif
#if canImport(UserNotifications)
import UserNotifications
#endif

private typealias AppRootView = ReggitRootView
private typealias AppDelegate = ReggitAppDelegate

#if canImport(UIKit)
private typealias AppDelegateAdaptor = UIApplicationDelegateAdaptor
private typealias AppMainDelegateBase = UIApplicationDelegate
private typealias AppType = UIApplication
#elseif canImport(AppKit)
private typealias AppDelegateAdaptor = NSApplicationDelegateAdaptor
private typealias AppMainDelegateBase = NSApplicationDelegate
private typealias AppType = NSApplication
#endif

private let kPushTokenUserDefaultsKey = "reggit_push_token"
private let kOneSignalAppIdUserDefaultsKey = "reggit_onesignal_app_id"
private let kShowNotificationDeniedAlertName = Notification.Name("ShowNotificationDeniedAlert")

@main
struct AppMain: App {
    @AppDelegateAdaptor(AppMainDelegate.self) var appDelegate
    @Environment(\.scenePhase) private var scenePhase
    #if canImport(UIKit)
    @State private var showNotificationDeniedAlert = false
    #endif

    var body: some Scene {
        WindowGroup {
            AppRootView()
            #if canImport(UIKit)
            .onReceive(NotificationCenter.default.publisher(for: kShowNotificationDeniedAlertName)) { _ in
                showNotificationDeniedAlert = true
            }
            .alert("通知がオフです", isPresented: $showNotificationDeniedAlert) {
                Button("設定を開く") { openAppNotificationSettings() }
                Button("あとで", role: .cancel) {}
            } message: {
                Text("このアプリの通知がオフになっています。設定から通知を有効にできます。")
            }
            #endif
        }
        .onChange(of: scenePhase) { _, newPhase in
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
                break
            }
        }
    }
}

#if canImport(UIKit)
@MainActor
private func openAppNotificationSettings() {
    let urlString: String
    if #available(iOS 16.0, *) {
        urlString = UIApplication.openNotificationSettingsURLString
    } else {
        urlString = UIApplication.openSettingsURLString
    }
    if let url = URL(string: urlString) {
        UIApplication.shared.open(url)
    }
}
#endif

#if canImport(OneSignalFramework) && canImport(UserNotifications)
private func showNotificationDeniedAlertIfStillDenied() {
    DispatchQueue.main.asyncAfter(deadline: .now() + 0.6) {
        UNUserNotificationCenter.current().getNotificationSettings { settings in
            guard settings.authorizationStatus == .denied else { return }
            DispatchQueue.main.async {
                NotificationCenter.default.post(name: kShowNotificationDeniedAlertName, object: nil)
            }
        }
    }
}

func requestNotificationPermissionIfNeeded() {
    UNUserNotificationCenter.current().getNotificationSettings { settings in
        switch settings.authorizationStatus {
        case .denied:
            showNotificationDeniedAlertIfStillDenied()
            return
        case .authorized, .provisional, .ephemeral:
            registerRemoteAndOptIn()
            return
        case .notDetermined:
            break
        @unknown default:
            break
        }
        OneSignal.Notifications.requestPermission({ granted in
            print("[OneSignal] Notification permission: \(granted ? "granted" : "denied")")
            if granted {
                DispatchQueue.main.async { registerRemoteAndOptIn() }
            } else {
                showNotificationDeniedAlertIfStillDenied()
            }
        }, fallbackToSettings: false)
    }
}

private func registerRemoteAndOptIn() {
    DispatchQueue.main.async {
        #if canImport(UIKit)
        UIApplication.shared.registerForRemoteNotifications()
        #endif
        OneSignal.User.pushSubscription.optIn()
    }
}
#elseif canImport(OneSignalFramework)
func requestNotificationPermissionIfNeeded() {
    OneSignal.Notifications.requestPermission({ _ in }, fallbackToSettings: false)
}
#endif

@MainActor
final class AppMainDelegate: NSObject, AppMainDelegateBase {
    let application = AppType.shared
    #if canImport(OneSignalFramework)
    private var oneSignalPushObserver: OneSignalPushObserver?
    private var oneSignalForegroundObserver: OneSignalForegroundObserver?
    private var oneSignalClickObserver: OneSignalNotificationClickObserver?
    #endif

    #if canImport(UIKit)
    func application(_ application: UIApplication, willFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil) -> Bool {
        NativeAppURLHandler.install()
        AppDelegate.shared.onInit()
        return true
    }

    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil) -> Bool {
        #if canImport(OneSignalFramework)
        setupOneSignalIfNeeded(launchOptions: launchOptions)
        #endif
        AppDelegate.shared.onLaunch()
        return true
    }

    #if canImport(OneSignalFramework)
    private func setupOneSignalIfNeeded(launchOptions: [UIApplication.LaunchOptionsKey: Any]?) {
        guard let appId = (Bundle.main.object(forInfoDictionaryKey: "OneSignalAppId") as? String)?
            .trimmingCharacters(in: .whitespacesAndNewlines),
              !appId.isEmpty else {
            print("[OneSignal] Skip: OneSignalAppId not set in Info.plist")
            return
        }
        resetStoredPushTokenIfAppIdChanged(currentAppId: appId)
        print("[OneSignal] Initializing with App ID: \(appId.prefix(8))...\(appId.suffix(4)) (Bundle ID: \(Bundle.main.bundleIdentifier ?? "?"))")
        OneSignal.initialize(appId, withLaunchOptions: launchOptions)
        bindOneSignalNotificationClickListenerIfNeeded()
        bindOneSignalForegroundListenerIfNeeded()
        bindOneSignalPushObserverIfNeeded()
        OneSignal.User.pushSubscription.optIn()
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.3) {
            #if canImport(UIKit)
            UIApplication.shared.registerForRemoteNotifications()
            #endif
        }
        requestNotificationPermissionIfNeeded()
        for delay in [2.0, 5.0, 10.0, 15.0, 20.0] as [Double] {
            DispatchQueue.main.asyncAfter(deadline: .now() + delay) { [weak self] in
                self?.saveOneSignalSubscriptionIdIfNeeded(loggingDelay: delay)
            }
        }
    }

    private func bindOneSignalNotificationClickListenerIfNeeded() {
        guard oneSignalClickObserver == nil else { return }
        let observer = OneSignalNotificationClickObserver()
        oneSignalClickObserver = observer
        OneSignal.Notifications.addClickListener(observer)
    }

    private func bindOneSignalForegroundListenerIfNeeded() {
        guard oneSignalForegroundObserver == nil else { return }
        let observer = OneSignalForegroundObserver()
        oneSignalForegroundObserver = observer
        OneSignal.Notifications.addForegroundLifecycleListener(observer)
    }

    private func bindOneSignalPushObserverIfNeeded() {
        guard oneSignalPushObserver == nil else { return }
        let observer = OneSignalPushObserver { [weak self] state in
            guard let self else { return }
            let currentId = state.current.id ?? "nil"
            let previousId = state.previous.id ?? "nil"
            print("[OneSignal] pushSubscription changed: previous=\(previousId), current=\(currentId), optedIn=\(state.current.optedIn ? "1" : "0")")
            saveOneSignalSubscriptionIdIfNeeded(loggingDelay: 0)
        }
        oneSignalPushObserver = observer
        OneSignal.User.pushSubscription.addObserver(observer)
    }

    private func resetStoredPushTokenIfAppIdChanged(currentAppId: String) {
        let defaults = UserDefaults.standard
        let previousAppId = defaults.string(forKey: kOneSignalAppIdUserDefaultsKey)?.trimmingCharacters(in: .whitespacesAndNewlines)
        if let prev = previousAppId, prev != currentAppId {
            defaults.removeObject(forKey: kPushTokenUserDefaultsKey)
            print("[OneSignal] App ID changed (\(prev) → \(currentAppId)). Cleared cached subscription ID.")
        }
        defaults.set(currentAppId, forKey: kOneSignalAppIdUserDefaultsKey)
    }

    private func saveOneSignalSubscriptionIdIfNeeded(loggingDelay: Double = 0) {
        guard let id = OneSignal.User.pushSubscription.id, !id.isEmpty else {
            print("[OneSignal] @\(Int(loggingDelay))s: pushSubscription.id is nil or empty → OneSignal に購読が作られていません。Dashboard の APNs (p8/Bundle ID) を確認してください")
            return
        }
        let existing = UserDefaults.standard.string(forKey: kPushTokenUserDefaultsKey)
        if id == existing { return }
        UserDefaults.standard.set(id, forKey: kPushTokenUserDefaultsKey)
        let preview = id.count > 12 ? "\(id.prefix(8))...\(id.suffix(4))" : id
        print("[OneSignal] Saved subscription ID (\(id.count) chars): \(preview) → will be sent to backend as push_token")
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

    func application(_ application: UIApplication, didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
        print("[OneSignal] APNs device token received (\(deviceToken.count) bytes) → OneSignal will register; subscription ID may appear after a few seconds")
        NotificationCenter.default.post(
            name: NSNotification.Name("didRegisterForRemoteNotificationsWithDeviceToken"),
            object: application,
            userInfo: ["deviceToken": deviceToken]
        )
    }

    func application(_ application: UIApplication, didFailToRegisterForRemoteNotificationsWithError error: any Error) {
        print("[OneSignal] APNs registration FAILED: \(error.localizedDescription) (simulator or missing capability?)")
        NotificationCenter.default.post(
            name: NSNotification.Name("didFailToRegisterForRemoteNotificationsWithError"),
            object: application,
            userInfo: ["error": error]
        )
    }

    func application(_ application: UIApplication, open url: URL, options: [UIApplication.OpenURLOptionsKey: Any] = [:]) -> Bool {
        NativeAppURLHandler.handle(url)
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

#if canImport(OneSignalFramework)
private final class OneSignalPushObserver: NSObject, OSPushSubscriptionObserver {
    private let onChange: (OSPushSubscriptionChangedState) -> Void

    init(onChange: @escaping (OSPushSubscriptionChangedState) -> Void) {
        self.onChange = onChange
        super.init()
    }

    func onPushSubscriptionDidChange(state: OSPushSubscriptionChangedState) {
        onChange(state)
    }
}

private final class OneSignalForegroundObserver: NSObject, OSNotificationLifecycleListener {
    func onWillDisplay(event: OSNotificationWillDisplayEvent) {
        let additionalData = event.notification.additionalData ?? [:]
        let type = (additionalData["type"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        if type == "coupon_issued" {
            AppDelegate.shared.onNotificationForeground(type)
        }
    }
}

private final class OneSignalNotificationClickObserver: NSObject, OSNotificationClickListener {
    func onClick(event: OSNotificationClickEvent) {
        let additionalData = event.notification.additionalData ?? [:]
        let tab = additionalData["tab"] as? String
        let type = additionalData["type"] as? String ?? ""
        guard let tab, !tab.isEmpty else { return }
        AppDelegate.shared.onNotificationOpened(tab, notificationType: type)
    }
}
#endif
