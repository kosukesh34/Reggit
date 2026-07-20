import Foundation

struct PublicProject: Decodable, Identifiable, Hashable {
    let projectKey: String
    let name: String

    var id: String { projectKey }

    enum CodingKeys: String, CodingKey {
        case projectKey = "project_key"
        case name
    }
}

struct PublicProjectsResponse: Decodable {
    let projects: [PublicProject]
}
