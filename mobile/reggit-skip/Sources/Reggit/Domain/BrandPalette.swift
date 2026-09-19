import Foundation

/// Theme input is a single optional #RRGGBB value supplied by each store.
/// Neutral surfaces and semantic colors remain independent of store branding.
struct BrandPalette: Equatable {
    let red: Double
    let green: Double
    let blue: Double

    init(hex: String) {
        let trimmed = hex.trimmingCharacters(in: .whitespacesAndNewlines)
        let digits = trimmed.hasPrefix("#") ? String(trimmed.dropFirst()) : trimmed
        let value = digits.count == 6 && digits.allSatisfy(\.isHexDigit) ? UInt64(digits, radix: 16) ?? 0x111111 : 0x111111
        red = Double((value >> 16) & 255) / 255
        green = Double((value >> 8) & 255) / 255
        blue = Double(value & 255) / 255
    }

    var usesDarkForeground: Bool {
        func linear(_ value: Double) -> Double {
            value <= 0.04045 ? value / 12.92 : pow((value + 0.055) / 1.055, 2.4)
        }
        let luminance = 0.2126 * linear(red) + 0.7152 * linear(green) + 0.0722 * linear(blue)
        return luminance > 0.179
    }
}
