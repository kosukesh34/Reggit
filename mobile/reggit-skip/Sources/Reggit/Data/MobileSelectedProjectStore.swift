import Foundation

enum MobileSelectedProjectStore {
    private static let projectKeyDefaultsKey = "reggit.selected_project_key"
    private static let selectedFlagKey = "reggit.project_selection_completed"

    static var selectedProjectKey: String? {
        get {
            let value = UserDefaults.standard.string(forKey: projectKeyDefaultsKey)?
                .trimmingCharacters(in: .whitespacesAndNewlines)
            return (value?.isEmpty == false) ? value : nil
        }
        set {
            if let newValue, !newValue.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                UserDefaults.standard.set(newValue, forKey: projectKeyDefaultsKey)
            } else {
                UserDefaults.standard.removeObject(forKey: projectKeyDefaultsKey)
            }
        }
    }

    static var hasCompletedSelection: Bool {
        get { UserDefaults.standard.bool(forKey: selectedFlagKey) }
        set { UserDefaults.standard.set(newValue, forKey: selectedFlagKey) }
    }

    static func needsSelection(bundleDefaultKey: String) -> Bool {
        if hasCompletedSelection, let selected = selectedProjectKey, !selected.isEmpty {
            return false
        }
        let key = selectedProjectKey ?? bundleDefaultKey
        return key.isEmpty || key == "default"
    }
}
