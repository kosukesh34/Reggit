import Observation
import SkipFuse
import SwiftUI

struct CartLine: Identifiable, Equatable {
    let id: Int
    let product: ProductItem
    let quantity: Int
    let optionIds: [Int]
    var optionItems: [ProductOptionItem] { product.optionGroups.flatMap(\.options).filter { optionIds.contains($0.id) } }
    var unitPrice: Int { product.price + optionItems.reduce(0) { $0 + $1.priceDelta } }
    var lineTotal: Int { unitPrice * quantity }
}

@MainActor
@Observable
final class ProductsViewModel {
    var isLoading = true
    var errorMessage: String?
    var products: [ProductItem] = []
    var cart: [Int: Int] = [:]
    var selectedOptions: [Int: [Int]] = [:]
    var isSubmitting = false
    var actionMessage: String?
    var selectedCategory = ""
    var searchText = ""
    var showConfirm = false
    var activeOrder: OrderSnapshot?
    var paymentMethod = "stripe"
    var checkoutURL: URL?

    private let apiClient: any ReggitAPIServing

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var mediaBaseURL: String { apiClient.baseURL }

    var categories: [String] {
        var seen = Set<String>()
        return products.map(\.category).filter { !$0.isEmpty && seen.insert($0).inserted }
    }

    var filteredProducts: [ProductItem] {
        products.filter { product in
            (selectedCategory.isEmpty || product.category == selectedCategory)
                && (searchText.trimmed.isEmpty || (product.name + " " + product.description + " " + product.category).localizedCaseInsensitiveContains(searchText.trimmed))
        }
    }

    var cartLines: [CartLine] {
        products.compactMap { product in
            guard let qty = cart[product.id], qty > 0 else { return nil }
            return CartLine(id: product.id, product: product, quantity: qty, optionIds: selectedOptions[product.id] ?? [])
        }
    }

    var cartCount: Int { cart.values.reduce(0, +) }
    var cartTotal: Int {
        cartLines.reduce(0) { $0 + $1.lineTotal }
    }

    func load() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            products = try await apiClient.fetchProducts().filter(\.isAvailable)
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func addToCart(_ product: ProductItem) { cart[product.id, default: 0] += 1 }

    func removeFromCart(_ product: ProductItem) {
        guard let count = cart[product.id], count > 0 else { return }
        if count == 1 { cart.removeValue(forKey: product.id) }
        else { cart[product.id] = count - 1 }
    }

    func placeOrder() async -> URL? {
        guard !isSubmitting else { return nil }
        let lines = cartLines.map { OrderLineRequest(productId: $0.product.id, quantity: $0.quantity, optionIds: $0.optionIds) }
        guard !lines.isEmpty else {
            actionMessage = "カートが空です"
            return nil
        }
        isSubmitting = true
        actionMessage = nil
        defer { isSubmitting = false }
        do {
            let order = try await apiClient.createOrder(items: lines, paymentMethod: paymentMethod, notes: nil)
            var paymentURL: URL?
            if paymentMethod == "stripe" {
                do {
                    let checkout = try await apiClient.createPaymentCheckout(orderId: order.id)
                    guard let url = URL(string: checkout.checkoutURL) else {
                        throw ReggitAPIError.invalidURL
                    }
                    paymentURL = url
                    checkoutURL = url
                } catch {
                    try? await apiClient.cancelUnpaidOrder(id: order.id)
                    throw error
                }
            }
            cart.removeAll()
            showConfirm = false
            activeOrder = order
            return paymentURL
        } catch {
            actionMessage = error.localizedDescription
            return nil
        }
    }

    func refreshActiveOrder() async {
        guard let id = activeOrder?.id else { return }
        if let latest = try? await apiClient.fetchOrder(id: id) {
            activeOrder = latest
        }
    }

    func fetchOrder(id: Int) async throws -> OrderSnapshot {
        try await apiClient.fetchOrder(id: id)
    }
}

struct ProductsView: View {
    @State var viewModel: ProductsViewModel
    @Environment(\.brandAccent) var accent
    @Environment(\.openURL) var openURL
    @State var selectedProduct: ProductItem?
    @State var draftOptionIds: [Int] = []
    let paymentReturnOrderID: Int?
    let paymentReturnRevision: Int

    init(apiClient: any ReggitAPIServing, paymentReturnOrderID: Int? = nil, paymentReturnRevision: Int = 0) {
        _viewModel = State(initialValue: ProductsViewModel(apiClient: apiClient))
        self.paymentReturnOrderID = paymentReturnOrderID
        self.paymentReturnRevision = paymentReturnRevision
    }

    var body: some View {
        SkipRefreshing {
            NavigationStack {
                AppAsyncScreen(
                    isLoading: viewModel.isLoading,
                    errorMessage: viewModel.errorMessage,
                    isEmpty: viewModel.products.isEmpty,
                    emptyTitle: "商品・メニューがありません",
                    emptySubtitle: "商品やサービスが登録されるとここに表示されます",
                    emptyIcon: "bag",
                    onRetry: { Task { await viewModel.load() } },
                    skeleton: { ProductsSkeletonView() }
                ) {
                    ScrollView {
                        VStack(alignment: .leading, spacing: 16) {
                            HStack(spacing: 10) {
                                Image(systemName: "magnifyingglass").foregroundStyle(AppDesign.inkSecondary)
                                TextField("商品・サービスを検索", text: Binding(get: { viewModel.searchText }, set: { viewModel.searchText = $0 }))
                                    .frame(minHeight: 44)
                                if !viewModel.searchText.isEmpty {
                                    Button { viewModel.searchText = "" } label: { Image(systemName: "xmark.circle.fill") }
                                        .frame(width: 44, height: 44)
                                        .accessibilityLabel("検索をクリア")
                                }
                            }
                            .padding(.horizontal, 14)
                            .background(AppDesign.surfaceMuted)
                            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
                            .padding(.horizontal, 20)
                            if viewModel.filteredProducts.isEmpty {
                                AppEmptyState(title: "見つかりませんでした", subtitle: "別のキーワードやカテゴリをお試しください", systemImage: "magnifyingglass")
                            }
                            if !viewModel.categories.isEmpty {
                                CategoryChipRow(
                                    categories: viewModel.categories,
                                    selected: Binding(
                                        get: { viewModel.selectedCategory },
                                        set: { viewModel.selectedCategory = $0 }
                                    )
                                )
                                .padding(.top, 8)
                            }
                            LazyVStack(spacing: 0) {
                                ForEach(Array(viewModel.filteredProducts.enumerated()), id: \.element.id) { index, product in
                                    productCard(product)
                                    if index < viewModel.filteredProducts.count - 1 {
                                        Divider()
                                    }
                                }
                            }
                            .padding(.horizontal, 16)
                            .padding(.bottom, 20)
                        }
                    }
                }
                .background(AppDesign.canvas.ignoresSafeArea())
                .navigationTitle("商品・サービス")
                .refreshable { await viewModel.load() }
                .appBottomAction {
                    if viewModel.cartCount > 0 && viewModel.activeOrder == nil {
                        cartBar
                    }
                }
                .overlay {
                    AppToastHost(message: Binding(
                        get: { viewModel.showConfirm ? nil : viewModel.actionMessage },
                        set: { viewModel.actionMessage = $0 }
                    ))
                }
                .sheet(item: $selectedProduct) { product in
                    productDetail(product)
                }
                .navigationDestination(isPresented: Binding(
                    get: { viewModel.showConfirm },
                    set: { viewModel.showConfirm = $0 }
                )) {
                    OrderConfirmView(
                        lines: viewModel.cartLines,
                        total: viewModel.cartTotal,
                        paymentMethod: Binding(
                            get: { viewModel.paymentMethod },
                            set: { viewModel.paymentMethod = $0 }
                        ),
                        isSubmitting: viewModel.isSubmitting,
                        errorMessage: viewModel.actionMessage,
                        onConfirm: {
                            Task {
                                if let url = await viewModel.placeOrder() {
                                    openURL(url)
                                }
                            }
                        }
                    )
                    .environment(\.brandAccent, accent)
                }
                .navigationDestination(item: Binding(
                    get: { viewModel.activeOrder },
                    set: { viewModel.activeOrder = $0 }
                )) { order in
                    OrderStatusView(
                        order: order,
                        onContinuePayment: viewModel.checkoutURL.map { url in
                            { openURL(url) }
                        }
                    )
                        .environment(\.brandAccent, accent)
                    .task(id: order.id) {
                        while !Task.isCancelled {
                            await viewModel.refreshActiveOrder()
                            if let latest = viewModel.activeOrder, !latest.isActive { break }
                            try? await Task.sleep(nanoseconds: 2_500_000_000)
                        }
                    }
                }
            }
        }
        .task { await viewModel.load() }
        .task(id: paymentReturnRevision) {
            guard let orderID = paymentReturnOrderID else { return }
            if let order = try? await viewModel.fetchOrder(id: orderID) {
                viewModel.activeOrder = order
                viewModel.showConfirm = false
                viewModel.checkoutURL = nil
            }
        }
    }

    private func productCard(_ product: ProductItem) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Button { openProduct(product) } label: {
                HStack(alignment: .top, spacing: 16) {
                    VStack(alignment: .leading, spacing: 7) {
                        if !product.category.isEmpty {
                            Text(product.category).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                        }
                        Text(product.name).font(.headline).foregroundStyle(AppDesign.ink)
                            .fixedSize(horizontal: false, vertical: true)
                        if !product.description.isEmpty {
                            Text(product.description).font(.caption).foregroundStyle(AppDesign.inkSecondary).lineLimit(2)
                        }
                    }
                    .frame(maxWidth: .infinity, alignment: .leading)
                    RemoteImageView(urlString: product.imageURL, baseURL: viewModel.mediaBaseURL, contentMode: .fill, cornerRadius: AppDesign.radiusM)
                        .frame(width: 96, height: 96).clipped()
                }.multilineTextAlignment(.leading)
            }
            .buttonStyle(.plain)
            .accessibilityLabel(Text("\(product.name)の詳細"))
            HStack {
                Text(AppFormat.yen(product.price)).font(.headline).foregroundStyle(AppDesign.ink)
                Spacer()
                QtyStepper(
                    quantity: viewModel.cart[product.id] ?? 0,
                    onMinus: { viewModel.removeFromCart(product) },
                    onPlus: {
                        if product.optionGroups.isEmpty { viewModel.addToCart(product) }
                        else { openProduct(product) }
                    }
                )
            }
        }
        .padding(.vertical, 16)
    }

    private func productDetail(_ product: ProductItem) -> some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 24) {
                    if product.imageURL != nil {
                        RemoteImageView(urlString: product.imageURL, baseURL: viewModel.mediaBaseURL, contentMode: .fit, cornerRadius: AppDesign.radiusXL)
                            .frame(height: 260)
                    }
                    VStack(alignment: .leading, spacing: 12) {
                        Text(product.category).font(.caption.weight(.medium)).foregroundStyle(AppDesign.inkSecondary)
                        Text(product.name).font(.title.weight(.bold)).foregroundStyle(AppDesign.ink)
                        Text(AppFormat.yen(product.price)).font(.title2.weight(.semibold))
                    }
                    if !product.description.isEmpty {
                        Divider()
                        Text(product.description).font(.body).foregroundStyle(AppDesign.inkSecondary)
                            .lineSpacing(5)
                    }
                    ForEach(product.optionGroups) { group in
                        VStack(alignment: .leading, spacing: 10) {
                            HStack {
                                Text(group.name).font(.headline)
                                if group.isRequired { Text("必須").font(.caption2).foregroundStyle(AppDesign.danger) }
                            }
                            ForEach(group.options) { option in
                                Button { toggleOption(option, in: group) } label: {
                                    HStack {
                                        Image(systemName: draftOptionIds.contains(option.id) ? "checkmark.circle.fill" : "circle")
                                            .foregroundStyle(accent)
                                        Text(option.name).foregroundStyle(AppDesign.ink)
                                        Spacer()
                                        if option.priceDelta > 0 { Text("+\(AppFormat.yen(option.priceDelta))").foregroundStyle(AppDesign.inkSecondary) }
                                    }
                                    .frame(minHeight: 44)
                                }
                                .buttonStyle(.plain)
                            }
                        }
                        .padding(14)
                        .background(AppDesign.surface)
                        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
                    }
                }.padding(24)
            }
            .background(AppDesign.canvas)
            .navigationTitle("商品・サービスの詳細")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("閉じる") { selectedProduct = nil }
                }
            }
            .appBottomAction {
                AppActionBar {
                    AppPrimaryButton(
                        title: "カートに追加 · \(AppFormat.yen(productPrice(product)))",
                        enabled: requiredOptionsSelected(product)
                    ) {
                        viewModel.selectedOptions[product.id] = draftOptionIds
                        viewModel.addToCart(product)
                        selectedProduct = nil
                    }
                }
            }
        }
    }

    private func openProduct(_ product: ProductItem) {
        draftOptionIds = viewModel.selectedOptions[product.id] ?? []
        selectedProduct = product
    }

    private func toggleOption(_ option: ProductOptionItem, in group: ProductOptionGroupItem) {
        if group.selectionType == "single" {
            let groupIDs = group.options.map(\.id)
            draftOptionIds.removeAll { groupIDs.contains($0) }
            draftOptionIds.append(option.id)
        } else if draftOptionIds.contains(option.id) {
            draftOptionIds.removeAll { $0 == option.id }
        } else {
            draftOptionIds.append(option.id)
        }
    }

    private func requiredOptionsSelected(_ product: ProductItem) -> Bool {
        product.optionGroups.allSatisfy { !$0.isRequired || $0.options.contains { draftOptionIds.contains($0.id) } }
    }

    private func productPrice(_ product: ProductItem) -> Int {
        product.price + product.optionGroups.flatMap(\.options).filter { draftOptionIds.contains($0.id) }.reduce(0) { $0 + $1.priceDelta }
    }

    private var cartBar: some View {
        HStack(spacing: 14) {
            VStack(alignment: .leading, spacing: 2) {
                Text("\(viewModel.cartCount)点")
                    .font(.caption)
                    .foregroundStyle(.white.opacity(0.65))
                Text(AppFormat.yen(viewModel.cartTotal))
                    .font(.title3.weight(.bold))
                    .foregroundStyle(.white)
            }
            Spacer(minLength: 0)
            Button {
                viewModel.showConfirm = true
            } label: {
                Text("注文を確認")
                    .font(.subheadline.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                    .padding(.horizontal, 16)
                    .frame(minHeight: 48)
                    .background(Color.white)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
            }
            .buttonStyle(.plain)
        }
        .padding(.horizontal, 20)
        .padding(.vertical, 12)
        .background(AppDesign.ink)
        .foregroundStyle(.white)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
        .padding(.horizontal, 16)
        .padding(.bottom, 4)
    }
}

struct OrderConfirmView: View {
    let lines: [CartLine]
    let total: Int
    @Binding var paymentMethod: String
    let isSubmitting: Bool
    let errorMessage: String?
    let onConfirm: () -> Void
    @Environment(\.brandAccent) var accent

    var body: some View {
        List {
            Section("注文内容") {
                ForEach(lines) { line in
                    HStack {
                        VStack(alignment: .leading, spacing: 2) {
                            Text(line.product.name)
                            Text("×\(line.quantity)")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                        if !line.optionItems.isEmpty {
                            Text(line.optionItems.map(\.name).joined(separator: "、"))
                                .font(.caption).foregroundStyle(.secondary)
                        }
                        Spacer()
                        Text(AppFormat.yen(line.lineTotal))
                    }
                }
            }
            Section("お支払い方法") {
                paymentOption(
                    value: "stripe",
                    title: "カードで支払う",
                    subtitle: "安全なStripe決済画面でお支払い",
                    icon: "creditcard"
                )
                paymentOption(
                    value: "cash",
                    title: "店頭で支払う",
                    subtitle: "商品受け取り時にお支払い",
                    icon: "banknote"
                )
            }
            Section {
                HStack {
                    Text("合計")
                    Spacer()
                    Text(AppFormat.yen(total))
                        .font(.title3.weight(.bold))
                        .foregroundStyle(accent)
                }
                Text(paymentMethod == "stripe" ? "注文後にカード決済画面を開きます" : "商品受け取り時に店頭でお支払いください")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
        .navigationTitle("注文確認")
        .scrollContentBackground(.hidden)
        .background(AppDesign.canvas)
        .appBottomAction {
            AppActionBar {
                VStack(spacing: 10) {
                    if let errorMessage {
                        Text(errorMessage).font(.footnote).foregroundStyle(AppDesign.danger)
                    }
                    AppPrimaryButton(
                        title: paymentMethod == "stripe" ? "カード決済へ · \(AppFormat.yen(total))" : "注文を確定 · \(AppFormat.yen(total))",
                        isLoading: isSubmitting,
                        enabled: !lines.isEmpty,
                        action: onConfirm
                    )
                }
            }
        }
    }

    private func paymentOption(value: String, title: String, subtitle: String, icon: String) -> some View {
        Button {
            paymentMethod = value
        } label: {
            HStack(spacing: 12) {
                Image(systemName: icon)
                    .frame(width: 28)
                    .foregroundStyle(paymentMethod == value ? accent : AppDesign.inkSecondary)
                VStack(alignment: .leading, spacing: 2) {
                    Text(title).font(.body.weight(.semibold)).foregroundStyle(AppDesign.ink)
                    Text(subtitle).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                }
                Spacer()
                Image(systemName: paymentMethod == value ? "checkmark.circle.fill" : "circle")
                    .foregroundStyle(paymentMethod == value ? accent : AppDesign.hairline)
            }
        }
        .buttonStyle(.plain)
    }
}

struct OrderStatusView: View {
    let order: OrderSnapshot
    var onContinuePayment: (() -> Void)? = nil
    @Environment(\.brandAccent) var accent

    var body: some View {
        ScrollView {
            VStack(spacing: 24) {
                VStack(spacing: 8) {
                    Text(order.paymentMethod == "stripe" && order.status == "pending" ? "カード決済を完了してください" : order.isActive ? "ご注文を受け付けました" : order.statusLabel)
                        .font(.system(size: 14, weight: .semibold))
                        .foregroundStyle(AppDesign.inkSecondary)
                    Text(order.pickupNumberLabel)
                        .font(.system(size: 44, weight: .bold, design: .rounded))
                        .foregroundStyle(accent)
                    Text("受取番号")
                        .font(.system(size: 13))
                        .foregroundStyle(AppDesign.inkSecondary)
                }
                .padding(.top, 8)

                if order.paymentMethod == "stripe", order.status == "pending", let onContinuePayment {
                    VStack(spacing: 10) {
                        AppPrimaryButton(title: "カード決済を続ける", action: onContinuePayment)
                        Text("決済完了後に受取番号が発行されます")
                            .font(.caption)
                            .foregroundStyle(AppDesign.inkSecondary)
                    }
                }

                VStack(alignment: .leading, spacing: 12) {
                    Text("進捗")
                        .font(.system(size: 15, weight: .bold))
                    if order.status == "cancelled" {
                        Text("この注文はキャンセルされました")
                            .foregroundStyle(AppDesign.danger)
                    } else {
                        OrderProgressTracker(status: order.status)
                    }
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                .padding(.vertical, 8)

                if !order.items.isEmpty {
                    VStack(alignment: .leading, spacing: 10) {
                        Text("内容")
                            .font(.system(size: 15, weight: .bold))
                        ForEach(order.items) { item in
                            HStack {
                                Text(item.productName)
                                Text("×\(item.quantity)")
                                    .foregroundStyle(AppDesign.inkSecondary)
                                Spacer()
                                Text(AppFormat.yen(item.price * item.quantity))
                            }
                            .font(.system(size: 14))
                        }
                        Divider()
                        HStack {
                            Text("合計")
                            Spacer()
                            Text(AppFormat.yen(order.totalAmount))
                                .fontWeight(.bold)
                        }
                    }
                }
            }
            .padding(20)
        }
        .background(AppDesign.canvas.ignoresSafeArea())
        .navigationTitle("注文状況")
    }
}
