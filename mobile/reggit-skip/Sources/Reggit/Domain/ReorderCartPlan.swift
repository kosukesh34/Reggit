import Foundation

/// Restore the entire order or fail without silently changing its contents.
struct ReorderCartPlan {
    let quantities: [Int: Int]
    let options: [Int: [Int]]
    let notes: [Int: String]

    static func make(order: OrderSnapshot, products: [ProductItem]) throws -> ReorderCartPlan {
        guard !order.items.isEmpty else { throw ReorderError.unavailable("注文内容") }
        var quantities: [Int: Int] = [:]
        var options: [Int: [Int]] = [:]
        var notes: [Int: String] = [:]
        for item in order.items {
            guard let id = item.productId, item.quantity > 0,
                  let product = products.first(where: { $0.id == id }), product.isAvailable else {
                throw ReorderError.unavailable(item.productName)
            }
            let allOptions = product.optionGroups.flatMap(\.options)
            var selected = item.optionIds ?? []
            var note = item.notes?.trimmedNonEmpty
            if item.optionIds == nil, !product.optionGroups.isEmpty, let legacyNote = note {
                // Older orders stored option names in the first notes segment.
                let names = legacyNote.components(separatedBy: " / ")[0].components(separatedBy: ", ")
                selected = try names.map { name in
                    let matches = allOptions.filter { $0.name == name }
                    guard matches.count == 1 else { throw ReorderError.optionsChanged(item.productName) }
                    return matches[0].id
                }
            }
            selected = Array(Set(selected)).sorted()
            guard selected.allSatisfy({ id in allOptions.contains { $0.id == id } }) else {
                throw ReorderError.optionsChanged(item.productName)
            }
            for group in product.optionGroups {
                let count = group.options.filter { selected.contains($0.id) }.count
                if (group.isRequired && count == 0) || (group.selectionType == "single" && count > 1) {
                    throw ReorderError.optionsChanged(item.productName)
                }
            }
            // Server adds the option summary again when creating the new order.
            if !selected.isEmpty, let existing = note {
                let summary = existing.components(separatedBy: " / ")[0]
                let selectedNames = allOptions.filter { selected.contains($0.id) }.map(\.name)
                if Set(summary.components(separatedBy: ", ")) == Set(selectedNames) {
                    let segments = existing.components(separatedBy: " / ")
                    note = segments.count > 1 ? segments.dropFirst().joined(separator: " / ") : nil
                }
            }
            if quantities[id] != nil, options[id] != selected || notes[id] != note {
                throw ReorderError.optionsChanged(item.productName)
            }
            let quantity = (quantities[id] ?? 0) + item.quantity
            if let stock = product.stock, quantity > stock { throw ReorderError.unavailable(item.productName) }
            quantities[id] = quantity
            options[id] = selected
            notes[id] = note
        }
        return ReorderCartPlan(quantities: quantities, options: options, notes: notes)
    }
}

enum ReorderError: LocalizedError {
    case unavailable(String)
    case optionsChanged(String)

    var errorDescription: String? {
        switch self {
        case .unavailable(let name): return "「\(name)」は販売終了または在庫不足のため再注文できません。商品一覧から選び直してください。"
        case .optionsChanged(let name): return "「\(name)」のオプションを復元できません。商品一覧から選び直してください。"
        }
    }
}
