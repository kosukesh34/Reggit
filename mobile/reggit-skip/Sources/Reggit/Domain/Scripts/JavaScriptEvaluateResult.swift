import Foundation

struct PageInspection {
    let visible: Bool
    let summary: String
}

enum JavaScriptEvaluateResult {
    static func normalize(_ value: Any) -> String {
        if let stringValue = value as? String {
            return trimOuterQuotes(stringValue)
        }
        return trimOuterQuotes(String(describing: value))
    }

    static func parsePageVisibility(from raw: Any) -> PageInspection {
        let summary = normalize(raw)
        let fields = summary.split(separator: "|", omittingEmptySubsequences: false).map(String.init)
        let visible = fields.last == "1"
        return PageInspection(visible: visible, summary: summary)
    }

    private static func trimOuterQuotes(_ s: String) -> String {
        guard s.count >= 2 else { return s }
        if s.hasPrefix("\""), s.hasSuffix("\"") {
            return String(s.dropFirst().dropLast())
        }
        return s
    }
}
