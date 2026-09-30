import Foundation

enum MobileSelectedProjectStore {
    private static var projectKeyDefaultsKey: String { projectKeyDefaultsKey(for: .current) }

    private static func projectKeyDefaultsKey(for environment: MobileConnectionEnvironment) -> String {
        "reggit.selected_project_key." + environment.storageKey
    }
    private static var selectedFlagKey: String { "reggit.project_selection_completed." + MobileConnectionEnvironment.current.storageKey }

    static func selectedProjectKey(for environment: MobileConnectionEnvironment) -> String? {
        let value = UserDefaults.standard.string(forKey: projectKeyDefaultsKey(for: environment))?
            .trimmingCharacters(in: .whitespacesAndNewlines)
        return (value?.isEmpty == false) ? value : nil
    }

    static var selectedProjectKey: String? {
        get {
            selectedProjectKey(for: .current)
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
