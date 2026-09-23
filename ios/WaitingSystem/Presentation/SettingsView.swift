import SwiftUI

// MARK: - Settings Sheet Wrapper

struct SettingsSheetView: View {
    @EnvironmentObject private var appState: AppState
    @StateObject private var viewModel = SettingsViewModel()
    let queueListEntries: [QueueListEntry]
    let onSelectPrinter: (@escaping () -> Void) -> Void
    let onQueueMutated: () -> Void

    var body: some View {
        SettingsView(
            viewModel: viewModel,
            queueListEntries: queueListEntries,
            onSelectPrinter: onSelectPrinter,
            onQueueMutated: onQueueMutated,
            onSave: {
                viewModel.save()
                appState.isShowingSettings = false
            },
            onClose: {
                appState.isShowingSettings = false
            }
        )
    }
}

// MARK: - Settings Form

struct SettingsView: View {
    @ObservedObject var viewModel: SettingsViewModel
    let queueListEntries: [QueueListEntry]
    let onSelectPrinter: (@escaping () -> Void) -> Void
    let onQueueMutated: () -> Void
    let onSave: () -> Void
    let onClose: () -> Void

    var body: some View {
        NavigationView {
            Form {
                queueListSection
                callSection
                serverSection
                printerSection
                screenSection
                securitySection
            }
            .navigationTitle(Strings.Settings.navigationTitle)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button(Strings.Common.close) { onClose() }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button(Strings.Common.save) { onSave() }
                        .fontWeight(.semibold)
                }
            }
        }
    }

    // MARK: - Queue List

    private var queueListSection: some View {
        Section {
            if queueListEntries.isEmpty {
                Label(Strings.Home.waitingListEmpty, systemImage: "person.2.slash")
                    .foregroundColor(Theme.Colors.textSecondary)
                    .padding(.vertical, Theme.Spacing.xs)
            } else {
                ForEach(queueListEntries) { entry in
                    QueueEntryRow(
                        entry: entry,
                        isActing: viewModel.actingEntryId == entry.id,
                        onRecall: {
                            Task {
                                await viewModel.recall(id: entry.id)
                                onQueueMutated()
                            }
                        },
                        onEnter: {
                            Task {
                                await viewModel.enter(id: entry.id)
                                onQueueMutated()
                            }
                        }
                    )
                }
            }

            if let message = viewModel.actionMessage {
                Text(message)
                    .font(.footnote)
                    .foregroundColor(Theme.Colors.textSecondary)
            }
        } header: {
            Label(Strings.Home.waitingListTitle, systemImage: "list.number")
        }
    }

    // MARK: - Call

    private var callSection: some View {
        Section(header: Text(Strings.Settings.callSection)) {
            Button {
                Task {
                    await viewModel.callNext()
                    onQueueMutated()
                }
            } label: {
                HStack {
                    Image(systemName: "bell.badge.fill")
                        .foregroundColor(Theme.Colors.accent)
                    Text(viewModel.isCallingNext ? Strings.Settings.callingButton : Strings.Settings.callNextButton)
                }
            }
            .disabled(viewModel.isCallingNext || viewModel.actingEntryId != nil)

            if let message = viewModel.callNextMessage {
                Text(message)
                    .font(.footnote)
                    .foregroundColor(Theme.Colors.textSecondary)
            }
        }
    }

    // MARK: - Server（ビルド時固定・LINE は API）

    private var serverSection: some View {
        let remote = WaitingRemoteConfigStore.current
        return Section {
            LabeledContent("向け先", value: AppBuildConfig.serverTargetLabel)
            LabeledContent("API URL", value: AppBuildConfig.baseURL)
            LabeledContent("プロジェクト", value: AppBuildConfig.projectKey)
            SecureField("店舗端末トークン", text: Binding(
                get: { viewModel.settings.storeToken ?? "" },
                set: { viewModel.settings.storeToken = $0.trimmingCharacters(in: .whitespacesAndNewlines) }
            ))
            .textInputAutocapitalization(.never)
            .autocorrectionDisabled()
            if !remote.projectName.isEmpty {
                LabeledContent("店舗名", value: remote.projectName)
            }
            if !remote.lineLiffId.isEmpty {
                LabeledContent("LIFF ID", value: remote.lineLiffId)
            }
            if !remote.lineLiffUrl.isEmpty {
                LabeledContent("LIFF URL", value: remote.lineLiffUrl)
            }
        } header: {
            Text(Strings.Settings.serverSection)
        } footer: {
            Text("接続先とプロジェクトはビルド時固定。LINE / LIFF はサーバー設定を API から取得します。")
                .font(.footnote)
        }
    }

    // MARK: - Printer

    private var printerSection: some View {
        Section(header: Text(Strings.Settings.printerSection)) {
            Toggle(Strings.Settings.printTicketToggle, isOn: $viewModel.settings.shouldPrintTicket)
            if !viewModel.settings.shouldPrintTicket {
                Text(Strings.Settings.printDisabledHint)
                    .font(.footnote)
                    .foregroundColor(Theme.Colors.textSecondary)
            }
            if let name = viewModel.settings.printerName {
                Text(String(format: Strings.Settings.printerSelectedFormat, name))
            } else {
                Text(Strings.Settings.printerNotSelected)
                    .foregroundColor(Theme.Colors.textSecondary)
            }
            Button(Strings.Settings.selectPrinter) {
                onSelectPrinter { viewModel.reload() }
            }
            .disabled(!viewModel.settings.shouldPrintTicket)
        }
    }

    // MARK: - Screen

    private var screenSection: some View {
        Section(header: Text(Strings.Settings.screenSection)) {
            ColorPicker(Strings.Settings.backgroundColor, selection: Binding(
                get: { viewModel.settings.backgroundColorHex.toColor() },
                set: { viewModel.settings.backgroundColorHex = $0.toHexString() }
            ))
            TextField(Strings.Settings.backgroundColorHexPlaceholder, text: $viewModel.settings.backgroundColorHex)
                .textInputAutocapitalization(.never)
                .disableAutocorrection(true)
        }
    }

    // MARK: - Security

    private var securitySection: some View {
        Section(header: Text(Strings.Settings.securitySection)) {
            SecureField(Strings.Settings.passcodeField, text: $viewModel.settings.passcode)
                .keyboardType(.numberPad)
                .onChange(of: viewModel.settings.passcode) { newValue in
                    let digits = newValue.filter(\.isNumber)
                    viewModel.settings.passcode = String(digits.prefix(8))
                }
            Text(Strings.Settings.passcodeHint)
                .font(.footnote)
                .foregroundColor(Theme.Colors.textSecondary)
        }
    }
}

// MARK: - Queue Entry Row

private struct QueueEntryRow: View {
    let entry: QueueListEntry
    let isActing: Bool
    let onRecall: () -> Void
    let onEnter: () -> Void

    private var isCalled: Bool {
        QueueStatus(raw: entry.status) == .called
    }

    var body: some View {
        HStack(spacing: Theme.Spacing.md) {
            Text(entry.displayNumber)
                .font(.system(size: 17, weight: .bold, design: .rounded))
                .foregroundColor(Theme.Colors.accent)
                .frame(width: 56, alignment: .leading)

            VStack(alignment: .leading, spacing: Theme.Spacing.xxs) {
                Text(entry.displayName)
                    .font(.system(size: 15, weight: .semibold))
                    .lineLimit(1)
                Text("\(entry.partySize)名")
                    .font(.system(size: 13))
                    .foregroundColor(Theme.Colors.textSecondary)
            }

            Spacer(minLength: Theme.Spacing.sm)

            HStack(spacing: Theme.Spacing.xs) {
                ForEach(linkBadges(for: entry.linkChannel), id: \.label) { badge in
                    LinkBadge(label: badge.label, color: badge.color)
                }
            }

            if isCalled {
                HStack(spacing: Theme.Spacing.md) {
                    Button(action: onRecall) {
                        Text(isActing ? Strings.Settings.recallingButton : Strings.Settings.recallButton)
                            .font(.system(size: 13, weight: .medium))
                    }
                    .foregroundColor(Theme.Colors.accent)
                    .disabled(isActing)

                    Button(action: onEnter) {
                        Text(isActing ? Strings.Settings.enteringButton : Strings.Settings.enterButton)
                            .font(.system(size: 13, weight: .semibold))
                    }
                    .foregroundColor(Theme.Colors.success)
                    .disabled(isActing)
                }
                .buttonStyle(.plain)
            } else {
                QueueStatusBadge(status: QueueStatus(raw: entry.status))
            }
        }
        .padding(.vertical, Theme.Spacing.xs)
    }

    private func linkBadges(for channel: QueueLinkChannel) -> [(label: String, color: Color)] {
        switch channel {
        case .none:
            return [(Strings.Home.linkChannelNone, Theme.Colors.textSecondary)]
        case .line:
            return [(Strings.Home.linkChannelLine, Color(red: 0.0, green: 0.72, blue: 0.36))]
        case .app:
            return [(Strings.Home.linkChannelApp, Theme.Colors.accent)]
        case .both:
            return [
                (Strings.Home.linkChannelLine, Color(red: 0.0, green: 0.72, blue: 0.36)),
                (Strings.Home.linkChannelApp, Theme.Colors.accent),
            ]
        }
    }
}

// MARK: - Reusable Badge Components

private struct LinkBadge: View {
    let label: String
    let color: Color

    var body: some View {
        Text(label)
            .font(.system(size: 10, weight: .bold))
            .padding(.horizontal, 6)
            .padding(.vertical, 3)
            .background(color.opacity(0.15))
            .foregroundColor(color)
            .cornerRadius(6)
    }
}

struct QueueStatusBadge: View {
    let status: QueueStatus

    var body: some View {
        Text(status.label)
            .font(.system(size: 11, weight: .semibold))
            .padding(.horizontal, Theme.Spacing.sm)
            .padding(.vertical, Theme.Spacing.xs)
            .background(status.color.opacity(0.15))
            .foregroundColor(status.color)
            .cornerRadius(Theme.Radius.sm)
    }
}
