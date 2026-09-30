import SwiftUI

struct MemberCodeSheet: View {
    let member: MemberProfileSnapshot
    let onClose: () -> Void

    var body: some View {
        ScrollView {
            VStack(spacing: 24) {
                HStack {
                    Text("会員証を提示").font(.title2.bold())
                    Spacer()
                    Button(action: onClose) {
                        FontAwesomeIcon(systemName: "xmark.circle.fill", size: 24)
                            .font(.title2)
                            .foregroundStyle(AppDesign.inkSecondary)
                            .frame(width: 44, height: 44)
                    }
                    .accessibilityLabel("閉じる")
                }
                MemberCodeView(value: member.memberNumber, codeType: member.cardCodeType, height: 120)
                    .padding(20)
                    .frame(maxWidth: .infinity)
                    .background(Color.white)
                    .clipShape(RoundedRectangle(cornerRadius: 16))
                Text(member.memberNumber)
                    .font(.system(size: 18, weight: .bold, design: .monospaced))
                    #if !os(Android)
                    .textSelection(.enabled)
                    #endif
                Text("スタッフにこちらのコードをご提示ください")
                    .font(.subheadline)
                    .foregroundStyle(AppDesign.inkSecondary)
                    .multilineTextAlignment(.center)
            }
            .padding(24)
        }
        .background(AppDesign.canvas.ignoresSafeArea())
    }
}
