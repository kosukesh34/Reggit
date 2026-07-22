import SwiftUI

extension String {
    func toColor() -> Color {
        let hex = trimmingCharacters(in: CharacterSet.alphanumerics.inverted)
        if hex.isEmpty {
            return Color(red: 0.949, green: 0.957, blue: 0.973) // #F2F4F8
        }
        guard hex.count == 6,
              let value = UInt64(hex, radix: 16) else {
            return Color(red: 0.949, green: 0.957, blue: 0.973)
        }
        let r = Double((value & 0xFF0000) >> 16) / 255.0
        let g = Double((value & 0x00FF00) >> 8) / 255.0
        let b = Double(value & 0x0000FF) / 255.0
        return Color(red: r, green: g, blue: b)
    }
}

extension Color {
    func toHexString() -> String {
        let uiColor = UIColor(self)
        var r: CGFloat = 0
        var g: CGFloat = 0
        var b: CGFloat = 0
        uiColor.getRed(&r, green: &g, blue: &b, alpha: nil)
        let ri = Int(r * 255)
        let gi = Int(g * 255)
        let bi = Int(b * 255)
        return String(format: "#%02X%02X%02X", ri, gi, bi)
    }
}
