import Foundation

/// ネイティブアプリ識別子。Skip.env / Laravel `config/native_app.php` と一致させる。
public enum NativeAppIdentity {
    public static let userAgentToken = "Reggit"
    public static let urlScheme = "reggit"
    public static let userAgentVersion = "1.0"

    public static var userAgentAppToken: String {
        "\(userAgentToken)/\(userAgentVersion)"
    }

    public static func customURL(_ host: String, query: String) -> String {
        "\(urlScheme)://\(host)?\(query)"
    }
}
