import SwiftUI

struct LoadingOverlay: View {
    @State private var isAnimating = false

    var body: some View {
        ZStack {
            Color.black.opacity(0.35)
                .ignoresSafeArea()

            VStack(spacing: Theme.Spacing.lg) {
                ZStack {
                    Circle()
                        .stroke(Color(.systemGray5), lineWidth: 4)
                        .frame(width: 44, height: 44)
                    Circle()
                        .trim(from: 0, to: 0.7)
                        .stroke(
                            Theme.Colors.gradientAccent,
                            style: StrokeStyle(lineWidth: 4, lineCap: .round)
                        )
                        .frame(width: 44, height: 44)
                        .rotationEffect(.degrees(isAnimating ? 360 : 0))
                        .animation(.linear(duration: 0.8).repeatForever(autoreverses: false), value: isAnimating)
                }
                Text(Strings.Common.loading)
                    .font(.system(size: 15, weight: .semibold))
                    .foregroundColor(Theme.Colors.textSecondary)
            }
            .padding(Theme.Spacing.xxxl)
            .background(.ultraThinMaterial)
            .cornerRadius(Theme.Radius.xl)
            .applyShadow(Theme.Shadow.lg)
        }
        .onAppear { isAnimating = true }
    }
}
