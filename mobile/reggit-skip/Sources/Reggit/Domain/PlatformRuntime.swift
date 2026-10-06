import Foundation
#if os(Android)
import SkipFuse
#endif
#if os(iOS)
import UIKit
#endif

/// プラットフォーム差分をこのファイルに閉じ込める。Presentation では `#if os` を書かない。
enum PlatformRuntime {
    /// Skip currently selects Set.first rather than supporting multiple detents.
    /// A single Android detent keeps observation refreshes from changing sheet height.
    static var supportsMultipleModalDetents: Bool {
        #if os(Android)
        return false
        #else
        return true
        #endif
    }

    @MainActor static var deviceModel: String {
        #if os(Android)
        return androidBuildField(className: "android/os/Build", fieldName: "MODEL") ?? "Android"
        #elseif os(iOS)
        let identifier = ProcessInfo.processInfo.environment["SIMULATOR_MODEL_IDENTIFIER"] ?? appleMachineIdentifier
        return appleDeviceName(identifier: identifier)
        #else
        return "Mac"
        #endif
    }

    #if os(iOS)
    private static var appleMachineIdentifier: String {
        var system = utsname()
        uname(&system)
        let capacity = MemoryLayout.size(ofValue: system.machine)
        return withUnsafePointer(to: &system.machine) {
            $0.withMemoryRebound(to: CChar.self, capacity: capacity) {
                String(cString: $0)
            }
        }
    }
    #endif

    static func appleDeviceName(identifier: String) -> String {
        let models = [
            "iPhone12,1": "iPhone 11", "iPhone12,3": "iPhone 11 Pro", "iPhone12,5": "iPhone 11 Pro Max",
            "iPhone12,8": "iPhone SE（第2世代）",
            "iPhone13,1": "iPhone 12 mini", "iPhone13,2": "iPhone 12", "iPhone13,3": "iPhone 12 Pro", "iPhone13,4": "iPhone 12 Pro Max",
            "iPhone14,2": "iPhone 13 Pro", "iPhone14,3": "iPhone 13 Pro Max", "iPhone14,4": "iPhone 13 mini", "iPhone14,5": "iPhone 13",
            "iPhone14,6": "iPhone SE（第3世代）", "iPhone14,7": "iPhone 14", "iPhone14,8": "iPhone 14 Plus",
            "iPhone15,2": "iPhone 14 Pro", "iPhone15,3": "iPhone 14 Pro Max", "iPhone15,4": "iPhone 15", "iPhone15,5": "iPhone 15 Plus",
            "iPhone16,1": "iPhone 15 Pro", "iPhone16,2": "iPhone 15 Pro Max",
            "iPhone17,1": "iPhone 16 Pro", "iPhone17,2": "iPhone 16 Pro Max", "iPhone17,3": "iPhone 16",
            "iPhone17,4": "iPhone 16 Plus", "iPhone17,5": "iPhone 16e",
            "iPhone18,1": "iPhone 17 Pro", "iPhone18,2": "iPhone 17 Pro Max", "iPhone18,3": "iPhone 17",
            "iPhone18,4": "iPhone Air", "iPhone18,5": "iPhone 17e",
        ]
        return models[identifier] ?? (identifier == "arm64" || identifier == "x86_64" ? "iPhone Simulator" : identifier)
    }

    static var osVersion: String {
        #if os(Android)
        return androidBuildField(className: "android/os/Build$VERSION", fieldName: "RELEASE") ?? "不明"
        #else
        return ProcessInfo.processInfo.operatingSystemVersionString
        #endif
    }

    static var deviceTimeZone: TimeZone {
        #if os(Android)
        let identifier: String? = try? jniContext {
            let timeZoneClass = try JClass(name: "java/util/TimeZone", systemClass: true)
            guard let getDefault = timeZoneClass.getStaticMethodID(name: "getDefault", sig: "()Ljava/util/TimeZone;"),
                  let getID = timeZoneClass.getMethodID(name: "getID", sig: "()Ljava/lang/String;") else {
                return nil
            }
            let javaTimeZone: JavaObjectPointer = try timeZoneClass.callStatic(method: getDefault, options: [], args: [])
            return try JObject(javaTimeZone).call(method: getID, options: [], args: [])
        }
        return identifier.flatMap(TimeZone.init(identifier:)) ?? .current
        #else
        return .current
        #endif
    }

    #if os(Android)
    private static func androidBuildField(className: String, fieldName: String) -> String? {
        try? jniContext {
            let buildClass = try JClass(name: className, systemClass: true)
            guard let field = buildClass.getStaticFieldID(name: fieldName, sig: "Ljava/lang/String;") else {
                return nil
            }
            let value: String = buildClass.getStatic(field: field, options: [])
            return value.isEmpty ? nil : value
        }
    }
    #endif

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

    static var homeHorizontalPadding: CGFloat {
        return 18
    }
}
