import SwiftUI

struct HomeView: View {
    @ObservedObject var viewModel: HomeViewModel
    @EnvironmentObject private var appState: AppState
    @State private var animateIn = false

    var body: some View {
        GeometryReader { geo in
            let compact = geo.size.height < 760

            ZStack(alignment: .topTrailing) {
                VStack(spacing: 0) {
                    Spacer()
                    heroSection(compact: compact)
                    Spacer()
                    bottomBar(compact: compact)
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .background(viewModel.settings.backgroundColorHex.toColor().ignoresSafeArea())

                GearButton { appState.isShowingPasscode = true }
            }
        }
        .sheet(isPresented: $appState.isShowingPasscode) {
            PasscodeView(
                onSuccess: {
                    appState.unlock()
                    appState.isShowingPasscode = false
                    appState.isShowingSettings = true
                },
                onCancel: { appState.isShowingPasscode = false },
                expectedPasscode: viewModel.settings.passcode
            )
        }
        .sheet(isPresented: $appState.isShowingSettings) {
            SettingsSheetView(
                queueListEntries: viewModel.queueListEntries,
                onSelectPrinter: { refresh in
                    viewModel.selectPrinter {
                        Task { @MainActor in
                            viewModel.reloadSettings()
                            refresh()
                        }
                    }
                },
                onQueueMutated: {
                    Task { await viewModel.refreshQueueOverview() }
                }
            )
        }
        .onAppear {
            viewModel.reloadSettings()
            viewModel.startOverviewRefresh()
            withAnimation(.easeOut(duration: 0.6).delay(0.1)) { animateIn = true }
        }
        .onDisappear { viewModel.stopOverviewRefresh() }
        .sheet(isPresented: $viewModel.isShowingPartySizeSheet) {
            PartySizeSheet(
                partySize: $viewModel.partySize,
                onConfirm: {
                    viewModel.isShowingPartySizeSheet = false
                    Task { await viewModel.createTicket() }
                },
                onCancel: { viewModel.isShowingPartySizeSheet = false }
            )
        }
    }

    // MARK: - Hero

    @ViewBuilder
    private func heroSection(compact: Bool) -> some View {
        VStack(spacing: compact ? 28 : 40) {
            Text(Strings.Home.heroTitle)
                .font(.system(size: compact ? 34 : 42, weight: .bold, design: .rounded))
                .foregroundColor(Theme.Colors.textPrimary)
                .multilineTextAlignment(.center)
                .padding(.horizontal, Theme.Spacing.xxxl)
                .opacity(animateIn ? 1 : 0)
                .offset(y: animateIn ? 0 : 10)

            WaitingCountBanner(
                waitingCount: viewModel.waitingCount,
                estimatedWaitMinutes: viewModel.estimatedWaitMinutes
            )
            .opacity(animateIn ? 1 : 0)
            .offset(y: animateIn ? 0 : 14)
        }
    }

    // MARK: - Bottom Bar

    @ViewBuilder
    private func bottomBar(compact: Bool) -> some View {
        VStack(spacing: 0) {
            Divider().opacity(0.3)
            PrimaryButton(title: Strings.Home.acceptButton) {
                viewModel.isShowingPartySizeSheet = true
            }
            .frame(maxWidth: AppConstants.Layout.primaryButtonMaxWidth)
            .padding(.horizontal, Theme.Spacing.xxl)
            .padding(.vertical, compact ? Theme.Spacing.lg : Theme.Spacing.xl)
        }
        .background(.ultraThinMaterial)
    }
}

// MARK: - Waiting Count Banner

private struct WaitingCountBanner: View {
    let waitingCount: Int
    let estimatedWaitMinutes: Int

    var body: some View {
        HStack(spacing: Theme.Spacing.xxl) {
            metricColumn(label: "現在", value: "\(waitingCount)", unit: "組待ち")

            if estimatedWaitMinutes > 0 {
                Rectangle()
                    .fill(Theme.Colors.separator.opacity(0.4))
                    .frame(width: 1, height: 44)

                metricColumn(label: "目安", value: "約\(estimatedWaitMinutes)", unit: "分")
            }
        }
        .padding(.horizontal, Theme.Spacing.xxxl)
    }

    @ViewBuilder
    private func metricColumn(label: String, value: String, unit: String) -> some View {
        VStack(spacing: Theme.Spacing.xs) {
            Text(label)
                .font(.system(size: 14, weight: .medium))
                .foregroundColor(Theme.Colors.textSecondary)
            HStack(alignment: .firstTextBaseline, spacing: Theme.Spacing.xxs) {
                Text(value)
                    .font(.system(size: 42, weight: .bold, design: .rounded))
                    .foregroundColor(Theme.Colors.textPrimary)
                Text(unit)
                    .font(.system(size: 16, weight: .semibold))
                    .foregroundColor(Theme.Colors.textSecondary)
            }
        }
    }
}
