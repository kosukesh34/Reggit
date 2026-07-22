import Foundation

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

    func createTicket(partySize: Int) async throws -> QueueTicket {
        let url = try buildURL(path: "/api/queue")
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
        let url = try buildURL(path: "/api/queue/\(id)/cancel")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        _ = try await execute(request)
    }

    func callNext() async throws -> QueueTicket? {
        let url = try buildURL(path: "/api/queue/call-next")
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
        let url = try buildURL(path: "/api/queue/\(id)/recall")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        applyWaitingSystemHeader(&request)

        let data = try await execute(request)
        let apiResponse = try decodeTicketResponse(from: data)
        guard let entry = apiResponse.queueEntry else { throw QueueAPIError.invalidResponse }
        return entry.toTicket()
    }

    func enterTicket(id: Int) async throws -> QueueTicket {
        let url = try buildURL(path: "/api/queue/\(id)/enter")
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        applyWaitingSystemHeader(&request)

        let data = try await execute(request)
        let apiResponse = try decodeTicketResponse(from: data)
        guard let entry = apiResponse.queueEntry else { throw QueueAPIError.invalidResponse }
        return entry.toTicket()
    }

    func fetchTicketStatus(id: Int) async throws -> QueueTicket? {
        let url = try buildURL(path: "/api/queue/\(id)")
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
        let url = try buildURL(path: "/api/queue/me")
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
        let url = try buildURL(path: "/api/queue/list")
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
        guard var components = URLComponents(string: "\(base)\(path)") else {
            throw QueueAPIError.invalidURL
        }
        if !settings.projectKey.isEmpty {
            components.queryItems = [URLQueryItem(name: "project_key", value: settings.projectKey)]
        }
        guard let url = components.url else { throw QueueAPIError.invalidURL }
        return url
    }

    private func execute(_ request: URLRequest) async throws -> Data {
        var request = request
        applyWaitingSystemHeader(&request)
        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw QueueAPIError.invalidResponse }
        try validateStatus(http, data: data)
        return data
    }

    private func applyWaitingSystemHeader(_ request: inout URLRequest) {
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
