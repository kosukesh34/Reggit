import SwiftUI

struct BoardView: View {
    @StateObject private var viewModel = BoardViewModel()
    @EnvironmentObject private var appState: AppState
    @State private var showSettings = false

    var body: some View {
        NavigationStack {
            ZStack {
                Color(red: 0.96, green: 0.97, blue: 0.96).ignoresSafeArea()
                content
            }
            .navigationTitle("キッチンモニター")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button("設定") {
                        appState.requestSettingsUnlock { showSettings = true }
                    }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { await viewModel.refresh() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
            }
            .sheet(isPresented: $showSettings) {
                SettingsView()
            }
            .task {
                await viewModel.refresh()
                viewModel.startPolling()
            }
            .onDisappear {
                viewModel.stopPolling()
            }
            .onReceive(NotificationCenter.default.publisher(for: .kitchenOrderPushReceived)) { _ in
                Task { await viewModel.refresh(silent: true) }
            }
        }
    }

    @ViewBuilder
    private var content: some View {
        if viewModel.orders.isEmpty && viewModel.isLoading {
            ProgressView("読み込み中…")
        } else if viewModel.orders.isEmpty {
            VStack(spacing: 12) {
                Image(systemName: "checkmark.circle")
                    .font(.system(size: 48))
                    .foregroundStyle(.green)
                Text("未完了の注文はありません")
                    .font(.title2.bold())
                if let error = viewModel.errorMessage {
                    Text(error).foregroundStyle(.red).multilineTextAlignment(.center)
                }
            }
            .padding()
        } else {
            ScrollView {
                LazyVGrid(columns: [GridItem(.adaptive(minimum: 280), spacing: 16)], spacing: 16) {
                    ForEach(viewModel.orders) { order in
                        OrderCardView(order: order) {
                            Task { await viewModel.advance(order) }
                        }
                    }
                }
                .padding(20)
            }
        }
    }
}

struct OrderCardView: View {
    let order: KitchenOrder
    let onAdvance: () -> Void
    @State private var horizontalOffset: CGFloat = 0

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Text(order.orderNumber)
                    .font(.system(size: 42, weight: .bold, design: .rounded))
                Spacer()
                Text(order.statusLabel)
                    .font(.subheadline.weight(.bold))
                    .padding(.horizontal, 10)
                    .padding(.vertical, 6)
                    .background(statusColor.opacity(0.15))
                    .foregroundStyle(statusColor)
                    .clipShape(Capsule())
            }
            if let createdAt = order.createdAt {
                Text(createdAt, style: .time)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            VStack(alignment: .leading, spacing: 6) {
                ForEach(Array(order.items.enumerated()), id: \.offset) { _, item in
                    HStack(alignment: .top) {
                        Text("×\(item.quantity)")
                            .font(.headline.monospacedDigit())
                            .frame(width: 36, alignment: .leading)
                        VStack(alignment: .leading, spacing: 2) {
                            Text(item.name).font(.body.weight(.semibold))
                            if let notes = item.notes, !notes.isEmpty {
                                Text(notes).font(.caption).foregroundStyle(.secondary)
                            }
                        }
                    }
                }
            }
            if let notes = order.notes, !notes.isEmpty {
                Text(notes)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            if let label = order.nextStatusLabel {
                Button(action: onAdvance) {
                    Text(label)
                        .font(.headline)
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 12)
                }
                .buttonStyle(.borderedProminent)
                .tint(statusColor)
                Text("← 左へスワイプでも「\(label)」")
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .frame(maxWidth: .infinity)
            }
        }
        .padding(16)
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 18, style: .continuous))
        .shadow(color: .black.opacity(0.06), radius: 10, y: 4)
        .offset(x: horizontalOffset)
        .gesture(
            DragGesture(minimumDistance: 24)
                .onChanged { value in
                    guard abs(value.translation.width) > abs(value.translation.height) else { return }
                    horizontalOffset = min(0, value.translation.width * 0.35)
                }
                .onEnded { value in
                    let shouldAdvance = value.translation.width < -90
                        && abs(value.translation.width) > abs(value.translation.height)
                        && order.nextStatusLabel != nil
                    withAnimation(.spring(response: 0.25)) { horizontalOffset = 0 }
                    if shouldAdvance { onAdvance() }
                }
        )
    }

    private var statusColor: Color {
        switch order.status {
        case "confirmed": return Color(red: 0.12, green: 0.45, blue: 0.38)
        case "preparing": return Color(red: 0.85, green: 0.45, blue: 0.1)
        case "ready": return Color(red: 0.15, green: 0.55, blue: 0.25)
        default: return .gray
        }
    }
}
