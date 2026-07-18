import Foundation

struct PageInspection {
    let visible: Bool
    let summary: String
}

enum JavaScriptEvaluateResult {
    static func normalize(_ value: Any) -> String {
        if let stringValue = value as? String {
            return decodeJavaScriptStringResult(stringValue)
        }
        // Skip Android では JSON オブジェクトが Dictionary / NSDictionary で返ることがある。
        if JSONSerialization.isValidJSONObject(value),
           let data = try? JSONSerialization.data(withJSONObject: value),
           let encoded = String(data: data, encoding: .utf8) {
            return encoded
        }
        return decodeJavaScriptStringResult(String(describing: value))
    }

    static func parsePageVisibility(from raw: Any) -> PageInspection {
        let summary = normalize(raw)
        let fields = summary.split(separator: "|", omittingEmptySubsequences: false).map(String.init)
        let visible = fields.last == "1"
        return PageInspection(visible: visible, summary: summary)
    }

    /// WebView の evaluateJavascript は JSON エンコードされた文字列リテラルを返すことがある。
    private static func decodeJavaScriptStringResult(_ s: String) -> String {
        let trimmed = s.trimmingCharacters(in: .whitespacesAndNewlines)

        if isValidJSONObjectOrArray(trimmed) {
            return trimmed
        }

        // "\"{...}\"" のような JSON 文字列リテラル
        if let data = trimmed.data(using: .utf8),
           let decoded = try? JSONSerialization.jsonObject(with: data) as? String {
            return decoded
        }

        let unquoted = trimOuterQuotes(trimmed)
        if isValidJSONObjectOrArray(unquoted) {
            return unquoted
        }

        // {\"key\":\"value\"} のようにバックスラッシュエスケープが残っている場合
        if unquoted.contains("\\\"") {
            let wrapped = "\"" + unquoted + "\""
            if let data = wrapped.data(using: .utf8),
               let decoded = try? JSONSerialization.jsonObject(with: data) as? String {
                return decoded
            }
        }

        return unquoted
    }

    private static func isValidJSONObjectOrArray(_ s: String) -> Bool {
        guard s.hasPrefix("{") || s.hasPrefix("[") else { return false }
        guard let data = s.data(using: .utf8) else { return false }
        return (try? JSONSerialization.jsonObject(with: data)) != nil
    }

    private static func trimOuterQuotes(_ s: String) -> String {
        guard s.count >= 2 else { return s }
        if s.hasPrefix("\""), s.hasSuffix("\"") {
            return String(s.dropFirst().dropLast())
        }
        return s
    }
}
