import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class QueueViewModel {
    var isLoading = true
    var errorMessage: String?
    var snapshot: CurrentQueueSnapshot?
    var partySize = 2
    var isSubmitting = false
    var actionMessage: String?
    var showCancelConfirm = false

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var hasActiveTicket: Bool {
        guard let entry = snapshot?.entry else { return false }
        return entry.status != "cancelled" && entry.status != "entered"
    }

    func load() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do { snapshot = try await apiClient.fetchCurrentQueueStatus() }
        catch { errorMessage = error.localizedDescription }
    }

    func refreshQuietly() async {
        if let latest = try? await apiClient.fetchCurrentQueueStatus() {
            snapshot = latest
        }
    }

    func createTicket() async {
        guard !isSubmitting else { return }
        isSubmitting = true
        actionMessage = nil
        defer { isSubmitting = false }
        do {
            let entry = try await apiClient.joinQueue(partySize: partySize)
            snapshot = CurrentQueueSnapshot(
                entry: entry,
                waitingCount: entry.waitingCount,
                estimatedWaitMinutes: entry.estimatedWaitMinutes,
                message: nil
            )
            actionMessage = "整理券を発行しました"
        } catch {
            actionMessage = error.localizedDescription
        }
    }

    func cancelTicket() async {
        guard let id = snapshot?.entry?.id else { return }
        isSubmitting = true
        actionMessage = nil
        defer { isSubmitting = false }
        do {
            try await apiClient.cancelQueueEntry(id: id)
            await load()
            actionMessage = "キャンセルしました"
        } catch {
            actionMessage = error.localizedDescription
        }
    }

    func applyQueueToken(_ token: String) async {
        do {
            let entry = try await apiClient.fetchQueueByToken(token)
            snapshot = CurrentQueueSnapshot(
                entry: entry,
                waitingCount: entry.waitingCount,
                estimatedWaitMinutes: entry.estimatedWaitMinutes,
                message: nil
            )
        } catch {
            actionMessage = error.localizedDescription
        }
    }
}

struct QueueView: View {
    @State var viewModel: QueueViewModel
    @Environment(\.brandAccent) var accent
    let pendingQueueToken: String?
    let onConsumePendingToken: () -> Void

    init(
        apiClient: any ReggitAPIServing,
        pendingQueueToken: String?,
        onConsumePendingToken: @escaping () -> Void
    ) {
        _viewModel = State(initialValue: QueueViewModel(apiClient: apiClient))
        self.pendingQueueToken = pendingQueueToken
        self.onConsumePendingToken = onConsumePendingToken
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                Group {
                    if viewModel.isLoading && viewModel.snapshot == nil {
                        QueueSkeletonView()
                    } else if let error = viewModel.errorMessage, viewModel.snapshot == nil {
                        AppEmptyState(
                            title: "読み込めませんでした",
                            subtitle: error,
                            systemImage: "wifi.slash",
                            actionTitle: "再読み込み",
                            action: { Task { await viewModel.load() } }
                        )
                    } else if let entry = viewModel.snapshot?.entry, viewModel.hasActiveTicket {
                        ScrollView { activeTicket(entry) }
                    } else {
                        ScrollView { joinForm }
                    }
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .refreshable { await viewModel.load() }
                .overlay {
                    AppToastHost(message: Binding(
                        get: { viewModel.actionMessage },
                        set: { viewModel.actionMessage = $0 }
                    ))
                }
                .confirmationDialog(
                    "整理券をキャンセルしますか？",
                    isPresented: Binding(
                        get: { viewModel.showCancelConfirm },
                        set: { viewModel.showCancelConfirm = $0 }
                    ),
                    titleVisibility: .visible
                ) {
                    Button("キャンセルする", role: .destructive) {
                        Task { await viewModel.cancelTicket() }
                    }
                    Button("戻る", role: .cancel) {}
                }
            }
        }
        .task {
            await viewModel.load()
            if let pendingQueueToken, !pendingQueueToken.isEmpty {
                await viewModel.applyQueueToken(pendingQueueToken)
                onConsumePendingToken()
            }
        }
        .task {
            while !Task.isCancelled {
                try? await Task.sleep(nanoseconds: 8_000_000_000)
                if viewModel.hasActiveTicket {
                    await viewModel.refreshQuietly()
                }
            }
        }
    }

    private func activeTicket(_ entry: QueueEntry) -> some View {
        VStack(spacing: 24) {
            Text("こちらの番号でお呼びします")
                .font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
            AppCard {
                VStack(spacing: 0) {
                    VStack(spacing: 14) {
                        Label("順番待ち整理券", systemImage: "ticket.fill")
                            .font(.subheadline).foregroundStyle(.white.opacity(0.7))
                        Text(entry.queueNumber)
                            .font(.system(size: 64, weight: .semibold, design: .rounded))
                            .minimumScaleFactor(0.6).lineLimit(1)
                        Text(entry.statusLabel).font(.subheadline.weight(.medium))
                    }
                    .foregroundStyle(.white)
                    .frame(maxWidth: .infinity).padding(28)
                    .background(AppDesign.coral)
                    HStack(spacing: 24) {
                        metric("ご利用人数", "\(entry.partySize)名")
                        Spacer(minLength: 0)
                        metric("待ち組数", "\(max(entry.waitingCount, 0))組")
                        if let minutes = entry.estimatedWaitMinutes {
                            Spacer(minLength: 0)
                            metric("待ち時間の目安", "\(minutes)分")
                        }
                    }.padding(24)
                }
            }
            Text("待ち状況は自動で更新されます。表示される時間は目安です。")
                .font(.caption).foregroundStyle(AppDesign.inkSecondary)
                .multilineTextAlignment(.center).lineSpacing(4)
            Button("受付をキャンセル", role: .destructive) { viewModel.showCancelConfirm = true }
                .font(.subheadline).frame(minHeight: 44)
                .disabled(viewModel.isSubmitting)
        }.padding(20)
    }

    private var joinForm: some View {
        VStack(spacing: 24) {
            Spacer(minLength: 32)
            VStack(spacing: 10) {
                FontAwesomeIcon(systemName: "person.2")
                    .foregroundStyle(AppDesign.coral)
                    .frame(width: 76, height: 76)
                    .background(AppDesign.coral.opacity(0.1)).clipShape(RoundedRectangle(cornerRadius: 24))
                Text("ご来店の受付")
                    .font(.title2.weight(.semibold))
                if let waiting = viewModel.snapshot?.waitingCount {
                    Text("いま \(waiting) 組が待っています")
                        .foregroundStyle(AppDesign.inkSecondary)
                } else {
                    Text("ご利用人数を選んで受付できます")
                        .foregroundStyle(AppDesign.inkSecondary)
                }
            }

            AppCard {
                VStack(spacing: 12) {
                    Text("人数")
                        .font(.system(size: 13, weight: .semibold))
                        .foregroundStyle(AppDesign.inkSecondary)
                    HStack(spacing: 24) {
                        Button {
                            if viewModel.partySize > 1 { viewModel.partySize -= 1 }
                        } label: {
                            FontAwesomeIcon(systemName: "minus.circle.fill")
                                .frame(width: 48, height: 48)
                                .foregroundStyle(AppDesign.inkSecondary)
                        }
                        .buttonStyle(.plain)
                        .disabled(viewModel.partySize <= 1)
                        .accessibilityLabel("人数を減らす")
                        Text("\(viewModel.partySize)")
                            .font(.system(size: 48, weight: .bold, design: .rounded))
                            .frame(minWidth: 60)
                        Button {
                            if viewModel.partySize < 20 { viewModel.partySize += 1 }
                        } label: {
                            FontAwesomeIcon(systemName: "plus.circle.fill")
                                .frame(width: 48, height: 48)
                                .foregroundStyle(accent)
                        }
                        .buttonStyle(.plain)
                        .disabled(viewModel.partySize >= 20)
                        .accessibilityLabel("人数を増やす")
                    }
                }
                .padding(24)
                .frame(maxWidth: .infinity)
            }
            .padding(.horizontal, 24)

            AppPrimaryButton(
                title: "整理券を発行",
                isLoading: viewModel.isSubmitting
            ) {
                Task { await viewModel.createTicket() }
            }
            .padding(.horizontal, 24)

            Spacer()
        }
    }

    private func metric(_ label: String, _ value: String) -> some View {
        VStack(spacing: 4) {
            Text(label)
                .font(.system(size: 12, weight: .medium))
                .foregroundStyle(AppDesign.inkSecondary)
            Text(value)
                .font(.system(size: 18, weight: .bold))
        }
    }
}
