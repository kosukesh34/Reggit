import Foundation

enum ProjectsAPIError: Error, LocalizedError {
    case invalidURL
    case invalidResponse
    case server(String)

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return Strings.Error.invalidURL
        case .invalidResponse:
            return Strings.Error.invalidResponse
        case .server(let message):
            return message
        }
    }
}

final class ProjectsAPIClient {
    private let session: URLSession
    private let settingsStore: SettingsStoreProtocol

    init(session: URLSession = .shared, settingsStore: SettingsStoreProtocol = SettingsStore()) {
        self.session = session
        self.settingsStore = settingsStore
    }

    func fetchProjects(baseURLOverride: String? = nil) async throws -> [PublicProject] {
        let settings = settingsStore.load()
        let base = normalizeBaseURL(baseURLOverride ?? settings.baseURL)
        guard let url = URL(string: "\(base)/api/v1/projects") else {
            throw ProjectsAPIError.invalidURL
        }
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw ProjectsAPIError.invalidResponse
        }
        guard (200..<300).contains(http.statusCode) else {
            let message = String(data: data, encoding: .utf8) ?? Strings.Error.serverError
            throw ProjectsAPIError.server(message)
        }

        return try JSONDecoder().decode(PublicProjectsResponse.self, from: data).projects
    }

    private func normalizeBaseURL(_ raw: String) -> String {
        raw.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
    }
}
