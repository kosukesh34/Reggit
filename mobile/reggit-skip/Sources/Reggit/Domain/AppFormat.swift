import Foundation

enum AppFormat {
    static func yen(_ amount: Int) -> String {
        "¥\(amount.formatted())"
    }

    static func greeting(at date: Date = Date()) -> String {
        let hour = Calendar.current.component(.hour, from: date)
        switch hour {
        case 5 ..< 11: return "おはようございます"
        case 11 ..< 18: return "こんにちは"
        default: return "こんばんは"
        }
    }

    static func apiDate(_ value: String) -> Date? {
        let raw = value.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !raw.isEmpty else { return nil }
        let formats = [
            "yyyy-MM-dd'T'HH:mm:ss.SSSSSSXXXXX",
            "yyyy-MM-dd'T'HH:mm:ss.SSSXXXXX",
            "yyyy-MM-dd'T'HH:mm:ssXXXXX",
            "yyyy-MM-dd HH:mm:ss",
            "yyyy-MM-dd'T'HH:mm:ss",
            "yyyy-MM-dd",
        ]
        for format in formats {
            let formatter = DateFormatter()
            formatter.locale = Locale(identifier: "en_US_POSIX")
            formatter.calendar = Calendar(identifier: .gregorian)
            formatter.timeZone = TimeZone(identifier: "Asia/Tokyo")
            formatter.dateFormat = format
            if let date = formatter.date(from: raw) { return date }
        }
        return nil
    }

    static func dateTime(_ value: String) -> String {
        guard let date = apiDate(value) else { return cleanedAPIDate(value) }
        return format(date, pattern: "yyyy/MM/dd HH:mm")
    }

    static func date(_ value: String) -> String {
        guard let date = apiDate(value) else { return cleanedAPIDate(value) }
        return format(date, pattern: "yyyy/MM/dd")
    }

    private static func format(_ date: Date, pattern: String) -> String {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "ja_JP")
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.timeZone = TimeZone(identifier: "Asia/Tokyo")
        formatter.dateFormat = pattern
        return formatter.string(from: date)
    }

    private static func cleanedAPIDate(_ value: String) -> String {
        let normalized = value.replacingOccurrences(of: "T", with: " ")
        return String(normalized.prefix(16)).replacingOccurrences(of: "-", with: "/")
    }
}

enum AppClock {
    static var now: Date { Date() }
}
