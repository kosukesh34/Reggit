import SwiftUI

#if canImport(UIKit) && canImport(PassKit)
import UIKit
import PassKit

/// Apple's official, localized Wallet artwork, rendered by PassKit.
/// https://developer.apple.com/wallet/add-to-apple-wallet-guidelines/
struct AppleWalletButton: UIViewRepresentable {
    var enabled: Bool
    let action: () -> Void

    func makeCoordinator() -> Coordinator { Coordinator(action: action) }

    func makeUIView(context: Context) -> PKAddPassButton {
        let button = PKAddPassButton(addPassButtonStyle: .black)
        button.addTarget(context.coordinator, action: #selector(Coordinator.addPass), for: .touchUpInside)
        button.isEnabled = enabled
        return button
    }

    func updateUIView(_ button: PKAddPassButton, context: Context) {
        button.isEnabled = enabled
        context.coordinator.action = action
    }

    func sizeThatFits(_ proposal: ProposedViewSize, uiView: PKAddPassButton, context: Context) -> CGSize? {
        let natural = uiView.intrinsicContentSize
        let height: CGFloat = 44
        return CGSize(width: natural.width * height / max(natural.height, 1), height: height)
    }

    @MainActor
    final class Coordinator: NSObject {
        var action: () -> Void
        init(action: @escaping () -> Void) { self.action = action }
        @objc func addPass() { action() }
    }
}
#else
struct AppleWalletButton: View {
    var enabled: Bool
    let action: () -> Void
    var body: some View { EmptyView() }
}
#endif
