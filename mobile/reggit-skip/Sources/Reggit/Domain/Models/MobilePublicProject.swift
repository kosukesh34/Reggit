struct MobilePublicProject: Decodable, Identifiable, Hashable, Sendable {
    let projectKey: String
    let name: String

    var id: String { projectKey }

    enum CodingKeys: String, CodingKey {
        case projectKey = "project_key"
        case name
    }
}

struct MobilePublicProjectsResponse: Decodable, Sendable {
    let projects: [MobilePublicProject]
}
