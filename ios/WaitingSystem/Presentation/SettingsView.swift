import SwiftUI

// MARK: - Settings Sheet Wrapper

struct SettingsSheetView: View {
    @EnvironmentObject private var appState: AppState
    @StateObject private var viewModel = SettingsViewModel()
    let queueListEntries: [QueueListEntry]
    let onSelectPrinter: (@escaping () -> Void) -> Void

    var body: some View {
        SettingsView(
            viewModel: viewModel,
            queueListEntries: queueListEntries,
            onSelectPrinter: onSelectPrinter,
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
                    QueueEntryRow(entry: entry)
                }
            }
        } header: {
            Label(Strings.Home.waitingListTitle, systemImage: "list.number")
        }
    }

    // MARK: - Call

    private var callSection: some View {
        Section(header: Text(Strings.Settings.callSection)) {
            Button {
                Task { await viewModel.callNext() }
            } label: {
                HStack {
                    Image(systemName: "bell.badge.fill")
                        .foregroundColor(Theme.Colors.accent)
                    Text(viewModel.isCallingNext ? Strings.Settings.callingButton : Strings.Settings.callNextButton)
                }
            }
            .disabled(viewModel.isCallingNext)

            if let message = viewModel.callNextMessage {
                Text(message)
                    .font(.footnote)
                    .foregroundColor(Theme.Colors.textSecondary)
            }
        }
    }

    // MARK: - Server

    private var serverSection: some View {
        Section(header: Text(Strings.Settings.serverSection)) {
            TextField(Strings.Settings.baseURLPlaceholder, text: $viewModel.settings.baseURL)
                .keyboardType(.URL)
                .textInputAutocapitalization(.never)
                .disableAutocorrection(true)
            TextField(Strings.Settings.liffURLPlaceholder, text: $viewModel.settings.liffURL)
                .keyboardType(.URL)
                .textInputAutocapitalization(.never)
                .disableAutocorrection(true)
            TextField(Strings.Settings.projectKeyPlaceholder, text: $viewModel.settings.projectKey)
                .textInputAutocapitalization(.never)
                .disableAutocorrection(true)
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

// MARK: - Queue Entry Row (SRP: display-only component)

private struct QueueEntryRow: View {
    let entry: QueueListEntry

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

            Spacer()

            HStack(spacing: Theme.Spacing.xs) {
                if entry.memberLinked {
                    LinkBadge(label: Strings.Home.memberLinked, color: Theme.Colors.success)
                }
                if entry.lineLinked {
                    LinkBadge(label: Strings.Home.lineLinkedShort, color: Color(red: 0.0, green: 0.72, blue: 0.36))
                }
            }

            QueueStatusBadge(status: QueueStatus(raw: entry.status))
        }
        .padding(.vertical, Theme.Spacing.xs)
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
