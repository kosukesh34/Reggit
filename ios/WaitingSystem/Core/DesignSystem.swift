import SwiftUI

// MARK: - Design Tokens

enum Theme {

    // MARK: Colors

    enum Colors {
        static let accent = Color(red: 0.09, green: 0.37, blue: 0.82)
        static let accentLight = Color(red: 0.22, green: 0.50, blue: 0.95)
        static let accentSubtle = accent.opacity(0.10)
        static let accentBorder = accent.opacity(0.20)

        static let success = Color.green
        static let successSubtle = Color.green.opacity(0.12)
        static let danger = Color.red
        static let dangerSubtle = Color.red.opacity(0.10)
        static let dangerBorder = Color.red.opacity(0.30)
        static let warning = Color.orange
        static let warningSubtle = Color.orange.opacity(0.12)

        static let textPrimary = Color.primary
        static let textSecondary = Color.secondary
        static let textOnAccent = Color.white
        static let separator = Color(.separator)
        static let background = Color(.systemBackground)
        static let secondaryBackground = Color(.secondarySystemBackground)
        static let groupedBackground = Color(.systemGroupedBackground)

        static let gradientAccent = LinearGradient(
            colors: [accent, accentLight],
            startPoint: .leading,
            endPoint: .trailing
        )
    }

    // MARK: Spacing

    enum Spacing {
        static let xxs: CGFloat = 2
        static let xs: CGFloat = 4
        static let sm: CGFloat = 8
        static let md: CGFloat = 12
        static let lg: CGFloat = 16
        static let xl: CGFloat = 20
        static let xxl: CGFloat = 24
        static let xxxl: CGFloat = 32
    }

    // MARK: Radius

    enum Radius {
        static let sm: CGFloat = 8
        static let md: CGFloat = 12
        static let lg: CGFloat = 16
        static let xl: CGFloat = 20
        static let xxl: CGFloat = 24
    }

    // MARK: Shadow

    enum Shadow {
        static let sm = ShadowStyle(color: .black.opacity(0.06), radius: 6, y: 3)
        static let md = ShadowStyle(color: .black.opacity(0.08), radius: 10, y: 4)
        static let lg = ShadowStyle(color: .black.opacity(0.12), radius: 16, y: 8)
        static let accent = ShadowStyle(color: Colors.accent.opacity(0.35), radius: 12, y: 6)
    }
}

struct ShadowStyle {
    let color: Color
    let radius: CGFloat
    let y: CGFloat
}

// MARK: - View Modifier Helpers

extension View {
    func cardStyle(padding: CGFloat = Theme.Spacing.xxl) -> some View {
        self
            .padding(padding)
            .background(Theme.Colors.background)
            .cornerRadius(Theme.Radius.xl)
            .shadow(color: Theme.Shadow.md.color, radius: Theme.Shadow.md.radius, x: 0, y: Theme.Shadow.md.y)
    }

    func applyShadow(_ style: ShadowStyle) -> some View {
        self.shadow(color: style.color, radius: style.radius, x: 0, y: style.y)
    }
}
