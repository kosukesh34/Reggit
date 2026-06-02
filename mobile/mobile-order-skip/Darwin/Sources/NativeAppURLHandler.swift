#if canImport(UIKit)
import MobileOrderApp
import SkipWeb
import UIKit

enum NativeAppURLHandler {
    @MainActor
    static func install() {
        NativeWebNavigationBridge.handleCustomURL = { url in
            handle(url)
        }
        #if canImport(PassKit)
        NativeWebNavigationBridge.handleDownload = { request in
            guard let url = request.url else { return }
            let mime = request.mimeType?.lowercased() ?? ""
            let isPkPass = mime.contains("pkpass")
                || mime.contains("application/zip")
                || url.pathExtension.lowercased() == "pkpass"
                || url.path.contains("wallet-pass")
            guard isPkPass else { return }
            AppleWalletPassAddHelper.shared.addPass(from: url.absoluteString)
        }
        #endif
    }

    @MainActor
    @discardableResult
    static func handle(_ url: URL) -> Bool {
        guard url.scheme == "mobileorderapp" else { return false }
        if url.host == "openExternal" {
            guard let components = URLComponents(url: url, resolvingAgainstBaseURL: false),
                  let externalString = components.queryItems?.first(where: { $0.name == "url" })?.value,
                  let externalURL = URL(string: externalString) else { return false }
            UIApplication.shared.open(externalURL)
            return true
        }
        #if canImport(PassKit)
        if url.host == "addAppleWalletPass" {
            guard let components = URLComponents(url: url, resolvingAgainstBaseURL: false),
                  let passUrlString = components.queryItems?.first(where: { $0.name == "url" })?.value else { return false }
            AppleWalletPassAddHelper.shared.addPass(from: passUrlString)
            return true
        }
        #endif
        return true
    }
}
#endif
