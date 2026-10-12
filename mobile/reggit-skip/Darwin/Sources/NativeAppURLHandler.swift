#if canImport(UIKit)
import Reggit
import UIKit

enum NativeAppURLHandler {
    @MainActor
    static func install() {
        // WebView 橋渡しは不要。ディープリンクは Main / QueueDeepLinkHandler で処理する。
    }

    @MainActor
    @discardableResult
    static func handle(_ url: URL) -> Bool {
        guard url.scheme == NativeAppIdentity.urlScheme else { return false }
        if QueueDeepLinkHandler.handle(url) {
            return true
        }
        if PaymentDeepLinkHandler.handle(url) {
            return true
        }
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
            Task {
                do { try await AppleWalletPassAddHelper.shared.addPass(from: passUrlString) }
                catch { AppleWalletPassAddHelper.shared.showError(error) }
            }
            return true
        }
        #endif
        return false
    }
}
#endif
