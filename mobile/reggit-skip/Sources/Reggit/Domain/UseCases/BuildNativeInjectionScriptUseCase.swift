import Foundation

struct BuildNativeInjectionScriptUseCase: NativeInjectionScriptBuilding {
    func execute(
        config: MobileWebShellConfig,
        device: NativeDeviceContextProvider,
        activeTab: String,
        applyInitialTab: Bool = true
    ) -> String {
        let applyInitialTabLiteral = applyInitialTab ? "true" : "false"
        return """
        (() => {
            const activeTab = \(activeTab.jsStringLiteral);
            const applyInitialTab = \(applyInitialTabLiteral);

            \(NativeInjectionScripts.redirectGuard(expectedLiffURL: config.liffURLString))

            \(NativeInjectionScripts.deviceInfoBlock(device: device))

            \(NativeInjectionScripts.bridgeFunctions(urlScheme: NativeAppIdentity.urlScheme))

            window.__NATIVE_TARGET_TAB__ = activeTab;

            \(NativeInjectionScripts.viewportEnforcement)
            \(NativeInjectionScripts.zoomLock)
            \(NativeInjectionScripts.hideWebTabBar)
            \(NativeInjectionScripts.applyInitialTab(activeTab: activeTab, applyInitialTab: applyInitialTab))

            enforceViewport();
            enforceNoZoom();
            hideWebTabBar();
            if (applyInitialTab) {
                window.__NATIVE_WEB_READY__ = true;
                applyInitialTabIfNeeded();
            } else {
                window.__NATIVE_WEB_READY__ = false;
            }
            return 'ready';
        })();
        """
    }
}
