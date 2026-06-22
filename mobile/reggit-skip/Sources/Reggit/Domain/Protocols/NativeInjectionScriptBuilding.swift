import Foundation

protocol NativeInjectionScriptBuilding {
    func execute(
        config: MobileWebShellConfig,
        device: NativeDeviceContextProvider,
        activeTab: String,
        applyInitialTab: Bool
    ) -> String
}
