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
        let tab = fixedTab.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? fixedTab
        return "\(baseUrl)/liff/\(projectKey)?tab=\(tab)"
    }
}
