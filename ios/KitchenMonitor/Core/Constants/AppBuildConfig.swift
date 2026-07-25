//
//  AppBuildConfig.swift
//  KitchenMonitor
//

import Foundation

enum AppServerTarget: String {
    case production
    case local
}

enum AppBuildConfig {
    static let serverTarget: AppServerTarget = .production
    static let projectKey = "sb_skt733n4"

    static let productionBaseURL = "https://reggit.jp"
    static let localBaseURL = "http://localhost:8080"

    static var baseURL: String {
        switch serverTarget {
        case .production: return productionBaseURL
        case .local: return localBaseURL
        }
    }

    static var serverTargetLabel: String {
        switch serverTarget {
        case .production: return "本番 (reggit.jp)"
        case .local: return "ローカル"
        }
    }
}
