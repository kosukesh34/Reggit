import Foundation

enum MediaURL {
    /// API が返す相対パス（`/images/...`）を baseURL 付きの絶対 URL にする。
    static func resolve(_ raw: String?, baseURL: String) -> URL? {
        guard let raw = raw?.trimmedNonEmpty else { return nil }
        if raw.hasPrefix("http://") || raw.hasPrefix("https://") {
            guard let absolute = URL(string: raw) else { return nil }
            // Laravel may generate media URLs with APP_URL=localhost. That works in the
            // iOS simulator, but localhost points at the emulator itself on Android.
            // Keep real CDN hosts intact and only rebase loopback media onto the API host.
            if isLoopbackHost(absolute.host),
               let base = URL(string: baseURL),
               !isLoopbackHost(base.host) || base.host != absolute.host {
                var components = URLComponents(url: absolute, resolvingAgainstBaseURL: false)
                components?.scheme = base.scheme
                components?.host = base.host
                components?.port = base.port
                return components?.url
            }
            return absolute
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

    private static func isLoopbackHost(_ host: String?) -> Bool {
        guard let host else { return false }
        return host == "localhost" || host == "127.0.0.1" || host == "::1"
    }
}
