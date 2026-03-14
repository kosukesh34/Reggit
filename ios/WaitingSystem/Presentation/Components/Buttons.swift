import SwiftUI

struct PrimaryButton: View {
    let title: String
    let action: () -> Void

    @State private var isPressed = false

    var body: some View {
        Button(action: action) {
            Text(title)
                .font(.system(size: 22, weight: .bold))
                .frame(maxWidth: .infinity)
                .padding(.vertical, 18)
                .background(Theme.Colors.gradientAccent)
                .foregroundColor(Theme.Colors.textOnAccent)
                .cornerRadius(Theme.Radius.lg)
                .applyShadow(Theme.Shadow.accent)
                .scaleEffect(isPressed ? 0.97 : 1.0)
                .animation(.spring(response: 0.3, dampingFraction: 0.6), value: isPressed)
        }
        .buttonStyle(.plain)
        .simultaneousGesture(
            DragGesture(minimumDistance: 0)
                .onChanged { _ in isPressed = true }
                .onEnded { _ in isPressed = false }
        )
    }
}

struct SecondaryButton: View {
    let title: String
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            Text(title)
                .font(.system(size: 18, weight: .semibold))
                .frame(width: 170)
                .padding(.vertical, Theme.Spacing.md + 2)
                .background(Theme.Colors.background)
                .foregroundColor(Theme.Colors.accent)
                .cornerRadius(Theme.Radius.md)
                .overlay(
                    RoundedRectangle(cornerRadius: Theme.Radius.md)
                        .stroke(Theme.Colors.accentBorder, lineWidth: 1.5)
                )
                .applyShadow(Theme.Shadow.sm)
        }
        .buttonStyle(.plain)
    }
}

struct GearButton: View {
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            Image(systemName: "gearshape.fill")
                .font(.system(size: 18, weight: .semibold))
                .foregroundColor(Theme.Colors.textSecondary)
                .padding(Theme.Spacing.md + 2)
                .background(.ultraThinMaterial)
                .clipShape(Circle())
                .applyShadow(Theme.Shadow.sm)
        }
        .padding()
        .accessibilityLabel(Strings.Accessibility.settingsButton)
    }
}

struct DangerButton: View {
    let title: String
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            Text(title)
                .font(.system(size: 18, weight: .semibold))
                .frame(width: 170)
                .padding(.vertical, Theme.Spacing.md + 2)
                .background(Theme.Colors.dangerSubtle)
                .foregroundColor(Theme.Colors.danger)
                .cornerRadius(Theme.Radius.md)
                .overlay(
                    RoundedRectangle(cornerRadius: Theme.Radius.md)
                        .stroke(Theme.Colors.dangerBorder, lineWidth: 1.5)
                )
        }
        .buttonStyle(.plain)
    }
}
