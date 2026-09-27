import Foundation

struct AppSettings: Codable, Equatable {
    var baseURL: String
    var liffURL: String
    var projectKey: String
    var backgroundColorHex: String
    var printerURL: String?
    var printerName: String?
    var shouldPrintTicket: Bool
    var passcode: String

    static let `default` = AppSettings(
        baseURL: AppConstants.DefaultSettings.baseURL,
        liffURL: AppConstants.DefaultSettings.liffURL,
        projectKey: "",
        backgroundColorHex: AppConstants.DefaultSettings.backgroundColorHex,
        printerURL: nil,
        printerName: nil,
        shouldPrintTicket: true,
        passcode: AppConstants.DefaultSettings.passcode
    )
}
