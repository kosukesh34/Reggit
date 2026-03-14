//
//  WaitingSystemApp.swift
//  WaitingSystem
//

import SwiftUI

@main
struct WaitingSystemApp: App {
    @StateObject private var appState = AppState()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(appState)
        }
    }
}
