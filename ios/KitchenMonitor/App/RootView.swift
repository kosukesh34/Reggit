import SwiftUI
import UIKit

struct RootView: View {
    @EnvironmentObject private var appState: AppState
    @State private var showSplash = true

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
            if showSplash {
                SplashView()
                    .transition(.opacity)
                    .zIndex(10)
            }
        }
        .onAppear {
            UIApplication.shared.isIdleTimerDisabled = true
            DispatchQueue.main.asyncAfter(deadline: .now() + 1.0) {
                withAnimation(.easeOut(duration: 0.25)) {
                    showSplash = false
                }
                DispatchQueue.main.asyncAfter(deadline: .now() + 0.4) {
                    PushRegistrationManager.shared.requestAuthorizationIfNeeded()
                }
            }
        }
    }
}
