import Foundation
import SkipFuse
import SwiftUI

let logger: Logger = Logger(subsystem: "jp.gamechanger.mobileorder", category: "MobileOrderApp")

/* SKIP @bridge */public struct MobileOrderAppRootView : View {
    /* SKIP @bridge */public init() {
    }

    public var body: some View {
        ContentView()
            .task {
                logger.info("Skip app logs are viewable in the Xcode console for iOS; Android logs can be viewed in Studio or using adb logcat")
            }
    }
}

/* SKIP @bridge */public final class MobileOrderAppAppDelegate : Sendable {
    /* SKIP @bridge */public static let shared = MobileOrderAppAppDelegate()
    private static let pushTokenKey = "mobile_order_push_token"

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
}
