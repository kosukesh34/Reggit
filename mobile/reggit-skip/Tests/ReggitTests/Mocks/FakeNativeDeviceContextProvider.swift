import Foundation
@testable import Reggit

struct FakeNativeDeviceContextProvider: NativeDeviceContextProvider {
    var platform: NativePlatform = .ios
    var deviceIdentifier: String = "test-device-id"
    var osVersion: String = "iOS 18.0"
    var appVersion: String = "1.0.0(1)"
    var pushToken: String? = "fake-push-token"
}
