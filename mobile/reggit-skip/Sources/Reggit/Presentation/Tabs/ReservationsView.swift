import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class ReservationsViewModel {
    var isLoading = true
    var errorMessage: String?
    var reservations: [ReservationItem] = []
    var showForm = false
    /// 変更中の予約。nil なら新規予約
    var editing: ReservationItem?
    var partySize = 2
    var selectedDate = ""
    var selectedTime = ""
    var notes = ""
    var selectedCourseID: Int? = nil
    var availability: ReservationAvailability?
    var isLoadingAvailability = false
    var availabilityError: String?
    var isSubmitting = false
    var actionMessage: String?
    var pendingCancel: ReservationItem?

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var maxPartySize: Int { availability?.maxPartySize ?? 10 }

    var slots: [String] {
        availability?.dates.first { $0.date == selectedDate }?.timeSlots ?? []
    }

    /// 人数や日付を変えて選べなくなった時刻は選択していない扱いにする
    var validTime: String? {
        slots.contains(selectedTime) ? selectedTime : nil
    }

    func load(force: Bool = false) async {
        if !force, let cached = SecondaryScreenCache.reservations {
            reservations = cached
            isLoading = false
            return
        }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            reservations = try await apiClient.fetchMemberReservations()
            SecondaryScreenCache.reservations = reservations
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func openForm(editing item: ReservationItem? = nil) {
        let current = ReservationStoreDateTime(iso: item?.reservedAt)
        editing = item
        partySize = item?.numberOfPeople ?? 2
        selectedDate = current.date
        selectedTime = current.time
        notes = item?.notes ?? ""
        selectedCourseID = item?.courseId
        availability = nil
        actionMessage = nil
        showForm = true
    }

    func loadAvailability() async {
        isLoadingAvailability = true
        availabilityError = nil
        defer { isLoadingAvailability = false }
        do {
            availability = try await apiClient.fetchReservationAvailability(numberOfPeople: partySize, excludeReservationId: editing?.id)
        } catch {
            availabilityError = error.localizedDescription
        }
    }

    func submit() async {
        guard !isSubmitting else { return }
        guard let time = validTime else {
            actionMessage = "日付と時間を選択してください"
            return
        }
        isSubmitting = true
        actionMessage = nil
        defer { isSubmitting = false }
        let reservedAt = ReservationStoreDateTime.requestValue(date: selectedDate, time: time)
        do {
            if let editing {
                try await apiClient.updateReservationBooking(id: editing.id, reservedAt: reservedAt, numberOfPeople: partySize, notes: notes.trimmed, courseId: selectedCourseID)
            } else {
                try await apiClient.bookReservation(reservedAt: reservedAt, numberOfPeople: partySize, notes: notes.trimmed, courseId: selectedCourseID)
            }
            showForm = false
            actionMessage = editing == nil ? "予約を作成しました" : "予約を変更しました"
            await load(force: true)
        } catch {
            actionMessage = error.localizedDescription
            // 満席などで選べなくなった枠を取り直す
            await loadAvailability()
        }
    }

    func cancel(_ item: ReservationItem) async {
        isSubmitting = true
        actionMessage = nil
        defer { isSubmitting = false }
        do {
            try await apiClient.cancelReservationBooking(id: item.id)
            actionMessage = "予約をキャンセルしました"
            await load(force: true)
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
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .toolbar {
                    ToolbarItem(placement: .primaryAction) {
                        Button {
                            viewModel.openForm()
                        } label: {
                            FontAwesomeIcon(systemName: "plus")
                        }
                        .accessibilityLabel("新しく予約する")
                    }
                }
                .refreshable { await viewModel.load(force: true) }
                .navigationDestination(isPresented: Binding(
                    get: { viewModel.showForm },
                    set: { viewModel.showForm = $0 }
                )) {
                    bookingForm.headerBackDestination()
                }
                .overlay {
                    AppToastHost(message: Binding(
                        get: { viewModel.showForm ? nil : viewModel.actionMessage },
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
                FontAwesomeLabel("ご予約", systemImage: "calendar")
                    .font(.subheadline.weight(.medium)).foregroundStyle(AppDesign.inkSecondary)
                Spacer()
                StatusBadge(text: item.statusLabel, emphasized: item.isCancellable)
            }
            Text(AppFormat.dateTime(item.reservedAt)).font(.title3.weight(.semibold)).foregroundStyle(AppDesign.ink)
            HStack(spacing: 20) {
                FontAwesomeLabel("\(item.numberOfPeople)名", systemImage: "person.2")
                Text("予約番号 \(item.reservationNumber)")
            }.font(.caption).foregroundStyle(AppDesign.inkSecondary)
            if let courseName = item.courseName, !courseName.isEmpty {
                Text(courseName).font(.subheadline.weight(.medium)).foregroundStyle(AppDesign.ink)
            }
            if !item.notes.isEmpty {
                Text(item.notes).font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
            if let note = item.changeNote {
                Text(note).font(.caption).foregroundStyle(AppDesign.inkSecondary)
            }
            if item.isCancellable {
                HStack(spacing: 20) {
                    Button("予約を変更") {
                        viewModel.openForm(editing: item)
                    }
                    .foregroundStyle(accent)
                    Button("予約をキャンセル", role: .destructive) {
                        viewModel.pendingCancel = item
                    }
                }
                .font(.subheadline).frame(minHeight: 44)
                .disabled(viewModel.isSubmitting)
            }
        }
        .padding(.vertical, 18)
    }

    private var bookingForm: some View {
        Form {
            Section("人数") {
                Stepper(
                    "\(viewModel.partySize)名",
                    value: Binding(
                        get: { viewModel.partySize },
                        set: { viewModel.partySize = $0 }
                    ),
                    in: 1 ... max(viewModel.maxPartySize, viewModel.partySize)
                )
            }
            Section("日付") {
                if viewModel.isLoadingAvailability && viewModel.availability == nil {
                    ProgressView().frame(maxWidth: .infinity, minHeight: 44)
                } else if let message = viewModel.availabilityError {
                    Button("空き状況を読み込めませんでした。再読み込み") {
                        Task { await viewModel.loadAvailability() }
                    }
                    .accessibilityHint(Text(message))
                } else if let dates = viewModel.availability?.dates, !dates.isEmpty {
                    ScrollView(.horizontal, showsIndicators: false) {
                        HStack(spacing: 8) {
                            ForEach(dates) { option in
                                choiceChip(option.display, isOn: option.date == viewModel.selectedDate) {
                                    viewModel.selectedDate = option.date
                                }
                            }
                        }
                    }
                } else {
                    Text("\(viewModel.partySize)名で予約できる日がありません").font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
                }
            }
            if !viewModel.selectedDate.isEmpty && viewModel.availability != nil {
                Section("時間") {
                    if viewModel.slots.isEmpty {
                        Text("この日は\(viewModel.partySize)名で予約できる時間がありません").font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
                    } else {
                        LazyVGrid(columns: Array(repeating: GridItem(.flexible(), spacing: 8), count: 4), spacing: 8) {
                            ForEach(viewModel.slots, id: \.self) { slot in
                                choiceChip(slot, isOn: slot == viewModel.validTime, fill: true) {
                                    viewModel.selectedTime = slot
                                }
                            }
                        }
                    }
                }
            }
            if let courses = viewModel.availability?.courses, !courses.isEmpty {
                Section("コース") {
                    Picker("コース", selection: Binding(
                        get: { viewModel.selectedCourseID ?? 0 },
                        set: { viewModel.selectedCourseID = $0 == 0 ? nil : $0 }
                    )) {
                        Text("指定なし").tag(0)
                        ForEach(courses) { course in
                            Text("\(course.name) · \(AppFormat.yen(course.price))").tag(course.id)
                        }
                    }
                }
            }
            Section("メモ") {
                TextField("要望など（任意）", text: Binding(
                    get: { viewModel.notes },
                    set: { viewModel.notes = String($0.prefix(500)) }
                ))
            }
        }
        .navigationTitle("")
        .tint(accent)
        .scrollContentBackground(.hidden)
        .background(AppDesign.canvas)
        .task(id: viewModel.partySize) { await viewModel.loadAvailability() }
        .appBottomAction {
            AppActionBar {
                VStack(spacing: 10) {
                    if let message = viewModel.actionMessage {
                        Text(message).font(.footnote).foregroundStyle(AppDesign.danger)
                    }
                    AppPrimaryButton(
                        title: viewModel.editing == nil ? "この内容で予約する" : "この内容に変更する",
                        isLoading: viewModel.isSubmitting,
                        enabled: viewModel.validTime != nil
                    ) {
                        Task { await viewModel.submit() }
                    }
                }
            }
        }
    }

    private func choiceChip(_ title: String, isOn: Bool, fill: Bool = false, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            Text(title)
                .font(.system(size: 14, weight: .semibold))
                .foregroundStyle(isOn ? Color.white : AppDesign.ink)
                .padding(.horizontal, 14)
                .frame(maxWidth: fill ? .infinity : nil, minHeight: 44)
                .background(isOn ? accent : AppDesign.surfaceMuted)
                .clipShape(Capsule())
        }
        .buttonStyle(.plain)
        .accessibilityAddTraits(isOn ? .isSelected : [])
    }
}
