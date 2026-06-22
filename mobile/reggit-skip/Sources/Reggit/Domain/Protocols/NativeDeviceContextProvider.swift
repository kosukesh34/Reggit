import Foundation

protocol NativePlatformProviding {
    var platform: NativePlatform { get }
}

protocol NativeDeviceIdentifying {
    var deviceIdentifier: String { get }
}

protocol NativeAppMetadataProviding {
    var osVersion: String { get }
    var appVersion: String { get }
}

protocol PushTokenProviding {
    var pushToken: String? { get }
}

typealias NativeDeviceContextProvider =
    NativePlatformProviding &
    NativeDeviceIdentifying &
    NativeAppMetadataProviding &
    PushTokenProviding
