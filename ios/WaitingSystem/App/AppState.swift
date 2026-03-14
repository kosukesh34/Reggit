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

    private var unlockUntil: Date?

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
