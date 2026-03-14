import SwiftUI

struct PartySizeSheet: View {
    @Binding var partySize: Int
    let onConfirm: () -> Void
    let onCancel: () -> Void

    private let quickOptions = [1, 2, 3, 4, 5, 6, 8, 10]

    var body: some View {
        NavigationView {
            ScrollView(showsIndicators: false) {
                VStack(spacing: Theme.Spacing.xxl + 4) {
                    questionHeader
                    numberDisplay
                    quickGrid
                    actionButtons
                }
                .padding(Theme.Spacing.xxl + 4)
                .frame(maxWidth: 600)
                .frame(maxWidth: .infinity)
            }
            .navigationTitle(Strings.PartySize.navigationTitle)
            .navigationBarTitleDisplayMode(.inline)
        }
    }

    // MARK: - Header

    private var questionHeader: some View {
        VStack(spacing: Theme.Spacing.sm) {
            Image(systemName: "person.2.fill")
                .font(.system(size: 36))
                .foregroundColor(Theme.Colors.accent.opacity(0.7))
            Text(Strings.PartySize.question)
                .font(.system(size: 28, weight: .bold, design: .rounded))
                .multilineTextAlignment(.center)
        }
    }

    // MARK: - Number Display + Steppers

    private var numberDisplay: some View {
        VStack(spacing: Theme.Spacing.xs) {
            Text(String(format: Strings.PartySize.selectedFormat, partySize))
                .font(.system(size: 48, weight: .heavy, design: .rounded))
                .foregroundColor(Theme.Colors.accent)
                .contentTransition(.numericText())
                .animation(.spring(response: 0.3), value: partySize)

            HStack(spacing: Theme.Spacing.md + 2) {
                stepperButton(icon: "minus", enabled: partySize > AppConstants.PartySize.minimum) {
                    partySize = max(AppConstants.PartySize.minimum, partySize - 1)
                }
                stepperButton(icon: "plus", enabled: partySize < AppConstants.PartySize.maximum) {
                    partySize = min(AppConstants.PartySize.maximum, partySize + 1)
                }
            }
        }
    }

    // MARK: - Quick Grid

    private var quickGrid: some View {
        LazyVGrid(columns: [GridItem(.adaptive(minimum: 100), spacing: Theme.Spacing.md)], spacing: Theme.Spacing.md) {
            ForEach(quickOptions, id: \.self) { option in
                quickOptionButton(option: option, isSelected: option == partySize)
            }
        }
    }

    // MARK: - Action Buttons

    private var actionButtons: some View {
        HStack(spacing: Theme.Spacing.lg) {
            Button(action: onCancel) {
                Text(Strings.Common.cancel)
                    .font(.system(size: 20, weight: .semibold))
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, Theme.Spacing.lg)
                    .background(Theme.Colors.secondaryBackground)
                    .foregroundColor(Theme.Colors.textPrimary)
                    .cornerRadius(Theme.Radius.md)
            }
            .buttonStyle(.plain)

            Button(action: onConfirm) {
                Text(Strings.Common.confirm)
                    .font(.system(size: 20, weight: .bold))
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, Theme.Spacing.lg)
                    .background(Theme.Colors.accent)
                    .foregroundColor(Theme.Colors.textOnAccent)
                    .cornerRadius(Theme.Radius.md)
                    .applyShadow(Theme.Shadow.accent)
            }
            .buttonStyle(.plain)
        }
        .padding(.top, Theme.Spacing.xs)
    }

    // MARK: - Helpers

    @ViewBuilder
    private func stepperButton(icon: String, enabled: Bool, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            Image(systemName: icon)
                .font(.system(size: 18, weight: .bold))
                .frame(width: 48, height: 48)
                .background(enabled ? Theme.Colors.secondaryBackground : Color(.tertiarySystemBackground))
                .foregroundColor(enabled ? Theme.Colors.textPrimary : Color(.tertiaryLabel))
                .cornerRadius(Theme.Radius.md)
        }
        .buttonStyle(.plain)
        .disabled(!enabled)
    }

    @ViewBuilder
    private func quickOptionButton(option: Int, isSelected: Bool) -> some View {
        Button {
            withAnimation(.spring(response: 0.3, dampingFraction: 0.7)) { partySize = option }
        } label: {
            Text(String(format: Strings.PartySize.optionFormat, option))
                .font(.system(size: 22, weight: .bold, design: .rounded))
                .lineLimit(1)
                .minimumScaleFactor(0.8)
                .frame(maxWidth: .infinity, minHeight: 68)
                .background(
                    isSelected
                        ? AnyShapeStyle(Theme.Colors.gradientAccent)
                        : AnyShapeStyle(Theme.Colors.secondaryBackground)
                )
                .foregroundColor(isSelected ? Theme.Colors.textOnAccent : Theme.Colors.textPrimary)
                .cornerRadius(Theme.Radius.md)
                .overlay(
                    RoundedRectangle(cornerRadius: Theme.Radius.md)
                        .stroke(isSelected ? Theme.Colors.accentBorder : Color.clear, lineWidth: 2)
                )
                .scaleEffect(isSelected ? 1.03 : 1.0)
        }
        .buttonStyle(.plain)
    }
}
