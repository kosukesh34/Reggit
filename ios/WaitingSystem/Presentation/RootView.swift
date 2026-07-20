//
//  RootView.swift
//  WaitingSystem
//

import SwiftUI

struct RootView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        Group {
            if appState.needsProjectSelection {
                ProjectSelectionView {
                    appState.completeProjectSelection()
                }
            } else {
                mainContent
            }
        }
    }

    private var mainContent: some View {
        ZStack {
            HomeViewWrapper(appState: appState)
                .allowsHitTesting(appState.currentTicket == nil)

            if appState.isLoading {
                LoadingOverlay()
            }
        }
        .animation(.easeInOut(duration: 0.2), value: appState.currentTicket?.id)
        .fullScreenCover(isPresented: Binding(
            get: { appState.currentTicket != nil },
            set: { if !$0 { appState.currentTicket = nil } }
        )) {
            if let ticket = appState.currentTicket {
                TicketViewWrapper(ticket: ticket, appState: appState)
                    .interactiveDismissDisabled(true)
            }
        }
        .onAppear {
            UIApplication.shared.isIdleTimerDisabled = true
        }
        .alert(Strings.Common.error, isPresented: Binding(
            get: { appState.errorMessage != nil },
            set: { if !$0 { appState.errorMessage = nil } }
        )) {
            Button(Strings.Common.ok) {}
        } message: {
            Text(appState.errorMessage ?? "")
        }
    }
}

// MARK: - Home ViewModel を環境から生成するラッパー

private struct HomeViewWrapper: View {
    let appState: AppState
    @StateObject private var viewModel: HomeViewModel

    init(appState: AppState) {
        self.appState = appState
        _viewModel = StateObject(wrappedValue: HomeViewModel(
            appState: appState,
            apiClient: QueueAPIClient(),
            printerService: PrinterService(),
            settingsStore: SettingsStore()
        ))
    }

    var body: some View {
        HomeView(viewModel: viewModel)
    }
}

// MARK: - Ticket ViewModel を生成するラッパー

private struct TicketViewWrapper: View {
    let ticket: QueueTicket
    let appState: AppState
    @StateObject private var viewModel: TicketViewModel

    init(ticket: QueueTicket, appState: AppState) {
        self.ticket = ticket
        self.appState = appState
        let settings = SettingsStore().load()
        _viewModel = StateObject(wrappedValue: TicketViewModel(
            ticket: ticket,
            settings: settings,
            appState: appState,
            apiClient: QueueAPIClient()
        ))
    }

    var body: some View {
        TicketView(viewModel: viewModel)
    }
}
