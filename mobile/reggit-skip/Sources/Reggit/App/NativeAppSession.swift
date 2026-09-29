import Foundation
import Observation

/// ネイティブ顧客アプリの共有セッション（WebView シェルの代替）。
@MainActor
enum NativeAppSession {
    private static var sharedRoot: NativeRootViewModel?

    static func rootViewModel() -> NativeRootViewModel {
        if let sharedRoot {
            return sharedRoot
        }
        let created = NativeRootViewModel()
        sharedRoot = created
        return created
    }

    static func reset() {
        sharedRoot?.shutdown()
        sharedRoot = nil
        SecondaryScreenCache.reset()
    }
}
