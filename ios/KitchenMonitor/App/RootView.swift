import SwiftUI
import UIKit

struct RootView: View {
    @EnvironmentObject private var appState: AppState
    @State private var showSplash = true

    var body: some View {
        ZStack {
            if appState.isAuthenticated {
                BoardView()
            } else {
                KitchenLoginView()
            }
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

private struct KitchenLoginView: View {
    @EnvironmentObject private var appState: AppState
    @State private var enrollmentToken = ""
    @State private var error: String?
    @State private var loading = false

    var body: some View {
        NavigationStack {
            Form {
                Section("端末登録") {
                    TextField("管理画面で発行した登録コード", text: $enrollmentToken).textInputAutocapitalization(.characters).autocorrectionDisabled()
                    Text("登録コードは一度だけ使用できます。この店舗のKitchen端末として登録されます。")
                        .font(.footnote).foregroundStyle(.secondary)
                    if let error { Text(error).foregroundStyle(.red) }
                    Button(loading ? "ログイン中…" : "ログイン") { Task { await login() } }
                        .disabled(loading || enrollmentToken.isEmpty)
                }
            }
            .navigationTitle("Reggit Kitchen")
        }
    }

    private func login() async {
        loading = true
        defer { loading = false }
        do {
            _ = try await KitchenAPIClient().login(enrollmentToken: enrollmentToken)
            appState.didLogin()
        } catch {
            self.error = error.localizedDescription
        }
    }
}
