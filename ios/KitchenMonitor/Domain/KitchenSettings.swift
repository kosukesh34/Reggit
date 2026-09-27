import Foundation

struct KitchenSettings: Codable, Equatable {
    var baseURL: String
    var projectKey: String
    var passcode: String
    var deviceName: String

    static var `default`: KitchenSettings {
        KitchenSettings(
            baseURL: AppBuildConfig.baseURL,
            projectKey: "",
            passcode: AppConstants.DefaultSettings.passcode,
            deviceName: "Kitchen iPad"
        )
    }
}
