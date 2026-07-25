import SwiftUI
import UIKit

struct RootView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        ZStack {
            BoardView()
            if appState.isShowingPasscode {
                PasscodeView(
                    expectedPasscode: appState.expectedPasscode,
                    onSuccess: { appState.unlock() },
                    onCancel: { appState.cancelPasscode() }
                )
            }
        }
        .onAppear {
            UIApplication.shared.isIdleTimerDisabled = true
        }
    }
}
