import SwiftUI

struct MembershipRegistrationGateView: View {
    let projectName: String
    let message: String?
    let canSkip: Bool
    let isRegistering: Bool
    let errorMessage: String?
    @Binding var name: String
    @Binding var phone: String
    let onSubmit: () -> Void
    let onSkip: () -> Void
    @Environment(\.brandAccent) var accent

    var body: some View {
        VStack(spacing: 0) {
            VStack(alignment: .leading, spacing: 8) {
                Text(projectName)
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(.white.opacity(0.75))
                Text("ようこそ")
                    .font(.largeTitle.weight(.bold))
                    .foregroundStyle(.white)
                Text(displayMessage)
                    .font(.subheadline)
                    .foregroundStyle(.white.opacity(0.82))
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            .padding(24)
            .padding(.top, 28)
            .background(AppDesign.ink)

            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    Text("会員登録")
                        .font(.headline)
                    MemberFieldStack(name: $name, phone: $phone)
                    if let errorMessage, !errorMessage.isEmpty {
                        Text(errorMessage)
                            .font(.footnote)
                            .foregroundStyle(AppDesign.danger)
                    }
                    AppPrimaryButton(
                        title: "登録してはじめる",
                        isLoading: isRegistering,
                        action: onSubmit
                    )
                    if canSkip {
                        Button("あとで登録する", action: onSkip)
                            .frame(maxWidth: .infinity)
                            .padding(.top, 4)
                    }
                }
                .padding(20)
            }
            .background(AppDesign.canvas)
        }
        .background(AppDesign.canvas.ignoresSafeArea())
    }

    private var displayMessage: String {
        if let message, !message.isEmpty { return message }
        return canSkip
            ? "会員登録でポイントやクーポンが使えます。"
            : "ご利用には会員登録が必要です。"
    }
}
