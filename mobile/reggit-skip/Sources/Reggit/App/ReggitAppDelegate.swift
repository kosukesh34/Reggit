import Foundation
import SkipFuse

/* SKIP @bridge */public final class ReggitAppDelegate : Sendable {
    /* SKIP @bridge */public static let shared = ReggitAppDelegate()

    private init() {
    }

    /* SKIP @bridge */public func onInit() {
        logger.debug("onInit")
    }

    /* SKIP @bridge */public func onLaunch() {
        logger.debug("onLaunch")
    }

    /* SKIP @bridge */public func onResume() {
        logger.debug("onResume")
    }

    /* SKIP @bridge */public func onPause() {
        logger.debug("onPause")
    }

    /* SKIP @bridge */public func onStop() {
        logger.debug("onStop")
    }

    /* SKIP @bridge */public func onDestroy() {
        logger.debug("onDestroy")
    }

    /* SKIP @bridge */public func onLowMemory() {
        logger.debug("onLowMemory")
    }

    /* SKIP @bridge */public func onPushTokenReceived(_ token: String) {
        guard let trimmed = token.trimmedNonEmpty else { return }
        UserDefaults.standard.set(trimmed, forKey: NativeAppStorage.pushTokenKey)
        NotificationCenter.default.post(
            name: .pushTokenDidUpdate,
            object: nil,
            userInfo: ["token": trimmed]
        )
    }

    /* SKIP @bridge */public func onNotificationForeground(_ notificationType: String) {
        guard let type = notificationType.trimmedNonEmpty else { return }
        NotificationCenter.default.post(
            name: .pushNotificationForeground,
            object: nil,
            userInfo: ["type": type]
        )
    }

    /* SKIP @bridge */public func onNotificationOpened(_ tab: String, notificationType: String) {
        guard let trimmedTab = tab.trimmedNonEmpty else { return }
        var userInfo: [String: Any] = ["tab": trimmedTab]
        if let type = notificationType.trimmedNonEmpty {
            userInfo["type"] = type
        }
        NotificationCenter.default.post(
            name: .pushNotificationOpened,
            object: nil,
            userInfo: userInfo
        )
    }
}
