import Foundation

struct QueueTicket: Identifiable, Codable, Equatable {
    let id: Int
    let queueNumber: Int
    let partySize: Int
    let status: String
    let lineLinked: Bool
    let qrToken: String?
    let createdAt: Date
    let updatedAt: Date
    let waitingCount: Int
    let aheadCount: Int?
    let estimatedWaitMinutes: Int

    var displayNumber: String { "No.\(queueNumber)" }
}

struct QueueTicketResponse: Codable {
    let message: String?
    let updatedAt: Date?
    let queueEntry: QueueEntryPayload?

    struct QueueEntryPayload: Codable {
        let id: Int
        let queueNumber: Int
        let status: String
        let partySize: Int
        let lineLinked: Bool?
        let qrToken: String?
        let createdAt: Date
        let updatedAt: Date
        let waitingCount: Int?
        let aheadCount: Int?
        let estimatedWaitMinutes: Int?
    }
}

struct QueueListEntry: Identifiable, Codable, Equatable {
    let id: Int
    let queueNumber: Int
    let status: String
    let partySize: Int
    let lineLinked: Bool
    let memberLinked: Bool
    let displayName: String
    let createdAt: String

    var displayNumber: String { "No.\(queueNumber)" }
}

struct QueueListResponse: Codable {
    let entries: [QueueListEntry]
    let updatedAt: String?
}
