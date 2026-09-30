import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

/// Laravel Reverb の Pusher プロトコルから会員・店舗の更新通知を受け取る。
@MainActor
final class MemberWalletRealtime {
    private var receiveTask: Task<Void, Never>?
    private var socket: URLSessionWebSocketTask?
    private var heartbeatTask: Task<Void, Never>?
    var isRunning: Bool { receiveTask != nil }

    func start(
        websocketURL: String,
        client: ReggitAPIClient?,
        memberID: Int?,
        projectKey: String,
        onWalletUpdated: @escaping @MainActor (Int?, Int?, Int) -> Void,
        onProjectUpdated: @escaping @MainActor (String) -> Void
    ) {
        stop()
        guard let url = URL(string: websocketURL), !projectKey.isEmpty else { return }
        receiveTask = Task {
            while !Task.isCancelled {
                let connection = URLSession.shared.webSocketTask(with: url)
                socket = connection
                connection.resume()
                heartbeatTask = Task {
                    while !Task.isCancelled {
                        try? await Task.sleep(nanoseconds: 25_000_000_000)
                        guard !Task.isCancelled else { return }
                        do {
                            try await connection.send(.string(#"{"event":"pusher:ping","data":{}}"#))
                        } catch { return }
                    }
                }
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
                            let projectSubscribe = [
                                "event": "pusher:subscribe",
                                "data": ["channel": "project.\(projectKey).data"],
                            ] as [String: Any]
                            let projectData = try JSONSerialization.data(withJSONObject: projectSubscribe)
                            try await connection.send(.string(String(decoding: projectData, as: UTF8.self)))
                            guard let client, let memberID, memberID > 0 else { continue }
                            let auth = try await client.authorizeWalletChannel(socketID: socketID, memberID: memberID)
                            let memberSubscribe = [
                                "event": "pusher:subscribe",
                                "data": ["auth": auth, "channel": "private-liff.member.\(memberID)"],
                            ] as [String: Any]
                            let data = try JSONSerialization.data(withJSONObject: memberSubscribe)
                            try await connection.send(.string(String(decoding: data, as: UTF8.self)))
                        case .subscribed(let channel):
                            if channel == "project.\(projectKey).data" { onProjectUpdated("all") }
                        case .walletUpdated(let points, let stampsTotal, let count):
                            onWalletUpdated(points, stampsTotal, count)
                        case .projectUpdated(let topic):
                            onProjectUpdated(topic)
                        case .ping:
                            try await connection.send(.string(#"{"event":"pusher:pong","data":{}}"#))
                        }
                    }
                } catch {
                    logger.warning("Wallet Reverb connection ended: \(error.localizedDescription)")
                }
                connection.cancel(with: .goingAway, reason: nil)
                heartbeatTask?.cancel()
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
        heartbeatTask?.cancel()
        heartbeatTask = nil
        socket?.cancel(with: .goingAway, reason: nil)
        socket = nil
    }

    enum Event: Equatable {
        case connected(String)
        case subscribed(String)
        case walletUpdated(points: Int?, stampsTotal: Int?, pendingCount: Int)
        case projectUpdated(String)
        case ping
    }

    nonisolated static func parse(_ raw: String) -> Event? {
        guard let bytes = raw.data(using: .utf8),
              let envelope = try? JSONSerialization.jsonObject(with: bytes) as? [String: Any],
              let name = envelope["event"] as? String else { return nil }
        if name == "pusher:ping" { return .ping }
        if name == "pusher_internal:subscription_succeeded", let channel = envelope["channel"] as? String {
            return .subscribed(channel)
        }
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
            return .walletUpdated(
                points: payload["points"] as? Int,
                stampsTotal: payload["stamps_total"] as? Int,
                pendingCount: count
            )
        }
        if name == "data.updated", let topic = payload["topic"] as? String {
            return .projectUpdated(topic)
        }
        return nil
    }
}
