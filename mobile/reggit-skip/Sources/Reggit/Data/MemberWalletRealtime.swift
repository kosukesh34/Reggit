import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

/// Laravel Reverb の Pusher プロトコルから本人の wallet.updated だけを受け取る。
@MainActor
final class MemberWalletRealtime {
    private var receiveTask: Task<Void, Never>?
    private var socket: URLSessionWebSocketTask?

    func start(
        websocketURL: String,
        client: ReggitAPIClient,
        memberID: Int,
        onPendingCount: @escaping @MainActor (Int) -> Void
    ) {
        stop()
        guard let url = URL(string: websocketURL), memberID > 0 else { return }
        receiveTask = Task {
            while !Task.isCancelled {
                let connection = URLSession.shared.webSocketTask(with: url)
                socket = connection
                connection.resume()
                do {
                    while !Task.isCancelled {
                        let message = try await connection.receive()
                        let raw: String
                        switch message {
                        case .string(let text): raw = text
                        case .data(let data): raw = String(data: data, encoding: .utf8) ?? ""
                        @unknown default: raw = ""
                        }
                        guard let event = Self.parse(raw) else { continue }
                        switch event {
                        case .connected(let socketID):
                            let auth = try await client.authorizeWalletChannel(socketID: socketID, memberID: memberID)
                            let subscribe = [
                                "event": "pusher:subscribe",
                                "data": ["auth": auth, "channel": "private-liff.member.\(memberID)"],
                            ] as [String: Any]
                            let data = try JSONSerialization.data(withJSONObject: subscribe)
                            try await connection.send(.string(String(decoding: data, as: UTF8.self)))
                        case .walletUpdated(let count):
                            onPendingCount(count)
                        }
                    }
                } catch {
                    logger.warning("Wallet Reverb connection ended: \(error.localizedDescription)")
                }
                connection.cancel(with: .goingAway, reason: nil)
                socket = nil
                if !Task.isCancelled {
                    try? await Task.sleep(nanoseconds: 3_000_000_000)
                }
            }
        }
    }

    func stop() {
        receiveTask?.cancel()
        receiveTask = nil
        socket?.cancel(with: .goingAway, reason: nil)
        socket = nil
    }

    enum Event: Equatable {
        case connected(String)
        case walletUpdated(Int)
    }

    nonisolated static func parse(_ raw: String) -> Event? {
        guard let bytes = raw.data(using: .utf8),
              let envelope = try? JSONSerialization.jsonObject(with: bytes) as? [String: Any],
              let name = envelope["event"] as? String else { return nil }
        let payload: [String: Any]
        if let object = envelope["data"] as? [String: Any] {
            payload = object
        } else if let nested = envelope["data"] as? String,
                  let data = nested.data(using: .utf8),
                  let object = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
            payload = object
        } else {
            return nil
        }
        if name == "pusher:connection_established", let socketID = payload["socket_id"] as? String {
            return .connected(socketID)
        }
        if name == "wallet.updated", let count = payload["pending_lottery_count"] as? Int {
            return .walletUpdated(count)
        }
        return nil
    }
}
