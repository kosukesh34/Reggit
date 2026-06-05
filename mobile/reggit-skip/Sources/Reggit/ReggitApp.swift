import Foundation
import SkipFuse
import SwiftUI

let logger: Logger = Logger(subsystem: "com.reggit.default", category: "Reggit")

/* SKIP @bridge */public struct ReggitRootView : View {
    /* SKIP @bridge */public init() {
    }

    public var body: some View {
        ContentView()
            .task {
                logger.info("Skip app logs are viewable in the Xcode console for iOS; Android logs can be viewed in Studio or using adb logcat")
            }
    }
}

/* SKIP @bridge */public final class ReggitAppDelegate : Sendable {
    /* SKIP @bridge */public static let shared = ReggitAppDelegate()
    private static let pushTokenKey = "reggit_push_token"

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
        let trimmed = token.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return }
        UserDefaults.standard.set(trimmed, forKey: Self.pushTokenKey)
        NotificationCenter.default.post(
            name: .oneSignalPushTokenDidUpdate,
            object: nil,
            userInfo: ["token": trimmed]
        )
    }

    /* SKIP @bridge */public func onNotificationForeground(_ notificationType: String) {
        let type = notificationType.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !type.isEmpty else { return }
        NotificationCenter.default.post(
            name: .oneSignalNotificationForeground,
            object: nil,
            userInfo: ["type": type]
        )
    }

    /* SKIP @bridge */public func onNotificationOpened(_ tab: String, notificationType: String) {
        let trimmed = tab.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return }
        var userInfo: [String: Any] = ["tab": trimmed]
        let type = notificationType.trimmingCharacters(in: .whitespacesAndNewlines)
        if !type.isEmpty {
            userInfo["type"] = type
        }
        NotificationCenter.default.post(
            name: .oneSignalNotificationOpened,
            object: nil,
            userInfo: userInfo
        )
    }
}
