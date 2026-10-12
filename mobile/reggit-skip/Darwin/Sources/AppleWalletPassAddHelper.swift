#if canImport(PassKit) && canImport(UIKit)
import PassKit
import Reggit
import UIKit

@MainActor
final class AppleWalletPassAddHelper: NSObject, @preconcurrency PKAddPassesViewControllerDelegate {
    static let shared = AppleWalletPassAddHelper()

    private override init() { super.init() }

    func addPass(from urlString: String) async throws {
        let trimmed = urlString.trimmingCharacters(in: .whitespacesAndNewlines)
        guard let url = URL(string: trimmed), ["https", "http"].contains(url.scheme?.lowercased() ?? "") else {
            throw URLError(.badURL)
        }
        let data = try await downloadPassData(from: url)
        try presentAddPass(data: data)
    }

    func addPassesViewControllerDidFinish(_ controller: PKAddPassesViewController) {
        controller.dismiss(animated: true)
    }

    func showError(_ error: Error) {
        let alert = UIAlertController(title: "Apple Wallet に追加できませんでした", message: error.localizedDescription, preferredStyle: .alert)
        alert.addAction(UIAlertAction(title: "閉じる", style: .default))
        topViewController()?.present(alert, animated: true)
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

    private func presentAddPass(data: Data) throws {
        let pass = try PKPass(data: data)
        guard PKAddPassesViewController.canAddPasses() else {
            throw URLError(.unsupportedURL)
        }
        guard let addVC = PKAddPassesViewController(pass: pass) else {
            throw URLError(.cannotDecodeContentData)
        }
        guard let presenter = topViewController() else {
            throw URLError(.unknown)
        }
        addVC.delegate = self
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
