import Foundation

/// DB: YYYYMMDD+通番 → 表示: 日+通番（例: 202607193 → 193）
enum DailySequenceNumberFormatter {
    static func formatStoredForDisplay(_ stored: Int) -> String {
        formatStoredForDisplay(String(stored))
    }

    static func formatStoredForDisplay(_ stored: String) -> String {
        let trimmed = stored.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return "" }
        guard let regex = try? NSRegularExpression(pattern: #"^(\d{4})(\d{2})(\d{2})(\d+)$"#) else {
            return trimmed
        }
        let range = NSRange(trimmed.startIndex..<trimmed.endIndex, in: trimmed)
        guard let match = regex.firstMatch(in: trimmed, range: range),
              match.numberOfRanges == 5,
              let dayRange = Range(match.range(at: 3), in: trimmed),
              let seqRange = Range(match.range(at: 4), in: trimmed) else {
            return trimmed
        }
        let day = Int(trimmed[dayRange]) ?? 0
        let seq = String(trimmed[seqRange])
        return "\(day)\(seq)"
    }
}
