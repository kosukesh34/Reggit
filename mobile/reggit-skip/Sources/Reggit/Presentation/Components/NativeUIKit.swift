import SkipFuse
import SwiftUI
#if canImport(UIKit)
import UIKit

@MainActor
private enum NativeImageCache {
    static let images = NSCache<NSURL, UIImage>()
    static var missingURLs: Set<URL> = []
}

private struct CachedRemoteImage: View {
    let url: URL
    let contentMode: ContentMode
    @State private var image: UIImage?
    @State private var failed = false

    var body: some View {
        Group {
            if let image = image ?? NativeImageCache.images.object(forKey: url as NSURL) {
                Image(uiImage: image).resizable().aspectRatio(contentMode: contentMode)
            } else if failed {
                AppDesign.surfaceMuted
            } else {
                SkeletonBlock(height: 1, cornerRadius: 0)
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            }
        }
        .task(id: url) {
            if let cached = NativeImageCache.images.object(forKey: url as NSURL) {
                image = cached
                return
            }
            if NativeImageCache.missingURLs.contains(url) {
                failed = true
                return
            }
            do {
                let (data, response) = try await URLSession.shared.data(from: url)
                if let response = response as? HTTPURLResponse,
                   response.statusCode == 404 || response.statusCode == 410 {
                    NativeImageCache.missingURLs.insert(url)
                    failed = true
                    return
                }
                guard let response = response as? HTTPURLResponse,
                      (200..<300).contains(response.statusCode),
                      let loaded = UIImage(data: data) else { failed = true; return }
                NativeImageCache.images.setObject(loaded, forKey: url as NSURL, cost: data.count)
                image = loaded
            } catch { if !Task.isCancelled { failed = true } }
        }
    }
}
#endif

private struct APIBaseURLKey: EnvironmentKey {
    static let defaultValue = ""
}

extension EnvironmentValues {
    var apiBaseURL: String {
        get { self[APIBaseURLKey.self] }
        set { self[APIBaseURLKey.self] = newValue }
    }
}

private struct BrandAccentKey: EnvironmentKey {
    static let defaultValue = Color.black
}

extension EnvironmentValues {
    var brandAccent: Color {
        get { self[BrandAccentKey.self] }
        set { self[BrandAccentKey.self] = newValue }
    }
}

private struct AppButtonColorsKey: EnvironmentKey {
    static let defaultValue: [String: String] = [:]
}

extension EnvironmentValues {
    var appButtonColors: [String: String] {
        get { self[AppButtonColorsKey.self] }
        set { self[AppButtonColorsKey.self] = newValue }
    }
}

// MARK: - Design tokens

enum AppDesign {
    // Neutral gray surfaces keep content and store-colored actions distinct.
    static let usesStoreTheme = true
    static let forest = Color(red: 0.09, green: 0.25, blue: 0.21)
    static let coral = Color(red: 0.91, green: 0.46, blue: 0.34)
    static let canvas = Color(red: 0.95, green: 0.96, blue: 0.97)
    static let surface = Color.white
    static let surfaceMuted = Color(red: 0.94, green: 0.95, blue: 0.96)
    static let ink = Color(red: 0.09, green: 0.13, blue: 0.11)
    static let inkSecondary = Color(red: 0.34, green: 0.38, blue: 0.42)
    static let hairline = Color(red: 214.0 / 255.0, green: 222.0 / 255.0, blue: 230.0 / 255.0)
    static let danger = Color(red: 0.77, green: 0.25, blue: 0.21)
    static let success = Color(red: 0.16, green: 0.52, blue: 0.32)

    static let radiusXL: CGFloat = 22
    static let radiusL: CGFloat = 16
    static let radiusM: CGFloat = 12
    static let radiusS: CGFloat = 8
}

private struct BrandForegroundKey: EnvironmentKey {
    static let defaultValue = Color.white
}

extension EnvironmentValues {
    var brandForeground: Color {
        get { self[BrandForegroundKey.self] }
        set { self[BrandForegroundKey.self] = newValue }
    }
}

extension String {
    func toNativeColor(fallback: Color = AppDesign.ink) -> Color {
        let value = trimmingCharacters(in: .whitespacesAndNewlines).replacingOccurrences(of: "#", with: "")
        guard value.count == 6, UInt32(value, radix: 16) != nil else { return fallback }
        let palette = BrandPalette(hex: self)
        return Color(red: palette.red, green: palette.green, blue: palette.blue)
    }
}

// MARK: - Skip Observation kick（OS 分岐は PlatformRuntime のみ）

/// `@Observable` の変更が Compose に届かない環境向けに、View 本体を再評価する。
/// `.task` や `onAppear` は外側に付け、キックのたびに再実行されないようにする。
struct SkipRefreshing<Content: View>: View {
    @State var generation = 0
    @ViewBuilder var content: () -> Content

    var body: some View {
        let _ = generation
        content()
            .task {
                let nanos = PlatformRuntime.viewObservationKickNanoseconds
                guard nanos > 0 else { return }
                while !Task.isCancelled {
                    generation &+= 1
                    try? await Task.sleep(nanoseconds: nanos)
                }
            }
    }
}

// MARK: - Remote image (AsyncImage = Skip 両対応。UIKit 非依存)

struct RemoteImageView: View {
    let urlString: String?
    var baseURL: String = ""
    var contentMode: ContentMode = .fill
    var cornerRadius: CGFloat = 0
    var usesDemoFallback = false

    @Environment(\.apiBaseURL) var envBaseURL

    private var resolvedURL: URL? {
        MediaURL.resolve(urlString, baseURL: baseURL.trimmedNonEmpty ?? envBaseURL)
    }

    var body: some View {
        Group {
            if let url = resolvedURL {
                #if canImport(UIKit)
                CachedRemoteImage(url: url, contentMode: contentMode)
                #else
                AsyncImage(url: url) { phase in
                    switch phase {
                    case .success(let image):
                        image
                            .resizable()
                            .aspectRatio(contentMode: contentMode)
                    case .failure:
                        imagePlaceholder
                    case .empty:
                        SkeletonBlock(height: 1, cornerRadius: 0)
                            .frame(maxWidth: .infinity, maxHeight: .infinity)
                    @unknown default:
                        imagePlaceholder
                    }
                }
                #endif
            } else {
                imagePlaceholder
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .center)
        .clipShape(RoundedRectangle(cornerRadius: self.cornerRadius))
    }

    private var imagePlaceholder: some View {
        ZStack {
            if usesDemoFallback {
                Image("DemoStoreHero", bundle: .module)
                    .resizable()
                    .aspectRatio(contentMode: contentMode)
                    .accessibilityHidden(true)
            } else {
                LinearGradient(
                    colors: [AppDesign.hairline.opacity(0.65), AppDesign.canvas],
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                )
                FontAwesomeIcon(systemName: "photo")
                    .foregroundStyle(AppDesign.inkSecondary.opacity(0.4))
            }
        }
    }
}

// MARK: - Shared chrome

struct AppPrimaryButton: View {
    let title: String
    var isLoading = false
    var enabled = true
    let action: () -> Void
    @Environment(\.brandAccent) var accent
    @Environment(\.brandForeground) var onAccent

    @Environment(\.appButtonColors) var colors

    var body: some View {
        Button(action: action) {
            Group {
                if isLoading {
                    ProgressView().tint((colors["primary_button_text"] ?? "").toNativeColor(fallback: onAccent))
                } else {
                    Text(title).fontWeight(.semibold)
                }
            }
            .frame(maxWidth: .infinity)
            .frame(minHeight: 52)
            .foregroundStyle((colors["primary_button_text"] ?? "").toNativeColor(fallback: onAccent))
            .background {
                RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                    .fill(enabled ? (colors["primary_button_background"] ?? "").toNativeColor(fallback: accent) : AppDesign.inkSecondary.opacity(0.5))
            }
        }
        .buttonStyle(.plain)
        .disabled(!enabled || isLoading)
    }
}

struct AppSecondaryButton: View {
    let title: String
    let action: () -> Void

    @Environment(\.appButtonColors) var colors

    var body: some View {
        Button(action: action) {
            Text(title)
                .fontWeight(.semibold)
                .frame(maxWidth: .infinity)
                .frame(minHeight: 52)
                .foregroundStyle((colors["secondary_button_text"] ?? "").toNativeColor(fallback: AppDesign.ink))
                .background((colors["secondary_button_background"] ?? "").toNativeColor(fallback: AppDesign.surfaceMuted))
                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
        }
        .buttonStyle(.plain)
    }
}

struct AppEmptyState: View {
    let title: String
    let subtitle: String
    let systemImage: String
    var actionTitle: String? = nil
    var action: (() -> Void)? = nil

    var body: some View {
        VStack(spacing: 14) {
            FontAwesomeIcon(systemName: systemImage)
                .foregroundStyle(AppDesign.ink)
                .frame(width: 64, height: 64)
            Text(title)
                .font(.system(size: 18, weight: .semibold))
                .foregroundStyle(AppDesign.ink)
            Text(subtitle)
                .font(.system(size: 14))
                .foregroundStyle(AppDesign.inkSecondary)
                .multilineTextAlignment(.center)
            if let actionTitle, let action {
                Button(actionTitle, action: action)
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(AppDesign.ink)
                    .frame(minHeight: 44)
                    .padding(.horizontal, 24)
                    .background(AppDesign.surface)
                    .clipShape(Capsule())
                    .padding(.top, 4)
            }
        }
        .padding(32)
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}

struct AppAsyncScreen<Skeleton: View, Content: View>: View {
    let isLoading: Bool
    let errorMessage: String?
    let isEmpty: Bool
    let emptyTitle: String
    let emptySubtitle: String
    let emptyIcon: String
    var onRetry: (() -> Void)? = nil
    @ViewBuilder var skeleton: () -> Skeleton
    @ViewBuilder var content: () -> Content

    var body: some View {
        if isLoading {
            skeleton()
        } else if let errorMessage {
            AppEmptyState(
                title: "読み込めませんでした",
                subtitle: errorMessage,
                systemImage: "wifi.slash",
                actionTitle: onRetry == nil ? nil : "再読み込み",
                action: onRetry
            )
        } else if isEmpty {
            AppEmptyState(title: emptyTitle, subtitle: emptySubtitle, systemImage: emptyIcon)
        } else {
            content()
        }
    }
}

struct AppCard<Content: View>: View {
    @ViewBuilder var content: () -> Content

    var body: some View {
        content()
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
            .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                .stroke(AppDesign.hairline, lineWidth: 1))
            .shadow(color: AppDesign.forest.opacity(0.055), radius: 16, x: 0, y: 8)
    }
}

struct AppToast: View {
    let message: String

    var body: some View {
        Text(message)
            .font(.system(size: 14, weight: .semibold))
            .foregroundStyle(.white)
            .padding(.horizontal, 16)
            .padding(.vertical, 12)
            .background(AppDesign.ink.opacity(0.92))
            .clipShape(RoundedRectangle(cornerRadius: 7, style: .continuous))
            .padding(.bottom, 12)
    }
}

struct AppToastHost: View {
    @Binding var message: String?

    var body: some View {
        VStack {
            Spacer()
            if let message, !message.isEmpty {
                AppToast(message: message)
            }
        }
        .allowsHitTesting(false)
        .task(id: message) {
            guard let current = message, !current.isEmpty else { return }
            try? await Task.sleep(nanoseconds: 2_400_000_000)
            if message == current {
                message = nil
            }
        }
    }
}

struct StatusBadge: View {
    let text: String
    var emphasized = false
    @Environment(\.brandAccent) var accent

    var body: some View {
        Text(text)
            .font(.caption.weight(.semibold))
            .foregroundStyle(emphasized ? accent : AppDesign.inkSecondary)
            .padding(.horizontal, 10)
            .padding(.vertical, 6)
            .background(emphasized ? accent.opacity(0.12) : AppDesign.hairline.opacity(0.55))
            .clipShape(Capsule())
    }
}

struct OrderSummaryRow: View {
    let order: OrderSnapshot
    @Environment(\.brandAccent) var accent

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            VStack(alignment: .leading, spacing: 4) {
                Text(order.pickupNumberLabel)
                    .font(.body.weight(.semibold))
                    .foregroundStyle(AppDesign.ink)
                Text(order.items.map { "\($0.productName)×\($0.quantity)" }.joined(separator: "、"))
                    .font(.caption)
                    .foregroundStyle(.secondary)
                    .lineLimit(2)
                if !order.createdAt.isEmpty {
                    Text(AppFormat.dateTime(order.createdAt))
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                }
            }
            Spacer(minLength: 8)
            VStack(alignment: .trailing, spacing: 6) {
                Text(AppFormat.yen(order.totalAmount))
                    .font(.body.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                StatusBadge(text: order.statusLabel, emphasized: order.isActive)
            }
        }
        .padding(.vertical, 2)
    }
}

struct CategoryChipRow: View {
    let categories: [String]
    @Binding var selected: String
    @Environment(\.brandAccent) var accent

    var body: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                chip(title: "すべて", key: "")
                ForEach(categories, id: \.self) { category in
                    chip(title: category, key: category)
                }
            }
            .padding(.horizontal, 16)
        }
    }

    private func chip(title: String, key: String) -> some View {
        let isOn = selected == key
        return Button {
            selected = key
        } label: {
            Text(title)
                .font(.system(size: 13, weight: .semibold))
                .foregroundStyle(isOn ? Color.white : AppDesign.ink)
                .padding(.horizontal, 16)
                .frame(minHeight: 44)
                .background(isOn ? AppDesign.ink : AppDesign.surfaceMuted)
                .clipShape(Capsule())
        }
        .buttonStyle(.plain)
    }
}

struct QtyStepper: View {
    let itemName: String
    let quantity: Int
    let onMinus: () -> Void
    let onPlus: () -> Void
    var canIncrease = true
    var expandsToFill = false
    @Environment(\.brandAccent) var accent
    @Environment(\.brandForeground) var onAccent

    @Environment(\.appButtonColors) var colors

    var body: some View {
        HStack(spacing: 0) {
            if quantity > 0 {
                Button(action: onMinus) {
                    FontAwesomeIcon(systemName: "minus")
                        .frame(width: 44, height: 44)
                }
                .buttonStyle(.plain)
                .accessibilityLabel("\(itemName)の数量を減らす")
                if expandsToFill { Spacer(minLength: 0) }
                Text("\(quantity)")
                    .font(.system(size: 15, weight: .semibold, design: .monospaced))
                    .frame(minWidth: 22)
                if expandsToFill { Spacer(minLength: 0) }
                Button(action: onPlus) {
                    FontAwesomeIcon(systemName: "plus")
                        .foregroundStyle((colors["primary_button_text"] ?? "").toNativeColor(fallback: onAccent))
                        .frame(width: 44, height: 44)
                        .background((colors["primary_button_background"] ?? "").toNativeColor(fallback: accent))
                        .clipShape(Circle())
                }
                .buttonStyle(.plain)
                .disabled(!canIncrease)
                .accessibilityLabel("\(itemName)の数量を増やす")
            } else {
                Button(action: onPlus) {
                    Text("追加")
                        .font(.subheadline.weight(.bold))
                        .foregroundStyle((colors["primary_button_text"] ?? "").toNativeColor(fallback: onAccent))
                        .padding(.horizontal, 18)
                        .frame(maxWidth: expandsToFill ? .infinity : nil, minHeight: 44)
                        .background((colors["primary_button_background"] ?? "").toNativeColor(fallback: accent))
                        .clipShape(Capsule())
                }
                .buttonStyle(.plain)
                .disabled(!canIncrease)
                .accessibilityLabel("\(itemName)を追加")
            }
        }
        .frame(maxWidth: expandsToFill ? .infinity : nil)
        .foregroundStyle(AppDesign.ink)
        .background(quantity > 0 ? AppDesign.surfaceMuted : Color.clear)
        .clipShape(Capsule())
    }
}

struct AppSegmentedControl: View {
    let titles: [String]
    @Binding var selectedIndex: Int

    var body: some View {
        HStack(spacing: 0) {
            ForEach(Array(titles.enumerated()), id: \.offset) { index, title in
                Button { selectedIndex = index } label: {
                    Text(title)
                        .font(.system(size: 13, weight: selectedIndex == index ? .semibold : .regular))
                        .foregroundStyle(AppDesign.ink)
                        .frame(maxWidth: .infinity, minHeight: 28)
                        .background(selectedIndex == index ? AppDesign.surface : Color.clear)
                        .clipShape(Capsule())
                }
                .buttonStyle(.plain)
                .accessibilityValue(selectedIndex == index ? "選択中" : "")
            }
        }
        .padding(2)
        .background(AppDesign.hairline)
        .clipShape(Capsule())
    }
}

struct QuickActionGrid: View {
    let tabs: [AppTabItem]
    let onSelect: (String) -> Void
    @Environment(\.brandAccent) var accent

    var body: some View {
        VStack(spacing: 0) {
            ForEach(Array(tabs.enumerated()), id: \.element.id) { index, tab in
                Button { onSelect(tab.key) } label: {
                    HStack(spacing: 14) {
                        FontAwesomeIcon(systemName: TabIconResolver.systemName(for: tab))
                            .foregroundStyle(accent)
                            .frame(width: 44, height: 44)
                            .background(AppDesign.surfaceMuted)
                            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                        VStack(alignment: .leading, spacing: 5) {
                            Text(tab.label).font(.body.weight(.semibold)).foregroundStyle(AppDesign.ink)
                            Text(actionSubtitle(for: tab))
                                .font(.caption).foregroundStyle(AppDesign.inkSecondary)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                        Spacer(minLength: 8)
                        FontAwesomeIcon(systemName: "chevron.right")
                            .foregroundStyle(AppDesign.inkSecondary)
                    }
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .padding(.vertical, 12)
                }
                .buttonStyle(.plain)
                if index < tabs.count - 1 {
                    Divider().padding(.leading, 58)
                }
            }
        }
    }

    private func actionSubtitle(for tab: AppTabItem) -> String {
        switch tab.key {
        case "products": return "商品・サービスを探す"
        case "orders": return "注文履歴を確認"
        case "coupons": return "使える特典を確認"
        case "reservations": return "予約の確認・管理"
        case "queue": return "順番待ちの状況"
        case "stamps": return "スタンプを集める"
        case "gallery": return "店舗の最新情報"
        default: return "タップして開く"
        }
    }
}

struct AppSectionHeader: View {
    let title: String
    var subtitle: String? = nil
    var actionTitle: String? = nil
    var action: (() -> Void)? = nil

    var body: some View {
        HStack(alignment: .firstTextBaseline) {
            VStack(alignment: .leading, spacing: 3) {
                Text(title)
                    .font(.title2.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                if let subtitle, !subtitle.isEmpty {
                    Text(subtitle)
                        .font(.caption)
                        .foregroundStyle(AppDesign.inkSecondary)
                }
            }
            Spacer()
            if let actionTitle, let action {
                Button(actionTitle, action: action)
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(AppDesign.ink)
                    .frame(minHeight: 44)
            }
        }
    }
}

struct MemberFieldStack: View {
    @Binding var lastName: String
    @Binding var firstName: String
    @Binding var phone: String

    var body: some View {
        VStack(spacing: 12) {
            HStack(spacing: 8) {
                TextField("姓", text: $lastName)
                    .padding(14)
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                    .accessibilityLabel("姓")
                TextField("名", text: $firstName)
                    .padding(14)
                    .background(AppDesign.surface)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                    .accessibilityLabel("名")
            }
            TextField("電話番号", text: $phone)
                .padding(14)
                .background(AppDesign.surface)
                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
        }
    }
}

/// Primary actions stay above the keyboard and system tab bar without covering content.
struct AppActionBar<Content: View>: View {
    @ViewBuilder var content: () -> Content

    var body: some View {
        content()
            .padding(.horizontal, 16)
            .padding(.top, 10)
            .padding(.bottom, 10)
            .background(AppDesign.surface.opacity(0.98))
            .overlay(alignment: .top) { Rectangle().fill(AppDesign.hairline).frame(height: 0.5) }
    }
}

/// Skip Fuse does not yet expose safeAreaInset; a separate stack row reserves
/// the same space on Android, including when the software keyboard is visible.
extension View {
    @ViewBuilder
    func appBottomAction<Action: View>(@ViewBuilder content: () -> Action) -> some View {
        #if os(Android)
        VStack(spacing: 0) {
            self.frame(maxWidth: .infinity, maxHeight: .infinity)
            content()
        }
        #else
        self.safeAreaInset(edge: .bottom, content: content)
        #endif
    }
}
