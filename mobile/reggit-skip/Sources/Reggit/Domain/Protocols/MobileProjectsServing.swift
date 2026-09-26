protocol MobileProjectsServing: Sendable {
    func fetchProjects(baseURL: String) async throws -> [MobilePublicProject]
}
