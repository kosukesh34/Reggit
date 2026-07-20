import SkipWeb

@MainActor
enum MobileWebShellSession {
    private static var sharedViewModel: MobileWebShellViewModel?

    static func viewModel() -> MobileWebShellViewModel {
        if let sharedViewModel {
            return sharedViewModel
        }
        let created = MobileWebShellFactory.makeViewModel()
        sharedViewModel = created
        return created
    }

    static func reset() {
        sharedViewModel = nil
    }
}
