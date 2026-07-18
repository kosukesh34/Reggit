import SwiftUI

struct ConnectionFallbackView: View {
    let title: String
    let message: String
    let onRetry: () -> Void

    init(
        title: String = ShellUserFacingMessages.connectionTitle,
        message: String,
        onRetry: @escaping () -> Void
    ) {
        self.title = title
        self.message = message
        self.onRetry = onRetry
    }

    var body: some View {
        ZStack {
            Rectangle()
                .fill(.ultraThinMaterial)
                .ignoresSafeArea()

            VStack(spacing: 16) {
                Image(systemName: "wifi.exclamationmark")
                    .font(.system(size: 40))
                    .foregroundStyle(.secondary)

                Text(title)
                    .font(.headline)
                    .multilineTextAlignment(.center)

                Text(message)
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                    .multilineTextAlignment(.center)
                    #if !os(Android)
                    .fixedSize(horizontal: false, vertical: true)
                    #endif

                Button("再読み込み", action: onRetry)
                    .buttonStyle(.borderedProminent)
                    .padding(.top, 4)
            }
            .padding(24)
            .frame(maxWidth: 340)
            .background(.ultraThinMaterial, in: RoundedRectangle(cornerRadius: 20))
            .padding(.horizontal, 24)
        }
    }
}
