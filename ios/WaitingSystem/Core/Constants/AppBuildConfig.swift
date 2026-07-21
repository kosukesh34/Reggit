//
//  AppBuildConfig.swift
//  WaitingSystem
//
//  ビルド前に「向け先」と「プロジェクト」だけここを書き換える。
//  LINE / LIFF 設定は API（/api/v1/app-config）から取得する。
//

import Foundation

enum AppServerTarget: String {
    /// 本番 (https://reggit.jp)
    case production
    /// ローカル / 開発トンネル
    case local
}

enum AppBuildConfig {
    // MARK: - ビルド前に切り替える値

    /// 接続先。`.production` か `.local` を選んでビルドする。
    static let serverTarget: AppServerTarget = .production

    /// このビルドが対象とするプロジェクトキー（オンボーディングで作成したキー）。
    static let projectKey = "sb_skt733n4"

    // MARK: - URL 定義

    static let productionBaseURL = "https://reggit.jp"
    /// ローカル Docker / 開発用。ngrok 利用時はここを書き換える。
    static let localBaseURL = "http://localhost:8080"

    // MARK: - 解決値

    static var baseURL: String {
        switch serverTarget {
        case .production:
            return productionBaseURL
        case .local:
            return localBaseURL
        }
    }

    static var serverTargetLabel: String {
        switch serverTarget {
        case .production:
            return "本番 (reggit.jp)"
        case .local:
            return "ローカル"
        }
    }

    static var hasFixedProjectKey: Bool {
        !projectKey.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
    }
}
