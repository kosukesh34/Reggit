import Foundation

enum AppConstants {
    enum Timing {
        static let pollIntervalSeconds: TimeInterval = 8
        static let unlockDurationSeconds: TimeInterval = 120
    }

    enum Storage {
        static let settingsKey = "kitchen.monitor.settings"
        static let pushTokenKey = "kitchen.monitor.pushToken"
    }

    enum DefaultSettings {
        static let passcode = "1234"
    }
}
