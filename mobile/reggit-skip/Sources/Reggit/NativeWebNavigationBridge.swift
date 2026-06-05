import Foundation
import SkipWeb

/// WebView からネイティブ機能へ橋渡しするコールバック（iOS Darwin 側で登録）。
@MainActor
public enum NativeWebNavigationBridge {
    public static var handleCustomURL: ((URL) -> Bool)?
    public static var handleDownload: ((WebDownloadRequest) -> Void)?

    public static func handle(url: URL) -> Bool {
        handleCustomURL?(url) ?? false
    }

    public static func handleDownloadRequest(_ request: WebDownloadRequest) {
        handleDownload?(request)
    }
}
