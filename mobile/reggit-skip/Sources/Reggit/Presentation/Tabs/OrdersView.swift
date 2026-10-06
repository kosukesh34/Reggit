import Observation
import SkipFuse
import SwiftUI

@MainActor
@Observable
final class OrdersViewModel {
    var isLoading = true
    var errorMessage: String?
    var orders: [OrderSnapshot] = []
    var reloadRevision = 0

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    func load(force: Bool = false) async {
        if !force, let cached = SecondaryScreenCache.orders {
            orders = cached
            isLoading = false
            return
        }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            orders = try await apiClient.fetchOrders()
            SecondaryScreenCache.orders = orders
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct OrdersView: View {
    @State var viewModel: OrdersViewModel
    @Environment(\.appButtonColors) var buttonColors
    @Environment(\.brandAccent) var accent
    @State var selectedOrder: OrderSnapshot?
    @State var selectedSegment = 0
    @State var reorderOrder: OrderSnapshot?
    let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing, reloadRevision: Int = 0) {
        _viewModel = State(initialValue: OrdersViewModel(apiClient: apiClient))
        _viewModel.wrappedValue.reloadRevision = reloadRevision
        self.apiClient = apiClient
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                AppAsyncScreen(
                    isLoading: viewModel.isLoading && viewModel.orders.isEmpty,
                    errorMessage: viewModel.orders.isEmpty ? viewModel.errorMessage : nil,
                    isEmpty: viewModel.orders.isEmpty,
                    emptyTitle: "注文履歴はありません",
                    emptySubtitle: "注文するとここに表示されます",
                    emptyIcon: "list.clipboard",
                    onRetry: { Task { await viewModel.load() } },
                    skeleton: { OrdersSkeletonView() }
                ) {
                    List {
                            AppSegmentedControl(titles: ["すべて", "進行中", "完了・キャンセル"], selectedIndex: $selectedSegment)
                                .padding(.bottom, 14)
                            let visible = viewModel.orders.filter { selectedSegment == 0 || (selectedSegment == 1 ? $0.isActive : !$0.isActive) }
                            if visible.isEmpty {
                                AppEmptyState(title: "該当する注文はありません", subtitle: "注文状況はここで確認できます", systemImage: "bag")
                            }
                            ForEach(visible) { order in
                                Button {
                                    selectedOrder = order
                                } label: {
                                    OrderSummaryRow(order: order)
                                        .padding(.vertical, 16)
                                }
                                .buttonStyle(.plain)
                                .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                                    Button {
                                        reorderOrder = order
                                    } label: {
                                        Label("再注文", systemImage: "arrow.clockwise")
                                    }
                                    .tint(accent)
                                }
                            }
                    }
                    .listStyle(.plain)
                    .scrollContentBackground(.hidden)
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationBarHidden(true)
                .toolbar(.hidden, for: .navigationBar)
                .refreshable { await viewModel.load(force: true) }
                .navigationDestination(item: $selectedOrder) { order in
                    OrderStatusView(order: order)
                        .environment(\.brandAccent, accent)
                        .environment(\.appButtonColors, buttonColors)
                        .headerBackDestination()
                }
                .navigationDestination(item: $reorderOrder) { order in
                    ProductsView(apiClient: apiClient, reorderOrder: order)
                        .environment(\.brandAccent, accent)
                        .environment(\.appButtonColors, buttonColors)
                        .headerBackDestination()
                }
            }
        }
        .task { await viewModel.load() }
        .task(id: viewModel.reloadRevision) {
            guard viewModel.reloadRevision > 0 else { return }
            await viewModel.load(force: true)
        }
    }
}
