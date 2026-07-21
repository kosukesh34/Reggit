import SwiftUI

struct TicketView: View {
    @ObservedObject var viewModel: TicketViewModel
    @EnvironmentObject private var appState: AppState
    @State private var showCheckmark = false
    @State private var animateIn = false

    var body: some View {
        GeometryReader { geo in
            let compact = geo.size.height < 760
            let qrSize = min(AppConstants.Layout.qrCodeSize, compact ? 160 : 200)

            ZStack(alignment: .topTrailing) {
                VStack(spacing: compact ? 14 : 20) {
                    successHeader(compact: compact)
                    ticketNumberCard(compact: compact)
                    summaryRow(compact: compact)
                    qrSection(qrSize: qrSize, compact: compact)
                    Text(Strings.Ticket.autoReturnNotice)
                        .font(.system(size: compact ? 12 : 13))
                        .foregroundColor(Theme.Colors.textSecondary)
                    Spacer(minLength: 0)
                    actionButtons
                }
                .padding(.top, compact ? 36 : 48)
                .padding(.horizontal, Theme.Spacing.xxl)
                .padding(.bottom, Theme.Spacing.xl)
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
            viewModel.scheduleAutoReturn()
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
                    .frame(width: compact ? 48 : 56, height: compact ? 48 : 56)
                    .scaleEffect(showCheckmark ? 1.0 : 0.5)
                Image(systemName: "checkmark.circle.fill")
                    .font(.system(size: compact ? 28 : 32))
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
                .font(.system(size: compact ? 48 : 64, weight: .heavy, design: .rounded))
                .foregroundColor(Theme.Colors.accent)
        }
        .padding(.vertical, compact ? Theme.Spacing.md : Theme.Spacing.xl)
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
        .font(.system(size: compact ? 14 : 16, weight: .medium))
        .foregroundColor(Theme.Colors.textSecondary)
        .opacity(animateIn ? 1 : 0)
    }

    @ViewBuilder
    private func qrSection(qrSize: CGFloat, compact: Bool) -> some View {
        let channel = viewModel.currentTicket.linkChannel
        VStack(spacing: Theme.Spacing.sm) {
            if channel.isLinked {
                Image(systemName: "checkmark.seal.fill")
                    .font(.system(size: compact ? 40 : 48))
                    .foregroundColor(Theme.Colors.success)
                Text(linkedTitle(for: channel))
                    .font(.system(size: compact ? 16 : 18, weight: .semibold))
                    .foregroundColor(Theme.Colors.success)
                    .multilineTextAlignment(.center)
                Text(Strings.Ticket.qrNotifyMessage)
                    .font(.system(size: compact ? 13 : 14))
                    .foregroundColor(Theme.Colors.textSecondary)
                    .multilineTextAlignment(.center)
            } else {
                if let qr = QRCodeGenerator().generate(from: viewModel.liffURLString(), size: qrSize) {
                    Image(uiImage: qr)
                        .interpolation(.none)
                        .resizable()
                        .frame(width: qrSize, height: qrSize)
                        .padding(Theme.Spacing.md)
                        .background(Color.white)
                        .cornerRadius(Theme.Radius.lg)
                        .applyShadow(Theme.Shadow.sm)
                }
                Text(Strings.Ticket.qrScanMessage)
                    .font(.system(size: compact ? 13 : 14))
                    .foregroundColor(Theme.Colors.textSecondary)
                    .multilineTextAlignment(.center)
                    .padding(.horizontal, Theme.Spacing.lg)
            }
        }
        .animation(.easeInOut(duration: 0.25), value: channel)
        .opacity(animateIn ? 1 : 0)
        .offset(y: animateIn ? 0 : 16)
    }

    private func linkedTitle(for channel: QueueLinkChannel) -> String {
        switch channel {
        case .line:
            return Strings.Ticket.linkedLine
        case .app:
            return Strings.Ticket.linkedApp
        case .both:
            return Strings.Ticket.linkedBoth
        case .none:
            return ""
        }
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
