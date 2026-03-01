import Foundation

enum NativePlatform: String {
    case ios
    case android
    case unknown
}

struct MobileWebShellConfig {
    let baseUrl: String
    let projectKey: String
    let fixedTab: String

    var liffURLString: String {
        "\(baseUrl)/liff/\(projectKey)"
    }
}

protocol MobileWebConfigRepository {
    func resolveConfig() -> MobileWebShellConfig
}

protocol NativeDeviceContextProvider {
    var platform: NativePlatform { get }
    var deviceIdentifier: String { get }
    var osVersion: String { get }
    var appVersion: String { get }
}
