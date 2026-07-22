import Foundation

enum QueueLinkChannel: String, Codable, Equatable {
    case none
    case line
    case app
    case both

    var isLinked: Bool { self != .none }

    static func resolve(raw: String?, lineLinked: Bool?) -> QueueLinkChannel {
        if let raw, let channel = QueueLinkChannel(rawValue: raw) {
            return channel
        }
        return (lineLinked ?? false) ? .app : .none
    }
}

struct QueueTicket: Identifiable, Codable, Equatable {
    let id: Int
    let queueNumber: Int
    let formattedQueueNumber: String?
    let partySize: Int
    let status: String
    let lineLinked: Bool
    let linkChannel: QueueLinkChannel
    let qrToken: String?
    let createdAt: Date
    let updatedAt: Date
    let waitingCount: Int
    let aheadCount: Int?
    let estimatedWaitMinutes: Int

    var displayNumber: String {
        let source = (formattedQueueNumber ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let number = source.isEmpty
            ? DailySequenceNumberFormatter.formatStoredForDisplay(queueNumber)
            : DailySequenceNumberFormatter.formatStoredForDisplay(source)
        return "No.\(number)"
    }

    var isLinked: Bool { linkChannel.isLinked }
}

struct QueueTicketResponse: Codable {
    let message: String?
    let updatedAt: Date?
    let queueEntry: QueueEntryPayload?

    struct QueueEntryPayload: Codable {
        let id: Int
        let queueNumber: Int
        let formattedQueueNumber: String?
        let status: String
        let partySize: Int
        let lineLinked: Bool?
        let linkChannel: String?
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
    let formattedQueueNumber: String?
    let status: String
    let partySize: Int
    let lineLinked: Bool
    let linkChannel: QueueLinkChannel
    let memberLinked: Bool
    let displayName: String
    let createdAt: String

    enum CodingKeys: String, CodingKey {
        case id, queueNumber, formattedQueueNumber, status, partySize
        case lineLinked, linkChannel, memberLinked, displayName, createdAt
    }

    init(
        id: Int,
        queueNumber: Int,
        formattedQueueNumber: String?,
        status: String,
        partySize: Int,
        lineLinked: Bool,
        linkChannel: QueueLinkChannel,
        memberLinked: Bool,
        displayName: String,
        createdAt: String
    ) {
        self.id = id
        self.queueNumber = queueNumber
        self.formattedQueueNumber = formattedQueueNumber
        self.status = status
        self.partySize = partySize
        self.lineLinked = lineLinked
        self.linkChannel = linkChannel
        self.memberLinked = memberLinked
        self.displayName = displayName
        self.createdAt = createdAt
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        id = try container.decode(Int.self, forKey: .id)
        queueNumber = try container.decode(Int.self, forKey: .queueNumber)
        formattedQueueNumber = try container.decodeIfPresent(String.self, forKey: .formattedQueueNumber)
        status = try container.decode(String.self, forKey: .status)
        partySize = try container.decode(Int.self, forKey: .partySize)
        let linked = try container.decodeIfPresent(Bool.self, forKey: .lineLinked) ?? false
        lineLinked = linked
        let rawChannel = try container.decodeIfPresent(String.self, forKey: .linkChannel)
        linkChannel = QueueLinkChannel.resolve(raw: rawChannel, lineLinked: linked)
        memberLinked = try container.decodeIfPresent(Bool.self, forKey: .memberLinked) ?? false
        displayName = try container.decode(String.self, forKey: .displayName)
        createdAt = try container.decode(String.self, forKey: .createdAt)
    }

    var displayNumber: String {
        let source = (formattedQueueNumber ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let number = source.isEmpty
            ? DailySequenceNumberFormatter.formatStoredForDisplay(queueNumber)
            : DailySequenceNumberFormatter.formatStoredForDisplay(source)
        return "No.\(number)"
    }
}

struct QueueListResponse: Codable {
    let entries: [QueueListEntry]
    let updatedAt: String?
}
