import Foundation

struct KitchenSettings: Codable, Equatable {
    var baseURL: String
    var projectKey: String
    var passcode: String
    var deviceName: String
    var storeToken: String? = nil

    static var `default`: KitchenSettings {
        KitchenSettings(
            baseURL: AppBuildConfig.baseURL,
            projectKey: AppBuildConfig.projectKey,
            passcode: AppConstants.DefaultSettings.passcode,
            deviceName: "Kitchen iPad"
        )
    }
}
