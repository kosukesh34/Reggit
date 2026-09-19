import SwiftUI
import Reggit
#if canImport(FirebaseCore)
import FirebaseCore
#endif
#if canImport(FirebaseMessaging)
import FirebaseMessaging
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

private let kPushTokenUserDefaultsKey = NativeAppStorage.pushTokenKey

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
            .onReceive(NotificationCenter.default.publisher(for: .showNotificationDeniedAlert)) { _ in
                showNotificationDeniedAlert = true
            }
            .alert("通知がオフです", isPresented: $showNotificationDeniedAlert) {
                Button("設定を開く") { openAppNotificationSettings() }
                Button("あとで", role: .cancel) {}
            } message: {
                Text("このアプリの通知がオフになっています。設定から通知を有効にできます。")
            }
            #endif
            .onAppear {
                #if canImport(UIKit)
                NativeWalletBridge.addAppleWalletPass = { url in
                    AppleWalletPassAddHelper.shared.addPass(from: url)
                }
                #endif
                #if canImport(UserNotifications)
                AnnouncementPushCancel.removeOrphanCancelledNotifications()
                AnnouncementPushCancel.syncCancelledAnnouncementNotifications()
                #endif
            }
            .onOpenURL { url in
                _ = QueueDeepLinkHandler.handle(url)
                _ = PaymentDeepLinkHandler.handle(url)
                #if canImport(UIKit)
                if url.scheme == NativeAppIdentity.urlScheme && url.host != "queue" && url.host != "payment" {
                    _ = NativeAppURLHandler.handle(url)
                }
                #endif
            }
        }
        .onChange(of: scenePhase) { _, newPhase in
            switch newPhase {
            case .active:
                AppDelegate.shared.onResume()
                #if canImport(UIKit)
                requestNotificationPermissionIfNeeded()
                #endif
                #if canImport(UserNotifications)
                AnnouncementPushCancel.removeOrphanCancelledNotifications()
                AnnouncementPushCancel.syncCancelledAnnouncementNotifications()
                DispatchQueue.main.asyncAfter(deadline: .now() + 1.5) {
                    AnnouncementPushCancel.syncCancelledAnnouncementNotifications()
                }
                DispatchQueue.main.asyncAfter(deadline: .now() + 4.0) {
                    AnnouncementPushCancel.syncCancelledAnnouncementNotifications()
                }
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

private func showNotificationDeniedAlertIfStillDenied() {
    DispatchQueue.main.asyncAfter(deadline: .now() + 0.6) {
        UNUserNotificationCenter.current().getNotificationSettings { settings in
            guard settings.authorizationStatus == .denied else { return }
            DispatchQueue.main.async {
                NotificationCenter.default.post(name: .showNotificationDeniedAlert, object: nil)
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
                UIApplication.shared.registerForRemoteNotifications()
            }
            return
        case .notDetermined:
            break
        @unknown default:
            break
        }
        UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .badge, .sound]) { granted, _ in
            print("[FCM] Notification permission: \(granted ? "granted" : "denied")")
            if granted {
                DispatchQueue.main.async {
                    UIApplication.shared.registerForRemoteNotifications()
                }
            } else {
                showNotificationDeniedAlertIfStillDenied()
            }
        }
    }
}
#endif

@MainActor
final class AppMainDelegate: NSObject, AppMainDelegateBase {
    let application = AppType.shared

    #if canImport(UIKit)
    func application(_ application: UIApplication, willFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil) -> Bool {
        NativeAppURLHandler.install()
        AppDelegate.shared.onInit()
        return true
    }

    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil) -> Bool {
        setupFirebaseMessagingIfNeeded()
        AppDelegate.shared.onLaunch()
        #if canImport(UserNotifications)
        AnnouncementPushCancel.syncCancelledAnnouncementNotifications()
        DispatchQueue.main.asyncAfter(deadline: .now() + 1.5) {
            AnnouncementPushCancel.syncCancelledAnnouncementNotifications()
        }
        DispatchQueue.main.asyncAfter(deadline: .now() + 4.0) {
            AnnouncementPushCancel.syncCancelledAnnouncementNotifications()
        }
        #endif
        return true
    }

    private func setupFirebaseMessagingIfNeeded() {
        #if canImport(FirebaseCore) && canImport(FirebaseMessaging)
        guard Bundle.main.path(forResource: "GoogleService-Info", ofType: "plist") != nil else {
            print("[FCM] GoogleService-Info.plist missing from bundle")
            return
        }
        if FirebaseApp.app() == nil {
            FirebaseApp.configure()
        }
        Messaging.messaging().delegate = self
        UNUserNotificationCenter.current().delegate = self
        requestNotificationPermissionIfNeeded()
        for delay in [2.0, 5.0, 10.0] as [Double] {
            DispatchQueue.main.asyncAfter(deadline: .now() + delay) {
                Messaging.messaging().token { token, error in
                    if let error {
                        print("[FCM] token error: \(error.localizedDescription)")
                        return
                    }
                    guard let token, !token.isEmpty else { return }
                    Self.saveFcmToken(token)
                }
            }
        }
        #else
        print("[FCM] FirebaseMessaging not linked")
        #endif
    }

    private static func saveFcmToken(_ token: String) {
        let existing = UserDefaults.standard.string(forKey: kPushTokenUserDefaultsKey)
        if token == existing { return }
        UserDefaults.standard.set(token, forKey: kPushTokenUserDefaultsKey)
        let preview = token.count > 12 ? "\(token.prefix(8))...\(token.suffix(4))" : token
        print("[FCM] Saved registration token (\(token.count) chars): \(preview)")
        NotificationCenter.default.post(
            name: .pushTokenDidUpdate,
            object: nil,
            userInfo: ["token": token]
        )
    }

    func applicationWillTerminate(_ application: UIApplication) {
        AppDelegate.shared.onDestroy()
    }

    func applicationDidReceiveMemoryWarning(_ application: UIApplication) {
        AppDelegate.shared.onLowMemory()
    }

    func application(_ application: UIApplication, didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data) {
        #if canImport(FirebaseMessaging)
        Messaging.messaging().apnsToken = deviceToken
        #endif
        print("[FCM] APNs device token received (\(deviceToken.count) bytes)")
        NotificationCenter.default.post(
            name: NSNotification.Name("didRegisterForRemoteNotificationsWithDeviceToken"),
            object: application,
            userInfo: ["deviceToken": deviceToken]
        )
    }

    func application(_ application: UIApplication, didFailToRegisterForRemoteNotificationsWithError error: any Error) {
        print("[FCM] APNs registration FAILED: \(error.localizedDescription)")
        NotificationCenter.default.post(
            name: NSNotification.Name("didFailToRegisterForRemoteNotificationsWithError"),
            object: application,
            userInfo: ["error": error]
        )
    }

    func application(
        _ application: UIApplication,
        didReceiveRemoteNotification userInfo: [AnyHashable: Any],
        fetchCompletionHandler completionHandler: @escaping (UIBackgroundFetchResult) -> Void
    ) {
        #if canImport(UserNotifications)
        if AnnouncementPushCancel.handleRemoteNotificationUserInfo(userInfo) {
            completionHandler(.newData)
            return
        }
        #endif
        completionHandler(.noData)
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

#if canImport(FirebaseMessaging) && canImport(UIKit)
extension AppMainDelegate: MessagingDelegate {
    nonisolated func messaging(_ messaging: Messaging, didReceiveRegistrationToken fcmToken: String?) {
        guard let fcmToken, !fcmToken.isEmpty else { return }
        Task { @MainActor in
            Self.saveFcmToken(fcmToken)
        }
    }
}
#endif

#if canImport(UIKit) && canImport(UserNotifications)
extension AppMainDelegate: UNUserNotificationCenterDelegate {
    nonisolated func userNotificationCenter(
        _ center: UNUserNotificationCenter,
        willPresent notification: UNNotification,
        withCompletionHandler completionHandler: @escaping (UNNotificationPresentationOptions) -> Void
    ) {
        let userInfo = notification.request.content.userInfo
        let type = AnnouncementPushCancel.additionalDataStringPublic(from: userInfo, key: "type") ?? ""
        let title = notification.request.content.title
        let body = notification.request.content.body
        let isBlank = AnnouncementPushCancel.isBlankText(title) && AnnouncementPushCancel.isBlankText(body)
        let isCancelMessage = body == "このお知らせは取り消されました"
        if type == AnnouncementPushCancel.cancelledType || isBlank || isCancelMessage {
            AnnouncementPushCancel.removeDeliveredAnnouncementNotifications(
                announcementId: AnnouncementPushCancel.additionalDataStringPublic(from: userInfo, key: "announcement_id") ?? "",
                originalTitle: AnnouncementPushCancel.additionalDataStringPublic(from: userInfo, key: "original_title") ?? ""
            )
            AnnouncementPushCancel.removeOrphanCancelledNotifications()
            completionHandler([])
            return
        }
        if type == "coupon_issued" {
            Task { @MainActor in
                AppDelegate.shared.onNotificationForeground(type)
            }
        }
        completionHandler([.banner, .sound, .badge])
    }

    nonisolated func userNotificationCenter(
        _ center: UNUserNotificationCenter,
        didReceive response: UNNotificationResponse,
        withCompletionHandler completionHandler: @escaping () -> Void
    ) {
        let userInfo = response.notification.request.content.userInfo
        let tab = AnnouncementPushCancel.additionalDataStringPublic(from: userInfo, key: "tab")
        let type = AnnouncementPushCancel.additionalDataStringPublic(from: userInfo, key: "type") ?? ""
        if let tab, !tab.isEmpty {
            Task { @MainActor in
                AppDelegate.shared.onNotificationOpened(tab, notificationType: type)
            }
        }
        completionHandler()
    }
}
#endif


#if canImport(UserNotifications)
private enum AnnouncementPushCancel {
    static let cancelledType = "announcement_cancelled"

    /// silent / background 取消しプッシュを処理する。処理したら true。
    static func handleRemoteNotificationUserInfo(_ userInfo: [AnyHashable: Any]) -> Bool {
        guard additionalDataString(from: userInfo, key: "type") == cancelledType else {
            return false
        }
        let announcementId = additionalDataString(from: userInfo, key: "announcement_id") ?? ""
        let originalTitle = additionalDataString(from: userInfo, key: "original_title") ?? ""
        removeDeliveredAnnouncementNotifications(announcementId: announcementId, originalTitle: originalTitle)
        return true
    }

    static func removeDeliveredAnnouncementNotifications(announcementId: String, originalTitle: String = "") {
        clearDelivered(
            cancelledIds: announcementId.isEmpty ? [] : [announcementId],
            cancelledTitles: originalTitle.isEmpty ? [] : [originalTitle]
        )
    }

    /// 過去に届いた空の取消し通知を起動時に掃除する
    static func removeOrphanCancelledNotifications() {
        UNUserNotificationCenter.current().getDeliveredNotifications { notifications in
            let identifiers = notifications.compactMap { notification -> String? in
                let userInfo = notification.request.content.userInfo
                let content = notification.request.content
                if additionalDataString(from: userInfo, key: "type") == cancelledType {
                    return notification.request.identifier
                }
                if content.body == "このお知らせは取り消されました" {
                    return notification.request.identifier
                }
                if isBlankText(content.title), isBlankText(content.subtitle), isBlankText(content.body) {
                    return notification.request.identifier
                }
                if isBlankText(content.title),
                   additionalDataString(from: userInfo, key: "announcement_id") != nil {
                    return notification.request.identifier
                }
                return nil
            }
            guard !identifiers.isEmpty else { return }
            UNUserNotificationCenter.current().removeDeliveredNotifications(withIdentifiers: identifiers)
        }
    }

    /// サーバの取消し ID 一覧を取得し、届済み通知を掃除する
    static func syncCancelledAnnouncementNotifications() {
        Task {
            // Debug の baseUrl は BundleMobileWebConfigRepository.developmentBaseUrl に集約
            let items = await CancelledAnnouncementIdsClient().fetchItems()
            let ids = items.map(\.id).filter { !$0.isEmpty }
            let titles = items.map(\.title).filter { !$0.isEmpty && !$0.hasPrefix("backfill-") }
            clearDelivered(cancelledIds: ids, cancelledTitles: titles)
        }
    }

    private static func clearDelivered(cancelledIds: [String], cancelledTitles: [String]) {
        let idSet = Set(cancelledIds)
        let titleSet = Set(cancelledTitles)
        guard !idSet.isEmpty || !titleSet.isEmpty else {
            return
        }

        for announcementId in idSet {
            let collapseId = "reggit:announcement:\(announcementId)"
            UNUserNotificationCenter.current().removeDeliveredNotifications(withIdentifiers: [collapseId, announcementId])
        }

        UNUserNotificationCenter.current().getDeliveredNotifications { notifications in
            let identifiers = notifications.compactMap { notification -> String? in
                if shouldRemove(notification, cancelledIds: idSet, cancelledTitles: titleSet) {
                    return notification.request.identifier
                }
                return nil
            }

            var toRemove = Set(identifiers)
            if toRemove.isEmpty, !idSet.isEmpty {
                for notification in notifications {
                    if looksLikeAnnouncementNotification(notification) {
                        toRemove.insert(notification.request.identifier)
                    }
                }
            }

            guard !toRemove.isEmpty else {
                return
            }
            UNUserNotificationCenter.current().removeDeliveredNotifications(withIdentifiers: Array(toRemove))
        }
    }

    /// お知らせプッシュっぽい届済みか（クーポン/順番待ち以外の プッシュ通知）
    private static func looksLikeAnnouncementNotification(_ notification: UNNotification) -> Bool {
        let userInfo = notification.request.content.userInfo
        let type = additionalDataString(from: userInfo, key: "type") ?? ""
        if type == "coupon_issued" || type == "queue_called" || type == cancelledType {
            return type == cancelledType
        }
        if additionalDataString(from: userInfo, key: "announcement_id") != nil {
            return true
        }
        if additionalDataString(from: userInfo, key: "delivery_type") != nil {
            return true
        }
        let thread = notification.request.content.threadIdentifier
        if thread.contains("reggit:announcement:") {
            return true
        }
        let flat = String(describing: userInfo)
        if flat.contains("announcement_id") || flat.contains("reggit:announcement:") || flat.contains("delivery_type") {
            return true
        }
        // プッシュ通知かつ type 不明 → お知らせ取消し同期中は候補にする
        if userInfo["custom"] != nil, type.isEmpty, !notification.request.content.title.isEmpty {
            return true
        }
        return false
    }

    private static func shouldRemove(
        _ notification: UNNotification,
        cancelledIds: Set<String>,
        cancelledTitles: Set<String>
    ) -> Bool {
        let content = notification.request.content
        let userInfo = content.userInfo
        let identifier = notification.request.identifier
        let thread = content.threadIdentifier
        let title = content.title

        if !cancelledTitles.isEmpty, cancelledTitles.contains(title) {
            return true
        }

        for announcementId in cancelledIds {
            let collapseId = "reggit:announcement:\(announcementId)"
            if identifier == collapseId || identifier.contains(collapseId) || identifier.contains(announcementId) {
                return true
            }
            if thread == collapseId || thread.contains(collapseId) {
                return true
            }
            if additionalDataString(from: userInfo, key: "announcement_id") == announcementId {
                return true
            }
            if additionalDataString(from: userInfo, key: "type") == cancelledType,
               additionalDataString(from: userInfo, key: "announcement_id") == announcementId || announcementId.isEmpty {
                return true
            }
            if apsThreadId(from: userInfo) == collapseId {
                return true
            }
            if deepContains(userInfo, announcementId: announcementId, collapseId: collapseId) {
                return true
            }
        }

        if additionalDataString(from: userInfo, key: "type") == cancelledType {
            return true
        }
        return false
    }

    private static func apsThreadId(from userInfo: [AnyHashable: Any]) -> String? {
        guard let aps = userInfo["aps"] as? [AnyHashable: Any] else { return nil }
        return stringValueIfPresent(aps["thread-id"]) ?? stringValueIfPresent(aps["thread_id"])
    }

    private static func deepContains(_ value: Any, announcementId: String, collapseId: String) -> Bool {
        if let string = value as? String {
            if string == announcementId || string == collapseId {
                return true
            }
            if string.contains("\"announcement_id\":\"\(announcementId)\"")
                || string.contains("\"announcement_id\":\(announcementId)")
                || string.contains(collapseId) {
                return true
            }
            if let data = string.data(using: .utf8),
               let json = try? JSONSerialization.jsonObject(with: data) {
                return deepContains(json, announcementId: announcementId, collapseId: collapseId)
            }
            return false
        }
        if let number = value as? NSNumber {
            return number.stringValue == announcementId
        }
        if let dict = value as? [AnyHashable: Any] {
            for (key, nested) in dict {
                if stringValue(key) == "announcement_id", stringValue(nested) == announcementId {
                    return true
                }
                if deepContains(nested, announcementId: announcementId, collapseId: collapseId) {
                    return true
                }
            }
            return false
        }
        if let array = value as? [Any] {
            return array.contains { deepContains($0, announcementId: announcementId, collapseId: collapseId) }
        }
        return false
    }

    static func isBlankText(_ text: String) -> Bool {
        let trimmed = text
            .replacingOccurrences(of: "\u{200B}", with: "")
            .replacingOccurrences(of: "\u{00A0}", with: "")
            .trimmingCharacters(in: .whitespacesAndNewlines)
        return trimmed.isEmpty
    }

    static func stringValue(_ value: Any?) -> String {
        if let string = value as? String {
            return string.trimmingCharacters(in: .whitespacesAndNewlines)
        }
        if let number = value as? NSNumber {
            return number.stringValue
        }
        if let key = value as? AnyHashable {
            return String(describing: key).trimmingCharacters(in: .whitespacesAndNewlines)
        }
        return ""
    }

    static func additionalDataStringPublic(from userInfo: [AnyHashable: Any], key: String) -> String? {
        additionalDataString(from: userInfo, key: key)
    }

    private static func additionalDataString(from userInfo: [AnyHashable: Any], key: String) -> String? {
        if let value = stringValueIfPresent(userInfo[key]) {
            return value
        }
        if let additional = additionalDataDictionary(from: userInfo),
           let value = stringValueIfPresent(additional[key]) {
            return value
        }
        return nil
    }

    private static func additionalDataDictionary(from userInfo: [AnyHashable: Any]) -> [AnyHashable: Any]? {
        if let custom = userInfo["custom"] as? [AnyHashable: Any] {
            if let additional = custom["a"] as? [AnyHashable: Any] {
                return additional
            }
            if let additionalString = custom["a"] as? String,
               let data = additionalString.data(using: .utf8),
               let additional = (try? JSONSerialization.jsonObject(with: data)) as? [AnyHashable: Any] {
                return additional
            }
        }
        if let customString = userInfo["custom"] as? String,
           let data = customString.data(using: .utf8),
           let custom = (try? JSONSerialization.jsonObject(with: data)) as? [AnyHashable: Any] {
            if let additional = custom["a"] as? [AnyHashable: Any] {
                return additional
            }
            if let additionalString = custom["a"] as? String,
               let additionalData = additionalString.data(using: .utf8),
               let additional = (try? JSONSerialization.jsonObject(with: additionalData)) as? [AnyHashable: Any] {
                return additional
            }
        }
        return nil
    }

    private static func stringValueIfPresent(_ value: Any?) -> String? {
        let trimmed = stringValue(value)
        return trimmed.isEmpty ? nil : trimmed
    }
}
#endif
