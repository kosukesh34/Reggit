import Foundation

/// プラットフォーム差分をこのファイルに閉じ込める。Presentation では `#if os` を書かない。
enum PlatformRuntime {
    static var nativePlatform: NativePlatform {
        #if os(Android)
        return .android
        #elseif os(iOS)
        return .ios
        #else
        return .unknown
        #endif
    }

    /// Android の URLSession.shared がハングする環境向けフォールバック。
    static var shouldSkipNetworkReachabilityProbe: Bool {
        #if os(Android)
        return true
        #else
        return false
        #endif
    }

    static var prefersBundleInfoDictionaryMeta: Bool {
        #if os(Android)
        return false
        #else
        return true
        #endif
    }

    /// Skip の Compose 側で `@Observable` の変更が View に届かないことがある。
    /// 0 ならキック不要（iOS は Observation に任せる）。
    static var viewObservationKickNanoseconds: UInt64 {
        #if os(Android)
        return 250_000_000
        #else
        return 0
        #endif
    }

    /// Skip/Compose と SwiftUI でステータスバー周辺の余白が異なるため、
    /// 共通ヘッダー画像の見た目上の中心を端末ごとに揃える。
    static var navigationHeaderHeight: CGFloat {
        #if os(Android)
        return 56
        #else
        return 52
        #endif
    }

    static var navigationHeaderImageWidth: CGFloat {
        #if os(Android)
        return 176
        #else
        return 168
        #endif
    }

    static var navigationHeaderImageVerticalOffset: CGFloat {
        #if os(Android)
        return 2
        #else
        return 1
        #endif
    }

    static var homeHeroHeight: CGFloat {
        #if os(Android)
        return 204
        #else
        return 220
        #endif
    }

    static var homeHorizontalPadding: CGFloat {
        #if os(Android)
        return 16
        #else
        return 18
        #endif
    }
}
