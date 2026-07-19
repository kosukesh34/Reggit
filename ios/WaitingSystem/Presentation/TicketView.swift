import SwiftUI

struct TicketView: View {
    @ObservedObject var viewModel: TicketViewModel
    @EnvironmentObject private var appState: AppState
    @State private var showCheckmark = false
    @State private var animateIn = false

    var body: some View {
        GeometryReader { geo in
            let compact = geo.size.height < 760
            let qrSize = min(AppConstants.Layout.qrCodeSize, compact ? 200 : 240)

            ZStack(alignment: .topTrailing) {
                ScrollView(showsIndicators: false) {
                    VStack(spacing: compact ? 20 : 28) {
                        successHeader(compact: compact)
                        ticketNumberCard(compact: compact)
                        summaryRow(compact: compact)
                        qrSection(qrSize: qrSize, compact: compact)
                        actionButtons
                    }
                    .padding(.top, compact ? 44 : 56)
                    .padding(.horizontal, Theme.Spacing.xxl)
                    .padding(.bottom, Theme.Spacing.xxl + 4)
                    .frame(maxWidth: .infinity)
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .background(viewModel.backgroundColorHex.toColor().ignoresSafeArea())

                GearButton { appState.isShowingPasscode = true }
            }
        }
        .sheet(isPresented: $appState.isShowingPasscode) {
            PasscodeView(
                onSuccess: {
                    appState.unlock()
                    appState.isShowingPasscode = false
                },
                onCancel: { appState.isShowingPasscode = false },
                expectedPasscode: viewModel.passcode
            )
        }
        .onAppear {
            if viewModel.currentTicket.lineLinked {
                viewModel.scheduleAutoReturn()
            }
            viewModel.startAutoRefresh()
            DispatchQueue.main.asyncAfter(deadline: .now() + 0.15) { showCheckmark = true }
            withAnimation(.easeOut(duration: 0.5).delay(0.2)) { animateIn = true }
        }
        .onDisappear {
            viewModel.cancelAutoReturn()
            viewModel.stopAutoRefresh()
        }
        .alert(Strings.Ticket.cancelConfirmTitle, isPresented: $viewModel.isShowingCancelConfirm) {
            Button(Strings.Ticket.cancelConfirmAction, role: .destructive) {
                Task { await viewModel.cancelTicket() }
            }
            Button(Strings.Ticket.goBack, role: .cancel) {}
        } message: {
            Text(Strings.Ticket.cancelConfirmMessage)
        }
    }

    // MARK: - Subviews

    @ViewBuilder
    private func successHeader(compact: Bool) -> some View {
        VStack(spacing: Theme.Spacing.sm) {
            ZStack {
                Circle()
                    .fill(Theme.Colors.successSubtle)
                    .frame(width: 56, height: 56)
                    .scaleEffect(showCheckmark ? 1.0 : 0.5)
                Image(systemName: "checkmark.circle.fill")
                    .font(.system(size: 32))
                    .foregroundColor(Theme.Colors.success)
                    .scaleEffect(showCheckmark ? 1.0 : 0.0)
            }
            .animation(.spring(response: 0.5, dampingFraction: 0.6), value: showCheckmark)

            Text(Strings.Ticket.completedTitle)
                .font(.system(size: compact ? 20 : 24, weight: .bold))
                .foregroundColor(Theme.Colors.success)
                .opacity(animateIn ? 1 : 0)
        }
    }

    @ViewBuilder
    private func ticketNumberCard(compact: Bool) -> some View {
        VStack(spacing: Theme.Spacing.xs) {
            Text("受付番号")
                .font(.system(size: 13, weight: .medium))
                .foregroundColor(Theme.Colors.textSecondary)
            Text(viewModel.currentTicket.displayNumber)
                .font(.system(size: compact ? 56 : 72, weight: .heavy, design: .rounded))
                .foregroundColor(Theme.Colors.accent)
        }
        .padding(.vertical, compact ? Theme.Spacing.lg : Theme.Spacing.xxl)
        .padding(.horizontal, Theme.Spacing.xxxl)
        .frame(maxWidth: AppConstants.Layout.contentMaxWidth)
        .background(Theme.Colors.background)
        .cornerRadius(Theme.Radius.xl)
        .overlay(
            RoundedRectangle(cornerRadius: Theme.Radius.xl)
                .stroke(Theme.Colors.accentBorder, lineWidth: 1.5)
        )
        .applyShadow(Theme.Shadow.md)
        .opacity(animateIn ? 1 : 0)
        .offset(y: animateIn ? 0 : 12)
    }

    @ViewBuilder
    private func summaryRow(compact: Bool) -> some View {
        HStack(spacing: Theme.Spacing.xl) {
            Label("\(viewModel.currentTicket.partySize)名", systemImage: "person.2.fill")
            Label("\(viewModel.currentTicket.waitingCount)組待ち", systemImage: "person.3.sequence.fill")
            Label("約\(viewModel.currentTicket.estimatedWaitMinutes)分", systemImage: "clock.fill")
        }
        .font(.system(size: compact ? 15 : 17, weight: .medium))
        .foregroundColor(Theme.Colors.textSecondary)
        .opacity(animateIn ? 1 : 0)
    }

    @ViewBuilder
    private func qrSection(qrSize: CGFloat, compact: Bool) -> some View {
        let qrURL = viewModel.liffURLString()
        let isLinked = viewModel.currentTicket.lineLinked
        VStack(spacing: Theme.Spacing.md) {
            if isLinked {
                Image(systemName: "checkmark.seal.fill")
                    .font(.system(size: compact ? 44 : 56))
                    .foregroundColor(Theme.Colors.success)
                Text(Strings.Ticket.lineLinked)
                    .font(.system(size: compact ? 16 : 18, weight: .semibold))
                    .foregroundColor(Theme.Colors.success)
                    .multilineTextAlignment(.center)
                Text(Strings.Ticket.qrNotifyMessage)
                    .font(.system(size: compact ? 13 : 15))
                    .foregroundColor(Theme.Colors.textSecondary)
                    .multilineTextAlignment(.center)
            } else {
                if let qr = QRCodeGenerator().generate(from: qrURL, size: qrSize) {
                    Image(uiImage: qr)
                        .interpolation(.none)
                        .resizable()
                        .frame(width: qrSize, height: qrSize)
                        .padding(Theme.Spacing.lg)
                        .background(Color.white)
                        .cornerRadius(Theme.Radius.lg)
                        .applyShadow(Theme.Shadow.sm)
                }
                Text(Strings.Ticket.lineNotLinked)
                    .font(.system(size: compact ? 14 : 16, weight: .semibold))
                    .foregroundColor(Theme.Colors.warning)
                    .multilineTextAlignment(.center)
                Text(Strings.Ticket.qrScanMessage)
                    .font(.system(size: compact ? 13 : 15))
                    .foregroundColor(Theme.Colors.textSecondary)
                    .multilineTextAlignment(.center)
            }
        }
        .animation(.easeInOut(duration: 0.25), value: isLinked)
        .opacity(animateIn ? 1 : 0)
        .offset(y: animateIn ? 0 : 16)
    }

    @ViewBuilder
    private var actionButtons: some View {
        if appState.isUnlocked {
            HStack(spacing: Theme.Spacing.lg) {
                SecondaryButton(title: Strings.Ticket.newAccept) {
                    appState.currentTicket = nil
                }
                DangerButton(title: Strings.Ticket.cancelTicket) {
                    viewModel.isShowingCancelConfirm = true
                }
            }
            .transition(.opacity.combined(with: .move(edge: .bottom)))
        }
    }
}
