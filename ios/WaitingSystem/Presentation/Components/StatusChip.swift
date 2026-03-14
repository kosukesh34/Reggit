import SwiftUI

struct StatusChip: View {
    let title: String
    let value: String

    var body: some View {
        VStack(alignment: .leading, spacing: 5) {
            Text(title)
                .font(.system(size: 11, weight: .bold))
                .foregroundColor(Theme.Colors.textSecondary)
                .textCase(.uppercase)
            Text(value)
                .font(.system(size: 14, weight: .semibold))
                .lineLimit(1)
                .truncationMode(.middle)
        }
        .padding(.vertical, 10)
        .padding(.horizontal, Theme.Spacing.md + 2)
        .background(Theme.Colors.secondaryBackground)
        .cornerRadius(Theme.Radius.md)
        .overlay(
            RoundedRectangle(cornerRadius: Theme.Radius.md)
                .stroke(Theme.Colors.separator.opacity(0.3), lineWidth: 0.5)
        )
    }
}
