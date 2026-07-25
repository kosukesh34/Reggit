import Foundation

final class KitchenAPIClient {
    private let session: URLSession
    private let settingsStore: KitchenSettingsStore

    init(session: URLSession = .shared, settingsStore: KitchenSettingsStore = KitchenSettingsStore()) {
        self.session = session
        self.settingsStore = settingsStore
    }

    func fetchOrders() async throws -> [KitchenOrder] {
        let request = try makeRequest(path: "/orders", method: "GET")
        let data = try await execute(request)
        let decoded = try JSONDecoder().decode(OrdersResponse.self, from: data)
        return decoded.orders.map { $0.toDomain() }
    }

    func updateStatus(orderId: Int, status: String) async throws -> KitchenOrder {
        let body = try JSONSerialization.data(withJSONObject: ["status": status])
        var request = try makeRequest(path: "/orders/\(orderId)/status", method: "POST")
        request.httpBody = body
        let data = try await execute(request)
        let decoded = try JSONDecoder().decode(OrderResponse.self, from: data)
        return decoded.order.toDomain()
    }

    func registerDevice(pushToken: String, deviceName: String) async throws {
        let body = try JSONSerialization.data(withJSONObject: [
            "push_token": pushToken,
            "platform": "ios",
            "device_name": deviceName,
        ])
        var request = try makeRequest(path: "/devices", method: "POST")
        request.httpBody = body
        _ = try await execute(request)
    }

    private func makeRequest(path: String, method: String) throws -> URLRequest {
        let settings = settingsStore.load()
        let base = settings.baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        let projectKey = settings.projectKey.trimmingCharacters(in: .whitespacesAndNewlines)
        guard let url = URL(string: "\(base)/api/kitchen/\(projectKey)\(path)") else {
            throw KitchenAPIError.invalidURL
        }
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        // WaitingSystem の X-Queue-Client と同様、クライアント識別のみ
        request.setValue("kitchen-monitor", forHTTPHeaderField: "X-Kitchen-Client")
        return request
    }

    private func execute(_ request: URLRequest) async throws -> Data {
        let (data, response) = try await session.data(for: request)
        guard let http = response as? HTTPURLResponse else {
            throw KitchenAPIError.invalidResponse
        }
        guard (200..<300).contains(http.statusCode) else {
            let message = (try? JSONDecoder().decode(ErrorResponse.self, from: data))?.error
                ?? "HTTP \(http.statusCode)"
            throw KitchenAPIError.server(message)
        }
        return data
    }
}

enum KitchenAPIError: Error, LocalizedError {
    case invalidURL
    case invalidResponse
    case server(String)

    var errorDescription: String? {
        switch self {
        case .invalidURL: return "URLが不正です"
        case .invalidResponse: return "応答を解析できませんでした"
        case .server(let msg): return msg
        }
    }
}

private struct ErrorResponse: Decodable {
    let error: String?
}

private struct OrdersResponse: Decodable {
    let orders: [APIOrder]
}

private struct OrderResponse: Decodable {
    let order: APIOrder
}

private struct APIOrder: Decodable {
    let id: Int
    let order_number: String
    let status: String
    let order_type: String?
    let notes: String?
    let created_at: String?
    let items: [APIItem]

    func toDomain() -> KitchenOrder {
        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        var createdAt: Date?
        if let raw = created_at {
            createdAt = formatter.date(from: raw) ?? ISO8601DateFormatter().date(from: raw)
        }
        return KitchenOrder(
            id: id,
            orderNumber: order_number,
            status: status,
            orderType: order_type,
            notes: notes,
            createdAt: createdAt,
            items: items.map { KitchenOrderItem(name: $0.name, quantity: $0.quantity, notes: $0.notes) }
        )
    }
}

private struct APIItem: Decodable {
    let name: String
    let quantity: Int
    let notes: String?
}
