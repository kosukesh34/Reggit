import SwiftUI

struct PasscodeView: View {
    let onSuccess: () -> Void
    let onCancel: () -> Void
    let expectedPasscode: String

    @State private var input = ""
    @State private var isError = false
    @State private var shakeOffset: CGFloat = 0

    private let maxDigits = 8
    private let minDigits = 4
    private let keys = [
        ["1", "2", "3"],
        ["4", "5", "6"],
        ["7", "8", "9"],
        ["clear", "0", "delete"]
    ]

    var body: some View {
        GeometryReader { geo in
            let containerWidth = min(480, max(280, geo.size.width - 48))
            let keyWidth = min(110, max(74, (containerWidth - 32) / 3))

            ScrollView(showsIndicators: false) {
                VStack(spacing: Theme.Spacing.xxl + 4) {
                    lockIcon
                    dotIndicators
                    errorMessage
                    keypad(keyWidth: keyWidth)
                    bottomButtons
                }
                .frame(maxWidth: containerWidth)
                .padding(.horizontal, Theme.Spacing.xxl)
                .padding(.vertical, Theme.Spacing.xxl + 4)
                .frame(maxWidth: .infinity)
            }
        }
    }

    // MARK: - Lock Icon

    private var lockIcon: some View {
        VStack(spacing: Theme.Spacing.md) {
            ZStack {
                Circle()
                    .fill(Theme.Colors.accentSubtle)
                    .frame(width: 68, height: 68)
                Image(systemName: "lock.shield.fill")
                    .font(.system(size: 30, weight: .semibold))
                    .foregroundColor(Theme.Colors.accent)
            }
            Text(Strings.Passcode.title)
                .font(.system(size: 26, weight: .bold, design: .rounded))
        }
    }

    // MARK: - Dot Indicators

    private var dotIndicators: some View {
        HStack(spacing: 10) {
            ForEach(0..<maxDigits, id: \.self) { index in
                Circle()
                    .fill(index < input.count ? Theme.Colors.accent : Color(.systemGray5))
                    .frame(width: 14, height: 14)
                    .scaleEffect(index < input.count ? 1.2 : 1.0)
                    .animation(.spring(response: 0.25, dampingFraction: 0.5), value: input.count)
            }
        }
        .offset(x: shakeOffset)
    }

    // MARK: - Error

    @ViewBuilder
    private var errorMessage: some View {
        if isError {
            HStack(spacing: 6) {
                Image(systemName: "exclamationmark.triangle.fill")
                    .font(.system(size: 13))
                Text(Strings.Passcode.wrongPasscode)
                    .font(.system(size: 14, weight: .semibold))
            }
            .foregroundColor(Theme.Colors.danger)
            .transition(.opacity.combined(with: .scale(scale: 0.9)))
        }
    }

    // MARK: - Keypad

    @ViewBuilder
    private func keypad(keyWidth: CGFloat) -> some View {
        VStack(spacing: 10) {
            ForEach(keys, id: \.self) { row in
                HStack(spacing: 10) {
                    ForEach(row, id: \.self) { key in
                        keypadButton(for: key, keyWidth: keyWidth)
                    }
                }
            }
        }
    }

    // MARK: - Bottom Buttons

    private var bottomButtons: some View {
        HStack(spacing: Theme.Spacing.lg) {
            Button(action: onCancel) {
                Text(Strings.Common.cancel)
                    .font(.system(size: 18, weight: .semibold))
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, Theme.Spacing.md + 2)
                    .background(Theme.Colors.secondaryBackground)
                    .foregroundColor(Theme.Colors.textPrimary)
                    .cornerRadius(Theme.Radius.md)
            }
            .buttonStyle(.plain)

            Button(action: validatePasscode) {
                Text(Strings.Passcode.unlock)
                    .font(.system(size: 18, weight: .bold))
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, Theme.Spacing.md + 2)
                    .background(input.count >= minDigits ? Theme.Colors.accent : Color(.systemGray4))
                    .foregroundColor(Theme.Colors.textOnAccent)
                    .cornerRadius(Theme.Radius.md)
            }
            .buttonStyle(.plain)
            .disabled(input.count < minDigits)
        }
    }

    // MARK: - Keypad Button

    @ViewBuilder
    private func keypadButton(for key: String, keyWidth: CGFloat) -> some View {
        let size = CGSize(width: keyWidth, height: 62)
        switch key {
        case "clear":
            Button {
                input = ""
                isError = false
            } label: {
                Text(Strings.Passcode.clear)
                    .font(.system(size: 15, weight: .semibold))
                    .frame(width: size.width, height: size.height)
                    .background(Color(.systemGray5))
                    .foregroundColor(Theme.Colors.textSecondary)
                    .cornerRadius(Theme.Radius.md)
            }
            .buttonStyle(.plain)
        case "delete":
            Button {
                if !input.isEmpty { input.removeLast() }
                isError = false
            } label: {
                Image(systemName: "delete.left.fill")
                    .font(.system(size: 20, weight: .semibold))
                    .frame(width: size.width, height: size.height)
                    .background(Color(.systemGray5))
                    .foregroundColor(Theme.Colors.textSecondary)
                    .cornerRadius(Theme.Radius.md)
            }
            .buttonStyle(.plain)
        default:
            Button {
                guard input.count < maxDigits else { return }
                input.append(key)
                isError = false
            } label: {
                Text(key)
                    .font(.system(size: 24, weight: .semibold, design: .rounded))
                    .frame(width: size.width, height: size.height)
                    .background(Theme.Colors.secondaryBackground)
                    .foregroundColor(Theme.Colors.textPrimary)
                    .cornerRadius(Theme.Radius.md)
            }
            .buttonStyle(.plain)
        }
    }

    // MARK: - Validation

    private func validatePasscode() {
        if input == expectedPasscode {
            onSuccess()
            return
        }
        withAnimation(.default) { isError = true }
        withAnimation(Animation.linear(duration: 0.06).repeatCount(5, autoreverses: true)) {
            shakeOffset = 12
        }
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.35) { shakeOffset = 0 }
    }
}
