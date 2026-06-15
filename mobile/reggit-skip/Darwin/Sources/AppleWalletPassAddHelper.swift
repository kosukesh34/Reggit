#if canImport(PassKit) && canImport(UIKit)
import PassKit
import Reggit
import UIKit

@MainActor
final class AppleWalletPassAddHelper {
    static let shared = AppleWalletPassAddHelper()

    private init() {}

    func addPass(from urlString: String) {
        let trimmed = urlString.trimmingCharacters(in: .whitespacesAndNewlines)
        guard let url = URL(string: trimmed) else {
            print("[AppleWallet] invalid pass URL: \(urlString.prefix(80))")
            return
        }
        Task {
            do {
                let data = try await downloadPassData(from: url)
                try await presentAddPass(data: data)
            } catch {
                print("[AppleWallet] download/present error: \(error.localizedDescription)")
            }
        }
    }

    private func downloadPassData(from url: URL) async throws -> Data {
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue(WebShellHTTP.ngrokSkipBrowserWarning, forHTTPHeaderField: "ngrok-skip-browser-warning")
        request.setValue("application/vnd.apple.pkpass", forHTTPHeaderField: "Accept")
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw URLError(.badServerResponse)
        }
        guard (200 ... 299).contains(http.statusCode) else {
            let preview = String(data: data.prefix(120), encoding: .utf8) ?? ""
            print("[AppleWallet] download failed status=\(http.statusCode) body=\(preview)")
            throw URLError(.badServerResponse)
        }
        let mime = http.mimeType?.lowercased() ?? ""
        if mime.contains("text/html") || mime.contains("application/json") {
            let preview = String(data: data.prefix(120), encoding: .utf8) ?? ""
            print("[AppleWallet] unexpected content-type=\(mime) body=\(preview)")
            throw URLError(.cannotDecodeContentData)
        }
        return data
    }

    private func presentAddPass(data: Data) async throws {
        let pass = try PKPass(data: data)
        guard PKAddPassesViewController.canAddPasses() else {
            print("[AppleWallet] cannot add passes on this device")
            return
        }
        guard let addVC = PKAddPassesViewController(pass: pass) else {
            print("[AppleWallet] PKAddPassesViewController init failed")
            return
        }
        guard let presenter = topViewController() else {
            print("[AppleWallet] no presenter view controller")
            return
        }
        addVC.modalPresentationStyle = .formSheet
        presenter.present(addVC, animated: true)
    }

    private func topViewController() -> UIViewController? {
        let scenes = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }
        let activeScene = scenes.first(where: { $0.activationState == .foregroundActive }) ?? scenes.first
        guard let window = activeScene?.windows.first(where: \.isKeyWindow) ?? activeScene?.windows.first,
              var presenter = window.rootViewController else {
            return nil
        }
        while let presented = presenter.presentedViewController {
            presenter = presented
        }
        if let nav = presenter as? UINavigationController {
            return nav.visibleViewController ?? nav
        }
        if let tab = presenter as? UITabBarController {
            return tab.selectedViewController ?? tab
        }
        return presenter
    }
}
#endif
