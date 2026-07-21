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
                // 受付端末はローカル iPad と同じライト配色で固定（ダークモードだと文字が消える）
                .preferredColorScheme(.light)
                .task {
                    await WaitingRemoteConfigStore.refreshFromAPI()
                    let store = SettingsStore()
                    store.save(store.load())
                }
        }
    }
}
