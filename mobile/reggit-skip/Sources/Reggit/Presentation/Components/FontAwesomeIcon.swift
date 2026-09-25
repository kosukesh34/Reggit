import SwiftUI

#if !os(Android)
import CoreText
#endif

enum FontAwesomeFont {
    static let postScriptName = "FontAwesome6Free-Solid"
    static let androidResourceName = "fontawesome6free_solid"

    static func registerIfNeeded() {
        #if !os(Android)
        guard let url = Bundle.module.url(forResource: androidResourceName, withExtension: "ttf") else { return }
        CTFontManagerRegisterFontsForURL(url as CFURL, .process, nil)
        #endif
    }

    static var fontName: String {
        #if SKIP
        androidResourceName
        #else
        postScriptName
        #endif
    }
}

struct FontAwesomeIcon: View {
    var systemName: String
    var size: CGFloat = 18

    var body: some View {
        Text(Self.glyph(for: systemName))
            .font(.custom(FontAwesomeFont.fontName, size: size))
    }

    static func glyph(for systemName: String) -> String {
        let key = systemName
            .replacingOccurrences(of: "fa-solid ", with: "")
            .replacingOccurrences(of: "fas ", with: "")
            .replacingOccurrences(of: "fa-", with: "")
            .trimmingCharacters(in: .whitespacesAndNewlines)
        let glyphs: [String: String] = [
            "house": "\u{f015}",
            "house.fill": "\u{f015}",
            "utensils": "\u{f2e7}",
            "receipt": "\u{f543}",
            "stamp": "\u{f5bf}",
            "ticket": "\u{f145}",
            "ticket-alt": "\u{f145}",
            "ticket.fill": "\u{f145}",
            "dice": "\u{f522}",
            "dice.fill": "\u{f522}",
            "list-ol": "\u{f0cb}",
            "calendar-check": "\u{f274}",
            "id-card": "\u{f2c2}",
            "user": "\u{f007}",
            "images": "\u{f302}",
            "bullhorn": "\u{f0a1}",
            "file-contract": "\u{f56c}",
            "user-shield": "\u{f505}",
            "wallet": "\u{f555}",
            "person.crop.circle.fill": "\u{f2c2}",
            "person.crop.circle": "\u{f007}",
            "person.3.fill": "\u{f0c0}",
            "bag": "\u{f290}",
            "bag.fill": "\u{f290}",
            "cart.fill": "\u{f07a}",
            "fork.knife": "\u{f2e7}",
            "fork.knife.circle": "\u{f2e7}",
            "list.clipboard": "\u{f46d}",
            "list.clipboard.fill": "\u{f46d}",
            "star.circle.fill": "\u{f005}",
            "star": "\u{f005}",
            "star.fill": "\u{f005}",
            "number.circle.fill": "\u{f292}",
            "calendar": "\u{f133}",
            "photo.on.rectangle": "\u{f302}",
            "photo.on.rectangle.angled": "\u{f302}",
            "bell": "\u{f0f3}",
            "bell.fill": "\u{f0f3}",
            "megaphone.fill": "\u{f0a1}",
            "doc.text.fill": "\u{f15c}",
            "hand.raised.fill": "\u{f256}",
            "circle.fill": "\u{f111}",
            "line.3.horizontal": "\u{f0c9}",
            "xmark": "\u{f00d}",
            "xmark.circle": "\u{f057}",
            "xmark.circle.fill": "\u{f057}",
            "chevron.right": "\u{f054}",
            "chevron.left": "\u{f053}",
            "link": "\u{f0c1}",
            "arrow.left.arrow.right": "\u{f362}",
            "info.circle": "\u{f05a}",
            "magnifyingglass": "\u{f002}",
            "photo": "\u{f03e}",
            "minus": "\u{f068}",
            "plus": "\u{f067}",
            "minus.circle.fill": "\u{f056}",
            "plus.circle.fill": "\u{f055}",
            "checkmark": "\u{f00c}",
            "checkmark.circle.fill": "\u{f058}",
            "seal": "\u{f0a3}",
            "person.2": "\u{f500}",
            "clock.arrow.circlepath": "\u{f1da}",
            "safari": "\u{f14e}",
            "hand.tap.fill": "\u{f25a}",
            "play.fill": "\u{f04b}",
            "sparkles": "\u{e2ca}",
            "hand.draw.fill": "\u{f256}",
            "lock.fill": "\u{f023}",
            "triangle.fill": "\u{f0d8}",
            "gift.fill": "\u{f06b}",
            "questionmark": "\u{f128}",
            "creditcard": "\u{f09d}",
            "banknote": "\u{f0d6}",
            "circle": "\u{f111}",
            "wifi.exclamationmark": "\u{f071}",
            "circle.hexagongrid.fill": "\u{f1b3}",
            "circle.circle.fill": "\u{f192}",
        ]
        return glyphs[key] ?? glyphs[systemName] ?? "\u{f111}"
    }
}
