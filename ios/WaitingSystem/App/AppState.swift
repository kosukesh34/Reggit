//
//  AppState.swift
//  WaitingSystem
//
//  アプリ全体の共有状態（チケット・ローディング・エラー・設定UI・ロック）を保持する ObservableObject です。
//

import Foundation

@MainActor
final class AppState: ObservableObject {
    @Published var currentTicket: QueueTicket?
    @Published var isShowingSettings = false
    @Published var isShowingPasscode = false
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var needsProjectSelection: Bool

    private var unlockUntil: Date?
    private let settingsStore: SettingsStoreProtocol

    init(settingsStore: SettingsStoreProtocol = SettingsStore()) {
        self.settingsStore = settingsStore
        let settings = settingsStore.load()
        self.needsProjectSelection = Self.shouldShowProjectSelection(settings.projectKey)
    }

    static func shouldShowProjectSelection(_ projectKey: String) -> Bool {
        let key = projectKey.trimmingCharacters(in: .whitespacesAndNewlines)
        return key.isEmpty || key == "default"
    }

    func completeProjectSelection() {
        let settings = settingsStore.load()
        needsProjectSelection = Self.shouldShowProjectSelection(settings.projectKey)
    }

    var isUnlocked: Bool {
        guard let unlockUntil else { return false }
        return unlockUntil > Date()
    }

    func unlock() {
        unlockUntil = Date().addingTimeInterval(AppConstants.Timing.unlockDurationSeconds)
    }

    func lock() {
        unlockUntil = nil
    }
}
