import SwiftUI

enum QueueStatus: String {
    case waiting
    case called
    case entered
    case cancelled

    init(raw: String) {
        self = QueueStatus(rawValue: raw) ?? .waiting
    }

    var label: String {
        switch self {
        case .waiting:   return "待機中"
        case .called:    return "呼出済"
        case .entered:   return "入店済"
        case .cancelled: return "取消"
        }
    }

    var color: Color {
        switch self {
        case .waiting:   return Theme.Colors.warning
        case .called:    return Theme.Colors.accent
        case .entered:   return Theme.Colors.success
        case .cancelled: return Theme.Colors.textSecondary
        }
    }
}
