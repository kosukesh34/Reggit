import SwiftUI
import MobileOrderApp
#if canImport(OneSignalFramework)
import OneSignalFramework
#endif
#if canImport(UserNotifications)
import UserNotifications
#endif

private typealias AppRootView = MobileOrderAppRootView
private typealias AppDelegate = MobileOrderAppAppDelegate

/// Key for storing OneSignal subscription ID so WebView injection can send it to the backend. Must match Skip module's key.
private let kPushTokenUserDefaultsKey = "mobile_order_push_token"
/// Key for storing the currently configured OneSignal App ID to avoid reusing stale subscription IDs after App ID changes.
private let kOneSignalAppIdUserDefaultsKey = "mobile_order_onesignal_app_id"

/// Notification name to show the Japanese "notification denied" alert (opens Settings).
private let kShowNotificationDeniedAlertName = Notification.Name("ShowNotificationDeniedAlert")

/// The entry point to the app simply loads the App implementation from SPM module.
@main struct AppMain: App {
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
                Button("設定を開く") {
                    openAppNotificationSettings()
                }
                Button("あとで", role: .cancel) {}
            } message: {
                Text("このアプリの通知がオフになっています。設定から通知を有効にできます。")
            }
            #endif
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
                break
            }
        }
    }
}

#if canImport(UIKit)
/// Opens the app's notification settings (iOS 16+) or general app settings (older).
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
/// 通知が本当に「オフ」のときだけアラートを出す。許可直後の誤表示を防ぐため、少し待ってから状態を再確認する。
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
            DispatchQueue.main.async {
                #if canImport(UIKit)
                UIApplication.shared.registerForRemoteNotifications()
                #endif
                OneSignal.User.pushSubscription.optIn()
            }
            return
        case .notDetermined:
            break
        @unknown default:
            break
        }
        OneSignal.Notifications.requestPermission({ granted in
            print("[OneSignal] Notification permission: \(granted ? "granted" : "denied")")
            if granted {
                DispatchQueue.main.async {
                    #if canImport(UIKit)
                    UIApplication.shared.registerForRemoteNotifications()
                    #endif
                    OneSignal.User.pushSubscription.optIn()
                }
            } else {
                showNotificationDeniedAlertIfStillDenied()
            }
        }, fallbackToSettings: false)
    }
}
#elseif canImport(OneSignalFramework)
func requestNotificationPermissionIfNeeded() {
    OneSignal.Notifications.requestPermission({ _ in }, fallbackToSettings: false)
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
    #if canImport(OneSignalFramework)
    private var oneSignalPushObserver: OneSignalPushObserver?
    #endif

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
              !appId.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty else {
            print("[OneSignal] Skip: OneSignalAppId not set in Info.plist")
            return
        }
        let normalizedAppId = appId.trimmingCharacters(in: .whitespacesAndNewlines)
        resetStoredPushTokenIfAppIdChanged(currentAppId: normalizedAppId)
        print("[OneSignal] Initializing with App ID: \(normalizedAppId.prefix(8))...\(normalizedAppId.suffix(4)) (Bundle ID: \(Bundle.main.bundleIdentifier ?? "?"))")
        OneSignal.initialize(normalizedAppId, withLaunchOptions: launchOptions)
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
                self?.saveOneSignalSubscriptionIdIfNeeded(atDelay: delay)
            }
        }
    }

    private func bindOneSignalPushObserverIfNeeded() {
        guard oneSignalPushObserver == nil else { return }
        let observer = OneSignalPushObserver { [weak self] state in
            guard let self else { return }
            let currentId = state.current.id ?? "nil"
            let previousId = state.previous.id ?? "nil"
            print("[OneSignal] pushSubscription changed: previous=\(previousId), current=\(currentId), optedIn=\(state.current.optedIn ? "1" : "0")")
            self.saveOneSignalSubscriptionIdIfNeeded(atDelay: 0)
        }
        oneSignalPushObserver = observer
        OneSignal.User.pushSubscription.addObserver(observer)
    }

    private func resetStoredPushTokenIfAppIdChanged(currentAppId: String) {
        let defaults = UserDefaults.standard
        let previousAppId = defaults.string(forKey: kOneSignalAppIdUserDefaultsKey)?.trimmingCharacters(in: .whitespacesAndNewlines)
        if previousAppId != nil, previousAppId != currentAppId {
            defaults.removeObject(forKey: kPushTokenUserDefaultsKey)
            print("[OneSignal] App ID changed (\(previousAppId ?? "nil") → \(currentAppId)). Cleared cached subscription ID.")
        }
        defaults.set(currentAppId, forKey: kOneSignalAppIdUserDefaultsKey)
    }

    private func saveOneSignalSubscriptionIdIfNeeded(atDelay delay: Double = 0) {
        let id = OneSignal.User.pushSubscription.id
        if id == nil || (id ?? "").isEmpty {
            print("[OneSignal] @\(Int(delay))s: pushSubscription.id is nil or empty → OneSignal に購読が作られていません。Dashboard の APNs (p8/Bundle ID) を確認してください")
            return
        }
        let subscriptionId = id!
        let existing = UserDefaults.standard.string(forKey: kPushTokenUserDefaultsKey)
        if subscriptionId == existing {
            return
        }
        UserDefaults.standard.set(subscriptionId, forKey: kPushTokenUserDefaultsKey)
        let preview = subscriptionId.count > 12 ? "\(subscriptionId.prefix(8))...\(subscriptionId.suffix(4))" : subscriptionId
        print("[OneSignal] Saved subscription ID (\(subscriptionId.count) chars): \(preview) → will be sent to backend as push_token")
        NotificationCenter.default.post(
            name: Notification.Name("OneSignalPushTokenDidUpdate"),
            object: nil,
            userInfo: ["token": subscriptionId]
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
    // OneSignal は method swizzling でこのコールバックを横取りしてトークンを受け取る。当メソッドはその後に呼ばれ、NotificationCenter に投稿する。
    func application(_ application: UIApplication, didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
        print("[OneSignal] APNs device token received (\(deviceToken.count) bytes) → OneSignal will register; subscription ID may appear after a few seconds")
        NotificationCenter.default.post(name: NSNotification.Name("didRegisterForRemoteNotificationsWithDeviceToken"), object: application, userInfo: ["deviceToken": deviceToken])
    }

    func application(_ application: UIApplication, didFailToRegisterForRemoteNotificationsWithError error: any Error) {
        print("[OneSignal] APNs registration FAILED: \(error.localizedDescription) (simulator or missing capability?)")
        NotificationCenter.default.post(name: NSNotification.Name("didFailToRegisterForRemoteNotificationsWithError"), object: application, userInfo: ["error": error])
    }

    /// WebView から「Safariで開く」用。mobileorderapp://openExternal?url=... で呼ばれる。
    func application(_ application: UIApplication, open url: URL, options: [UIApplication.OpenURLOptionsKey: Any] = [:]) -> Bool {
        guard url.scheme == "mobileorderapp", url.host == "openExternal" else { return false }
        guard let components = URLComponents(url: url, resolvingAgainstBaseURL: false),
              let externalString = components.queryItems?.first(where: { $0.name == "url" })?.value,
              let externalURL = URL(string: externalString) else { return false }
        application.open(externalURL)
        return true
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
#endif
