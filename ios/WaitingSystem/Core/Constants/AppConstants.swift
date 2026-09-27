//
//  AppConstants.swift
//  WaitingSystem
//
//  アプリ全体で使う定数（キー・数値・デフォルト値）を集約します。
//

import Foundation

enum AppConstants {

    // MARK: - UserDefaults

    enum Storage {
        static let settingsKey = "waiting_system_settings"
    }

    // MARK: - 時間・間隔

    enum Timing {
        /// パスコード解除の有効時間（秒）
        static let unlockDurationSeconds: TimeInterval = 5 * 60
        /// チケット画面の自動戻りまでの秒数（未解除時）
        static let ticketAutoReturnSeconds: TimeInterval = 30
        /// チケット画面の最新情報ポーリング間隔（秒）
        static let ticketRefreshIntervalSeconds: TimeInterval = 30
        /// 未連携時のチケット画面ポーリング間隔（秒）
        static let ticketLinkPollIntervalSeconds: TimeInterval = 3
    }

    // MARK: - 受付

    enum PartySize {
        static let minimum = 1
        static let maximum = 20
        static let `default` = 2
    }

    // MARK: - デフォルト設定値（プロジェクトはログイン後に保存）

    enum DefaultSettings {
        static var baseURL: String { AppBuildConfig.baseURL }
        static let projectKey = ""
        static var liffURL: String { WaitingRemoteConfigStore.current.lineLiffUrl }
        /// ローカル iPad と同じライト配色の既定背景
        static let backgroundColorHex = "#F2F4F8"
        static let passcode = "1234"
    }

    // MARK: - UI レイアウト（共通で参照する場合）

    enum Layout {
        static let cardCornerRadius: CGFloat = 20
        static let cardShadowRadius: CGFloat = 12
        static let cardShadowY: CGFloat = 6
        static let cardPadding: CGFloat = 28
        static let contentCornerRadius: CGFloat = 16
        static let qrCodeSize: CGFloat = 260
        static let primaryButtonMaxWidth: CGFloat = 340
        static let contentMaxWidth: CGFloat = 520
    }

    // MARK: - 印刷

    enum Print {
        static let ticketImageWidth: CGFloat = 576
        static let ticketImageHeight: CGFloat = 900
        static let ticketQRSize: CGFloat = 280
    }
}
