import Foundation

struct WaitingAppRemoteConfig: Codable, Equatable {
    var projectName: String
    var lineLiffId: String
    var lineLiffUrl: String
    var lineFriendAddUrl: String

    static let empty = WaitingAppRemoteConfig(
        projectName: "",
        lineLiffId: "",
        lineLiffUrl: "",
        lineFriendAddUrl: ""
    )
}

struct WaitingAppConfigClient {
    let baseURL: String
    let projectKey: String

    func fetch() async -> WaitingAppRemoteConfig? {
        let trimmedBase = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        let encodedKey = projectKey.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? projectKey
        guard let url = URL(string: "\(trimmedBase)/api/v1/app-config?project_key=\(encodedKey)") else {
            return nil
        }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.timeoutInterval = 10

        do {
            let (data, response) = try await URLSession.shared.data(for: request)
            guard let http = response as? HTTPURLResponse, http.statusCode == 200 else {
                return nil
            }
            return Self.parse(data)
        } catch {
            return nil
        }
    }

    static func parse(_ data: Data) -> WaitingAppRemoteConfig? {
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            return nil
        }
        let line = json["line"] as? [String: Any] ?? [:]
        return WaitingAppRemoteConfig(
            projectName: (json["project_name"] as? String) ?? "",
            lineLiffId: (line["liff_id"] as? String) ?? "",
            lineLiffUrl: (line["liff_url"] as? String) ?? "",
            lineFriendAddUrl: (line["friend_add_url"] as? String) ?? ""
        )
    }
}
