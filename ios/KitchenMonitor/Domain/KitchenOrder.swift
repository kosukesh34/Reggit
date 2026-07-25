import Foundation

struct KitchenOrder: Identifiable, Equatable {
    let id: Int
    let orderNumber: String
    let status: String
    let orderType: String?
    let notes: String?
    let createdAt: Date?
    let items: [KitchenOrderItem]

    var statusLabel: String {
        switch status {
        case "confirmed": return "受付"
        case "preparing": return "準備中"
        case "ready": return "受取可"
        case "completed": return "完了"
        default: return status
        }
    }

    var nextStatus: String? {
        switch status {
        case "confirmed": return "preparing"
        case "preparing": return "ready"
        case "ready": return "completed"
        default: return nil
        }
    }

    var nextStatusLabel: String? {
        switch nextStatus {
        case "preparing": return "準備中にする"
        case "ready": return "受取可にする"
        case "completed": return "完了にする"
        default: return nil
        }
    }
}

struct KitchenOrderItem: Equatable {
    let name: String
    let quantity: Int
    let notes: String?
}
