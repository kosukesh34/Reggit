import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

struct MobileProjectsAPIClient: MobileProjectsServing {
    func fetchProjects(baseURL: String) async throws -> [MobilePublicProject] {
        let trimmed = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        guard let url = URL(string: "\(trimmed)/api/v1/projects") else {
            throw URLError(.badURL)
        }
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            throw URLError(.badServerResponse)
        }
        return try JSONDecoder().decode(MobilePublicProjectsResponse.self, from: data).projects
    }
}
