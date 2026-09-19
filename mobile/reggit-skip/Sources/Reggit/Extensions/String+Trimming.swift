import Foundation

extension String {
    var trimmed: String {
        trimmingCharacters(in: .whitespacesAndNewlines)
    }

    var trimmedNonEmpty: String? {
        let value = trimmed
        return value.isEmpty ? nil : value
    }
}
