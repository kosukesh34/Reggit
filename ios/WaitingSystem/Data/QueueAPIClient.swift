import Foundation
import Security

enum QueueAPIError: Error, LocalizedError {
    case invalidURL
    case invalidResponse
    case server(String)

    var errorDescription: String? {
        switch self {
        case .invalidURL:       return Strings.Error.invalidURL
        case .invalidResponse:  return Strings.Error.invalidResponse
        case .server(let msg):  return msg
        }
    }
}

final class QueueAPIClient: QueueAPIClientProtocol {
    private let session: URLSession
    private let settingsStore: SettingsStoreProtocol

    init(session: URLSession = .shared, settingsStore: SettingsStoreProtocol = SettingsStore()) {
        self.session = session
        self.settingsStore = settingsStore
    }

    // MARK: - Public API

    func login(enrollmentToken: String) async throws -> WaitingLoginResponse {
        let base = normalizeBaseURL(AppBuildConfig.baseURL)
        guard let url = URL(string: "\(base)/api/store-app/login") else { throw QueueAPIError.invalidURL }
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: [
            "enrollment_token": enrollmentToken,
            "app": "waiting",
            "device_name": "Waiting iPad",
            "device_key": WaitingCredentialStore.deviceKey(),
        ])
        let data = try await execute(request, authenticated: false)
        let response = try JSONDecoder().decode(WaitingLoginResponse.self, from: data)
        WaitingCredentialStore.save(response.token)
        var settings = settingsStore.load()
        settings.projectKey = response.project.key
        settingsStore.save(settings)
        return response
    }

    func createTicket(partySize: Int) async throws -> QueueTicket {
        let url = try buildURL(path: "/queue")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: ["party_size": partySize])

        let data = try await execute(request)
        let apiResponse = try decodeTicketResponse(from: data)
        guard let entry = apiResponse.queueEntry else { throw QueueAPIError.invalidResponse }
        return entry.toTicket()
    }

    func cancelTicket(id: Int) async throws {
        let url = try buildURL(path: "/queue/\(id)/cancel")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        _ = try await execute(request)
    }

    func callNext() async throws -> QueueTicket? {
        let url = try buildURL(path: "/queue/call-next")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        applyWaitingSystemHeader(&request)

        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw QueueAPIError.invalidResponse }
        if http.statusCode == 204 { return nil }
        try validateStatus(http, data: data)

        let apiResponse = try decodeTicketResponse(from: data)
        return apiResponse.queueEntry?.toTicket()
    }

    func recallTicket(id: Int) async throws -> QueueTicket {
        let url = try buildURL(path: "/queue/\(id)/recall")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        applyWaitingSystemHeader(&request)

        let data = try await execute(request)
        let apiResponse = try decodeTicketResponse(from: data)
        guard let entry = apiResponse.queueEntry else { throw QueueAPIError.invalidResponse }
        return entry.toTicket()
    }

    func callTicket(id: Int) async throws -> QueueTicket {
        let url = try buildURL(path: "/queue/\(id)/call")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        let data = try await execute(request)
        let apiResponse = try decodeTicketResponse(from: data)
        guard let entry = apiResponse.queueEntry else { throw QueueAPIError.invalidResponse }
        return entry.toTicket()
    }

    func enterTicket(id: Int) async throws -> QueueTicket {
        let url = try buildURL(path: "/queue/\(id)/enter")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        applyWaitingSystemHeader(&request)

        let data = try await execute(request)
        let apiResponse = try decodeTicketResponse(from: data)
        guard let entry = apiResponse.queueEntry else { throw QueueAPIError.invalidResponse }
        return entry.toTicket()
    }

    func fetchTicketStatus(id: Int) async throws -> QueueTicket? {
        let url = try buildURL(path: "/queue/\(id)")
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        applyWaitingSystemHeader(&request)

        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw QueueAPIError.invalidResponse }
        if http.statusCode == 404 { return nil }
        try validateStatus(http, data: data)

        let apiResponse = try decodeTicketResponse(from: data)
        return apiResponse.queueEntry?.toTicket()
    }

    func fetchQueueOverview() async throws -> QueueOverview {
        let url = try buildURL(path: "/queue/me")
        var request = URLRequest(url: url)
        request.httpMethod = "GET"

        let data = try await execute(request)
        let decoder = makeDecoder(dateStrategy: true)
        let payload = try decoder.decode(QueueMePayload.self, from: data)

        let wc: Int
        let ewm: Int
        if let entry = payload.queueEntry {
            wc = entry.waitingCount ?? payload.waitingCount ?? 0
            ewm = entry.estimatedWaitMinutes ?? payload.estimatedWaitMinutes ?? 0
        } else {
            wc = payload.waitingCount ?? 0
            ewm = payload.estimatedWaitMinutes ?? 0
        }
        return QueueOverview(waitingCount: max(0, wc), estimatedWaitMinutes: max(0, ewm))
    }

    func fetchQueueList() async throws -> [QueueListEntry] {
        let url = try buildURL(path: "/queue/list")
        var request = URLRequest(url: url)
        request.httpMethod = "GET"

        let data = try await execute(request)
        let decoder = makeDecoder(dateStrategy: false)
        return try decoder.decode(QueueListResponse.self, from: data).entries
    }

    // MARK: - Request Infrastructure

    private func buildURL(path: String) throws -> URL {
        let settings = settingsStore.load()
        let base = normalizeBaseURL(settings.baseURL)
        guard let components = URLComponents(string: "\(base)/api/store-app/waiting/\(settings.projectKey)\(path)") else {
            throw QueueAPIError.invalidURL
        }
        guard let url = components.url else { throw QueueAPIError.invalidURL }
        return url
    }

    private func execute(_ request: URLRequest, authenticated: Bool = true) async throws -> Data {
        var request = request
        if authenticated { applyWaitingSystemHeader(&request) }
        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw QueueAPIError.invalidResponse }
        try validateStatus(http, data: data)
        return data
    }

    private func applyWaitingSystemHeader(_ request: inout URLRequest) {
        request.setValue("Bearer \(WaitingCredentialStore.token() ?? "")", forHTTPHeaderField: "Authorization")
        request.setValue(WaitingCredentialStore.deviceKey(), forHTTPHeaderField: "X-Device-Key")
        request.setValue("waiting-system", forHTTPHeaderField: "X-Queue-Client")
    }

    private func validateStatus(_ http: HTTPURLResponse, data: Data) throws {
        guard (200..<300).contains(http.statusCode) else {
            let message = String(data: data, encoding: .utf8) ?? Strings.Error.serverError
            throw QueueAPIError.server(message)
        }
    }

    private func decodeTicketResponse(from data: Data) throws -> QueueTicketResponse {
        let decoder = makeDecoder(dateStrategy: true)
        do {
            return try decoder.decode(QueueTicketResponse.self, from: data)
        } catch {
            let body = String(data: data, encoding: .utf8) ?? ""
            let suffix = body.isEmpty ? "" : " レスポンス: \(body)"
            throw QueueAPIError.server(String(format: Strings.Error.parseFailedFormat, suffix))
        }
    }

    private func makeDecoder(dateStrategy: Bool) -> JSONDecoder {
        let decoder = JSONDecoder()
        decoder.keyDecodingStrategy = .convertFromSnakeCase
        if dateStrategy { decoder.dateDecodingStrategy = .iso8601 }
        return decoder
    }

    private func normalizeBaseURL(_ raw: String) -> String {
        if raw.hasPrefix("http://") || raw.hasPrefix("https://") { return raw }
        return "http://\(raw)"
    }
}

struct WaitingLoginResponse: Decodable {
    struct Project: Decodable { let id: Int; let key: String; let name: String }
    let token: String
    let project: Project
}

enum WaitingCredentialStore {
    private static let service = "jp.reggit.store-app.waiting"

    static func token() -> String? {
        read(service: service)
    }

    static func deviceKey() -> String {
        let keyService = service + ".device-key"
        if let value = read(service: keyService) { return value }
        let value = UUID().uuidString + UUID().uuidString
        saveValue(value, service: keyService)
        return value
    }

    private static func read(service: String) -> String? {
        let query: [String: Any] = [kSecClass as String: kSecClassGenericPassword,
                                    kSecAttrService as String: service,
                                    kSecReturnData as String: true,
                                    kSecMatchLimit as String: kSecMatchLimitOne]
        var item: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary, &item) == errSecSuccess,
              let data = item as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func save(_ token: String) {
        clear()
        saveValue(token, service: service)
    }

    private static func saveValue(_ value: String, service: String) {
        SecItemAdd([kSecClass as String: kSecClassGenericPassword,
                    kSecAttrService as String: service,
                    kSecValueData as String: Data(value.utf8),
                    kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly] as CFDictionary, nil)
    }

    static func clear() {
        SecItemDelete([kSecClass as String: kSecClassGenericPassword,
                       kSecAttrService as String: service] as CFDictionary)
    }
}

// MARK: - Response Helpers

struct QueueOverview: Equatable {
    let waitingCount: Int
    let estimatedWaitMinutes: Int
}

private struct QueueMePayload: Codable {
    let queueEntry: QueueTicketResponse.QueueEntryPayload?
    let waitingCount: Int?
    let estimatedWaitMinutes: Int?
}

private extension QueueTicketResponse.QueueEntryPayload {
    func toTicket() -> QueueTicket {
        let channel = QueueLinkChannel.resolve(raw: linkChannel, lineLinked: lineLinked)
        return QueueTicket(
            id: id,
            queueNumber: queueNumber,
            formattedQueueNumber: formattedQueueNumber,
            partySize: partySize,
            status: status,
            lineLinked: channel.isLinked,
            linkChannel: channel,
            qrToken: qrToken,
            createdAt: createdAt,
            updatedAt: updatedAt,
            waitingCount: waitingCount ?? 0,
            aheadCount: aheadCount,
            estimatedWaitMinutes: estimatedWaitMinutes ?? 0
        )
    }
}
