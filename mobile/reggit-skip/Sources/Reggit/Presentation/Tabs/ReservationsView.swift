import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class ReservationsViewModel {
    var isLoading = true
    var errorMessage: String?
    var reservations: [ReservationItem] = []
    var showCreate = false
    var reservedAt = AppClock.now.addingTimeInterval(3600)
    var partySize = 2
    var notes = ""
    var courses: [ReservationCourseItem] = []
    var selectedCourseID: Int? = nil
    var isSubmitting = false
    var actionMessage: String?
    var pendingCancel: ReservationItem?

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    func load() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            reservations = try await apiClient.fetchMemberReservations()
            courses = (try? await apiClient.fetchReservationCourses()) ?? []
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func create() async {
        guard !isSubmitting else { return }
        isSubmitting = true
        actionMessage = nil
        defer { isSubmitting = false }
        if reservedAt.timeIntervalSince(AppClock.now) < 0 {
            actionMessage = "現在より後の日時を選択してください"
            return
        }
        do {
            try await apiClient.bookReservation(
                reservedAt: reservedAt,
                numberOfPeople: partySize,
                notes: notes.trimmed,
                courseId: selectedCourseID
            )
            showCreate = false
            notes = ""
            selectedCourseID = nil
            actionMessage = "予約を作成しました"
            await load()
        } catch {
            actionMessage = error.localizedDescription
        }
    }

    func cancel(_ item: ReservationItem) async {
        isSubmitting = true
        actionMessage = nil
        defer { isSubmitting = false }
        do {
            try await apiClient.cancelReservationBooking(id: item.id)
            actionMessage = "予約をキャンセルしました"
            await load()
        } catch {
            actionMessage = error.localizedDescription
        }
    }
}

struct ReservationsView: View {
    @State var viewModel: ReservationsViewModel
    @Environment(\.brandAccent) var accent

    init(apiClient: any ReggitAPIServing) {
        _viewModel = State(initialValue: ReservationsViewModel(apiClient: apiClient))
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                AppAsyncScreen(
                    isLoading: viewModel.isLoading && viewModel.reservations.isEmpty,
                    errorMessage: viewModel.reservations.isEmpty ? viewModel.errorMessage : nil,
                    isEmpty: viewModel.reservations.isEmpty,
                    emptyTitle: "予約はありません",
                    emptySubtitle: "ご希望の日時を選んで予約できます",
                    emptyIcon: "calendar",
                    onRetry: { Task { await viewModel.load() } },
                    skeleton: { ListCardSkeletonView(count: 3) }
                ) {
                    ScrollView {
                        LazyVStack(spacing: 0) {
                            ForEach(Array(viewModel.reservations.enumerated()), id: \.element.id) { index, item in
                                reservationCard(item)
                                if index < viewModel.reservations.count - 1 { Divider() }
                            }
                        }
                        .padding(16)
                    }
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationTitle("予約")
                .toolbar {
                    ToolbarItem(placement: .primaryAction) {
                        Button {
                            viewModel.actionMessage = nil
                            viewModel.showCreate = true
                        } label: {
                            Image(systemName: "plus")
                        }
                        .accessibilityLabel("新しく予約する")
                    }
                }
                .refreshable { await viewModel.load() }
                .navigationDestination(isPresented: Binding(
                    get: { viewModel.showCreate },
                    set: { viewModel.showCreate = $0 }
                )) {
                    createForm
                }
                .overlay {
                    AppToastHost(message: Binding(
                        get: { viewModel.showCreate ? nil : viewModel.actionMessage },
                        set: { viewModel.actionMessage = $0 }
                    ))
                }
                .confirmationDialog(
                    "この予約をキャンセルしますか？",
                    isPresented: Binding(
                        get: { viewModel.pendingCancel != nil },
                        set: { if !$0 { viewModel.pendingCancel = nil } }
                    ),
                    titleVisibility: .visible
                ) {
                    Button("キャンセルする", role: .destructive) {
                        if let item = viewModel.pendingCancel {
                            viewModel.pendingCancel = nil
                            Task { await viewModel.cancel(item) }
                        }
                    }
                    Button("戻る", role: .cancel) {
                        viewModel.pendingCancel = nil
                    }
                } message: {
                    Text(viewModel.pendingCancel?.reservationNumber ?? "")
                }
            }
        }
        .task { await viewModel.load() }
    }

    private func reservationCard(_ item: ReservationItem) -> some View {
        VStack(alignment: .leading, spacing: 14) {
            HStack {
                Label("ご予約", systemImage: "calendar")
                    .font(.subheadline.weight(.medium)).foregroundStyle(AppDesign.inkSecondary)
                Spacer()
                StatusBadge(text: item.statusLabel, emphasized: item.isCancellable)
            }
            Text(AppFormat.dateTime(item.reservedAt)).font(.title3.weight(.semibold)).foregroundStyle(AppDesign.ink)
            HStack(spacing: 20) {
                Label("\(item.numberOfPeople)名", systemImage: "person.2")
                Text("予約番号 \(item.reservationNumber)")
            }.font(.caption).foregroundStyle(AppDesign.inkSecondary)
            if let courseName = item.courseName, !courseName.isEmpty {
                Text(courseName).font(.subheadline.weight(.medium)).foregroundStyle(AppDesign.ink)
            }
            if !item.notes.isEmpty {
                Text(item.notes).font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
            if item.isCancellable {
                Button("予約をキャンセル", role: .destructive) {
                    viewModel.pendingCancel = item
                }
                .font(.subheadline).frame(minHeight: 44)
                .disabled(viewModel.isSubmitting)
            }
        }
        .padding(.vertical, 18)
    }

    private var createForm: some View {
        Form {
            Section("日時") {
                DatePicker(
                    "予約日時",
                    selection: Binding(
                        get: { viewModel.reservedAt },
                        set: { viewModel.reservedAt = $0 }
                    ),
                    displayedComponents: [.date, .hourAndMinute]
                )
            }
            Section("人数") {
                Stepper(
                    "\(viewModel.partySize)名",
                    value: Binding(
                        get: { viewModel.partySize },
                        set: { viewModel.partySize = $0 }
                    ),
                    in: 1 ... 10
                )
            }
            if !viewModel.courses.isEmpty {
                Section("コース") {
                    Picker("コース", selection: Binding(
                        get: { viewModel.selectedCourseID ?? 0 },
                        set: { viewModel.selectedCourseID = $0 == 0 ? nil : $0 }
                    )) {
                        Text("指定なし").tag(0)
                        ForEach(viewModel.courses) { course in
                            Text("\(course.name) · \(AppFormat.yen(course.price))").tag(course.id)
                        }
                    }
                }
            }
            Section("メモ") {
                TextField("要望など（任意）", text: Binding(
                    get: { viewModel.notes },
                    set: { viewModel.notes = $0 }
                ))
            }
        }
        .navigationTitle("新規予約")
        .tint(accent)
        .scrollContentBackground(.hidden)
        .background(AppDesign.canvas)
        .appBottomAction {
            AppActionBar {
                VStack(spacing: 10) {
                    if let message = viewModel.actionMessage {
                        Text(message).font(.footnote).foregroundStyle(AppDesign.danger)
                    }
                    AppPrimaryButton(title: "この内容で予約する", isLoading: viewModel.isSubmitting) {
                        Task { await viewModel.create() }
                    }
                }
            }
        }
    }
}
