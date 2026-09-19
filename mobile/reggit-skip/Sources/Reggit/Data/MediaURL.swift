import Foundation

enum MediaURL {
    /// API が返す相対パス（`/images/...`）を baseURL 付きの絶対 URL にする。
    static func resolve(_ raw: String?, baseURL: String) -> URL? {
        guard let raw = raw?.trimmedNonEmpty else { return nil }
        if raw.hasPrefix("http://") || raw.hasPrefix("https://") {
            return URL(string: raw)
        }
        let base = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        guard !base.isEmpty else { return nil }
        if raw.hasPrefix("//") {
            return URL(string: "https:\(raw)")
        }
        if raw.hasPrefix("/") {
            return URL(string: base + raw)
        }
        return URL(string: "\(base)/\(raw)")
    }
}
