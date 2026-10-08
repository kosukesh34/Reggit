import Foundation
import Security

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
        request.timeoutInterval = 5
        request.httpBody = body
        _ = try await execute(request)
    }

    func unregisterDevice(pushToken: String) async throws {
        var request = try makeRequest(path: "/devices", method: "DELETE")
        request.timeoutInterval = 5
        request.httpBody = try JSONSerialization.data(withJSONObject: ["push_token": pushToken])
        _ = try await execute(request)
    }

    func login(enrollmentToken: String) async throws -> StoreAppLoginResponse {
        let base = AppBuildConfig.baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        guard let url = URL(string: "\(base)/api/store-app/login") else { throw KitchenAPIError.invalidURL }
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: [
            "enrollment_token": enrollmentToken,
            "app": "kitchen",
            "device_name": settingsStore.load().deviceName,
            "device_key": StoreAppCredentialStore.deviceKey(app: "kitchen"),
        ])
        let data = try await execute(request)
        let response = try JSONDecoder().decode(StoreAppLoginResponse.self, from: data)
        StoreAppCredentialStore.save(response.token, app: "kitchen")
        var settings = settingsStore.load()
        settings.projectKey = response.project.key
        settingsStore.save(settings)
        return response
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
        request.setValue("Bearer \(StoreAppCredentialStore.token(app: "kitchen") ?? "")", forHTTPHeaderField: "Authorization")
        request.setValue(StoreAppCredentialStore.deviceKey(app: "kitchen"), forHTTPHeaderField: "X-Device-Key")
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

struct StoreAppLoginResponse: Decodable {
    struct Project: Decodable { let id: Int; let key: String; let name: String }
    let token: String
    let project: Project
}

enum StoreAppCredentialStore {
    static func deviceKey(app: String) -> String {
        let service = "jp.reggit.store-app.\(app).device-key"
        if let value = read(service: service) { return value }
        let value = UUID().uuidString + UUID().uuidString
        saveValue(value, service: service)
        return value
    }

    static func token(app: String) -> String? {
        read(service: "jp.reggit.store-app.\(app)")
    }

    private static func read(service: String) -> String? {
        let query: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrService as String: service,
                                    kSecReturnData as String: true, kSecMatchLimit as String: kSecMatchLimitOne]
        var item: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary, &item) == errSecSuccess,
              let data = item as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func save(_ token: String, app: String) {
        clear(app: app)
        saveValue(token, service: "jp.reggit.store-app.\(app)")
    }

    private static func saveValue(_ value: String, service: String) {
        let query: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrService as String: service,
                                    kSecValueData as String: Data(value.utf8),
                                    kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly]
        SecItemAdd(query as CFDictionary, nil)
    }

    static func clear(app: String) {
        SecItemDelete([kSecClass as String: kSecClassGenericPassword,
                       kSecAttrService as String: "jp.reggit.store-app.\(app)"] as CFDictionary)
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
