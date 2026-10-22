import SwiftUI

/// 未会員の起動直後の全画面。お店の写真を背景に「ログイン」「新規会員登録」だけを出す。
/// お店の画面（ヘッダー・メニュー）は認証が終わるまで出さない。
struct WelcomeView: View {
    let apiClient: any ReggitAPIServing
    let title: String
    /// 管理画面で設定したお店のホーム画像。未設定なら同梱の店内写真
    let backgroundImageURL: String?
    let canSkip: Bool
    let onRegister: () -> Void
    let onTransfer: () -> Void
    let onExistingMember: () -> Void
    let onSkip: () -> Void
    @State var showsLogin = false
    @Environment(\.brandAccent) var accent
    @Environment(\.brandForeground) var foreground
    @Environment(\.appButtonColors) var buttonColors

    var body: some View {
        GeometryReader { geometry in
            ZStack(alignment: .bottom) {
                RemoteImageView(urlString: backgroundImageURL, usesDemoFallback: true)
                    .frame(width: geometry.size.width, height: geometry.size.height)
                    .clipped()
                    .ignoresSafeArea(edges: .top)
                    .accessibilityHidden(true)

                VStack(spacing: 14) {
                    Text(title)
                        .font(.title2.weight(.bold))
                        .foregroundStyle(AppDesign.ink)
                        .multilineTextAlignment(.center)
                        .padding(.bottom, 8)
                    AppPrimaryButton(title: "ログイン") { showsLogin = true }
                    AppSecondaryButton(title: "新規会員登録", action: onRegister)
                    if canSkip {
                        Button("ログインせずに使う", action: onSkip)
                            .font(.footnote)
                            .foregroundStyle(AppDesign.inkSecondary)
                            .padding(.top, 4)
                    }
                }
                .padding(.horizontal, 24)
                .padding(.top, 32)
                .padding(.bottom, 28)
                .frame(maxWidth: .infinity)
                .background(AppDesign.surface)
                .clipShape(RoundedRectangle(cornerRadius: 32, style: .continuous))
            }
        }
        .background(AppDesign.surface.ignoresSafeArea())
        .navigationDestination(isPresented: $showsLogin) {
            // Android では遷移先に環境値が引き継がれないため、テーマ色を渡し直す
            LoginMethodsView(apiClient: apiClient, onTransfer: onTransfer, onExistingMember: onExistingMember)
                .environment(\.brandAccent, accent)
                .environment(\.brandForeground, foreground)
                .environment(\.appButtonColors, buttonColors)
        }
    }
}

/// ログイン方法（メールのリンク・機種変更・お店の会員カード）
struct LoginMethodsView: View {
    let apiClient: any ReggitAPIServing
    let onTransfer: () -> Void
    let onExistingMember: () -> Void
    @State var email = ""
    @State var isSending = false
    @State var message: String?
    @State var errorMessage: String?
    @Environment(\.brandAccent) var accent

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                Text("登録したメールアドレス")
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(AppDesign.inkSecondary)
                TextField("example@mail.com", text: $email)
                    .keyboardType(.emailAddress)
                    .textContentType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .padding(.horizontal, 14)
                    .frame(minHeight: 52)
                    .background(AppDesign.canvas)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                    .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                        .stroke(AppDesign.hairline, lineWidth: 1))
                AppPrimaryButton(title: "ログインリンクを送る", isLoading: isSending, enabled: !isSending) {
                    Task { await sendLoginLink() }
                }
                if let message {
                    Text(message).font(.footnote).foregroundStyle(AppDesign.success)
                }
                if let errorMessage {
                    Text(errorMessage).font(.footnote).foregroundStyle(AppDesign.danger)
                }

                VStack(spacing: 0) {
                    methodRow("機種変更の方", action: onTransfer)
                    Divider()
                    methodRow("お店の会員カードをお持ちの方", action: onExistingMember)
                }
                .background(AppDesign.surface)
                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                    .stroke(AppDesign.hairline, lineWidth: 1))
                .padding(.top, 16)
            }
            .padding(24)
        }
        .background(AppDesign.surface.ignoresSafeArea())
        .navigationTitle("ログイン")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
    }

    private func methodRow(_ title: String, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            HStack {
                Text(title).font(.body.weight(.semibold)).foregroundStyle(AppDesign.ink)
                Spacer()
                FontAwesomeIcon(systemName: "chevron.right").font(.footnote).foregroundStyle(AppDesign.inkSecondary)
            }
            .padding(.horizontal, 16)
            .frame(minHeight: 56)
        }
        .buttonStyle(.plain)
    }

    private func sendLoginLink() async {
        let trimmed = email.trimmingCharacters(in: .whitespacesAndNewlines)
        guard trimmed.contains("@") else {
            errorMessage = "メールアドレスを入力してください"
            message = nil
            return
        }
        isSending = true
        errorMessage = nil
        message = nil
        defer { isSending = false }
        do {
            message = try await apiClient.requestEmailLogin(email: trimmed)
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
