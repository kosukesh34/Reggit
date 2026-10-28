import Observation
import SkipFuse
import SwiftUI
#if !os(Android)
import UIKit
#endif

@MainActor
@Observable
final class ChatViewModel {
    /// 画面を開いている間だけ新着を確認する間隔。確認は会話ドキュメント1件の読み取りだけ
    static let pollNanoseconds: UInt64 = 4_000_000_000

    var isLoading = true
    var errorMessage: String?
    var messages: [ChatMessageItem] = []
    var draft = ""
    var isSending = false
    var sendError: String?
    var imagesEnabled = false
    var staffReadAt: Date?
    var blocked = false
    var attempt = 0
    /// 編集中の自分のメッセージ。入力欄に本文を入れ、送信ボタンで保存する
    var editing: ChatMessageItem?

    private let apiClient: any ReggitAPIServing
    private(set) var client: FirebaseChatClient?
    private var lastRaw: String?
    private var editedRaw: String?

    init(apiClient: any ReggitAPIServing) {
        self.apiClient = apiClient
    }

    var trimmedDraft: String { draft.trimmingCharacters(in: .whitespacesAndNewlines) }

    var canSend: Bool {
        !isSending && !trimmedDraft.isEmpty && trimmedDraft.count <= FirebaseChatClient.textLimit
    }

    /// 上限が近いときだけ残り文字数を出す
    var remainingLabel: String? {
        let remaining = FirebaseChatClient.textLimit - trimmedDraft.count
        return remaining <= 200 ? "残り\(remaining)文字" : nil
    }

    /// 店舗が読んだ自分のメッセージ
    func isReadByStaff(_ message: ChatMessageItem) -> Bool {
        guard message.isMine, let createdAt = message.createdAt, let staffReadAt else { return false }
        return createdAt <= staffReadAt
    }

    func run() async {
        isLoading = true
        errorMessage = nil
        do {
            let session = try await apiClient.fetchChatSession()
            let apiClient = self.apiClient
            let client = FirebaseChatClient(session: session) { try await apiClient.fetchChatSession() }
            try await client.openThread()
            messages = try await client.fetchMessages(after: nil)
            lastRaw = messages.last?.createdAtRaw
            apply(try await client.fetchThread())
            imagesEnabled = session.imagesEnabled
            self.client = client
            isLoading = false
        } catch {
            errorMessage = error.localizedDescription
            isLoading = false
            return
        }
        while !Task.isCancelled {
            try? await Task.sleep(nanoseconds: Self.pollNanoseconds)
            if Task.isCancelled { break }
            await refresh()
        }
    }

    func refresh() async {
        guard let client else { return }
        do {
            guard let thread = try await client.fetchThread() else { return }
            // どこかでメッセージが削除されたら、一覧を取り直して表示をそろえる
            if thread.editedRaw != editedRaw {
                messages = try await client.fetchMessages(after: nil)
                lastRaw = messages.last?.createdAtRaw
            }
            apply(thread)
            if let latest = thread.lastMessageRaw, latest != lastRaw {
                let known = Set(messages.map(\.id))
                messages += try await client.fetchMessages(after: lastRaw).filter { !known.contains($0.id) }
                lastRaw = messages.last?.createdAtRaw ?? lastRaw
            }
            if thread.hasUnreadReply {
                try await client.openThread()
                // アプリアイコン・メニューの未読数も0に戻す
                try? await apiClient.markChatRead()
            }
        } catch {
            // 一時的な通信失敗は次の確認で回復するため、表示中の会話は消さない
        }
    }

    private func apply(_ thread: ChatThreadState?) {
        staffReadAt = thread?.staffReadAt
        blocked = thread?.blocked ?? false
        editedRaw = thread?.editedRaw
    }

    func delete(_ message: ChatMessageItem) async {
        guard let client else { return }
        sendError = nil
        do {
            try await client.deleteMessage(message)
            await refresh()
        } catch {
            sendError = error.localizedDescription
        }
    }

    /// 本文中の http(s) の URL をタップで開けるようにする。
    /// NSDataDetector は Android の Foundation で使えないため、空白・括弧までを URL とみなして自前で切り出す
    static func linkedText(_ text: String) -> AttributedString {
        var result = AttributedString()
        var rest = Substring(text)
        while let start = rest.range(of: "https://") ?? rest.range(of: "http://") {
            let first = [rest.range(of: "https://"), rest.range(of: "http://")].compactMap { $0 }.min { $0.lowerBound < $1.lowerBound } ?? start
            result += AttributedString(String(rest[..<first.lowerBound]))
            let tail = rest[first.lowerBound...]
            let end = tail.firstIndex { $0.isWhitespace || "<>\"'（）「」、。".contains($0) } ?? tail.endIndex
            let candidate = String(tail[..<end])
            var link = AttributedString(candidate)
            if let url = URL(string: candidate), url.host != nil {
                link.link = url
                link.underlineStyle = .single
            }
            result += link
            rest = tail[end...]
        }
        result += AttributedString(String(rest))
        return result
    }

    func startEdit(_ message: ChatMessageItem) {
        editing = message
        draft = message.text
        sendError = nil
    }

    func cancelEdit() {
        editing = nil
        draft = ""
    }

    func send() async {
        guard canSend, !blocked, let client else { return }
        isSending = true
        sendError = nil
        defer { isSending = false }
        do {
            if let editing {
                try await client.editMessage(editing, text: draft)
                cancelEdit()
            } else {
                try await client.send(text: draft)
                draft = ""
            }
            await refresh()
        } catch {
            sendError = error.localizedDescription
        }
    }

    /// 日付の区切りを入れるか（前のメッセージと日付が違う）
    func startsNewDay(at index: Int) -> Bool {
        guard let date = messages[index].createdAt else { return false }
        guard index > 0, let previous = messages[index - 1].createdAt else { return true }
        return !Calendar.current.isDate(date, inSameDayAs: previous)
    }

    static func dayLabel(_ date: Date, now: Date = Date()) -> String {
        let calendar = Calendar.current
        if calendar.isDate(date, inSameDayAs: now) { return "今日" }
        if let yesterday = calendar.date(byAdding: .day, value: -1, to: now), calendar.isDate(date, inSameDayAs: yesterday) { return "昨日" }
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "ja_JP")
        formatter.dateFormat = calendar.isDate(date, equalTo: now, toGranularity: .year) ? "M月d日(E)" : "yyyy年M月d日(E)"
        return formatter.string(from: date)
    }

    static func timeLabel(_ date: Date) -> String {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "ja_JP")
        formatter.dateFormat = "H:mm"
        return formatter.string(from: date)
    }
}

struct ChatView: View {
    @State var viewModel: ChatViewModel
    @State var pendingDelete: ChatMessageItem?
    @Environment(\.brandAccent) var accent
    /// カードのボタンで開くタブ（クーポン・予約）
    let onOpenTab: (String) -> Void

    init(apiClient: any ReggitAPIServing, onOpenTab: @escaping (String) -> Void = { _ in }) {
        _viewModel = State(initialValue: ChatViewModel(apiClient: apiClient))
        self.onOpenTab = onOpenTab
    }

    var body: some View {
        NavigationStack {
            AppAsyncScreen(
                isLoading: viewModel.isLoading,
                errorMessage: viewModel.errorMessage,
                isEmpty: false,
                emptyTitle: "",
                emptySubtitle: "",
                emptyIcon: "bubble.left.and.bubble.right",
                onRetry: { viewModel.attempt += 1 },
                skeleton: { ChatSkeletonView() }
            ) {
                VStack(spacing: 0) {
                    ScrollViewReader { proxy in
                        ScrollView {
                            LazyVStack(spacing: 10) {
                                if viewModel.messages.isEmpty { emptyState }
                                ForEach(Array(viewModel.messages.enumerated()), id: \.element.id) { index, message in
                                    if viewModel.startsNewDay(at: index), let date = message.createdAt {
                                        daySeparator(date)
                                    }
                                    bubble(message).id(message.id)
                                }
                            }
                            .padding(16)
                        }
                        .onChange(of: viewModel.messages.count) { _, _ in scrollToLatest(proxy) }
                        .onAppear { scrollToLatest(proxy) }
                    }
                    if viewModel.blocked {
                        Text("現在このチャットではメッセージを送信できません。")
                            .font(.subheadline)
                            .foregroundStyle(AppDesign.inkSecondary)
                            .frame(maxWidth: .infinity)
                            .padding(16)
                            .background(AppDesign.surface)
                    } else {
                        composer
                    }
                }
            }
            .alert("送信を取り消しますか？", isPresented: Binding(get: { pendingDelete != nil }, set: { if !$0 { pendingDelete = nil } })) {
                Button("取り消す", role: .destructive) {
                    if let message = pendingDelete { Task { await viewModel.delete(message) } }
                    pendingDelete = nil
                }
                Button("キャンセル", role: .cancel) { pendingDelete = nil }
            } message: {
                Text("お店の画面からも消えます。")
            }
            .background(AppDesign.canvas.ignoresSafeArea())
            .navigationBarHidden(true)
            .toolbar(.hidden, for: .navigationBar)
        }
        .task(id: viewModel.attempt) { await viewModel.run() }
    }

    private func scrollToLatest(_ proxy: ScrollViewProxy) {
        if let last = viewModel.messages.last { proxy.scrollTo(last.id, anchor: .bottom) }
    }

    private var emptyState: some View {
        VStack(spacing: 12) {
            FontAwesomeIcon(systemName: "bubble.left.and.bubble.right")
                .font(.system(size: 32))
                .foregroundStyle(accent)
            Text("お店とのチャット")
                .font(.headline)
                .foregroundStyle(AppDesign.ink)
            Text("ご質問やご相談をお送りください。スタッフが確認して返信します。返信が届くと通知でお知らせします。")
                .font(.subheadline)
                .foregroundStyle(AppDesign.inkSecondary)
                .multilineTextAlignment(.center)
        }
        .padding(.vertical, 48)
        .padding(.horizontal, 16)
    }

    private func daySeparator(_ date: Date) -> some View {
        Text(ChatViewModel.dayLabel(date))
            .font(.caption.weight(.semibold))
            .foregroundStyle(AppDesign.inkSecondary)
            .padding(.horizontal, 12)
            .padding(.vertical, 4)
            .background(AppDesign.surfaceMuted)
            .clipShape(Capsule())
            .frame(maxWidth: .infinity)
            .padding(.vertical, 4)
            .accessibilityAddTraits(.isHeader)
    }

    private func bubble(_ message: ChatMessageItem) -> some View {
        HStack(alignment: .bottom, spacing: 6) {
            if message.isMine {
                Spacer(minLength: 48)
                meta(message, alignment: .trailing)
            }
            VStack(alignment: .leading, spacing: 6) {
                if !message.isMine && !message.senderName.isEmpty {
                    Text(message.senderName)
                        .font(.caption.weight(.bold))
                        .foregroundStyle(AppDesign.inkSecondary)
                }
                if message.deleted {
                    Text(message.isMine ? "送信を取り消しました" : "メッセージは削除されました")
                        .font(.footnote)
                        .foregroundStyle(AppDesign.inkSecondary)
                } else {
                    if let path = message.imagePath, let client = viewModel.client, viewModel.imagesEnabled {
                        ChatImageView(client: client, path: path)
                    }
                    if let card = message.card {
                        cardView(card)
                    }
                    if !message.text.isEmpty {
                        Text(ChatViewModel.linkedText(message.text))
                            .font(.body)
                            .foregroundStyle(message.isMine ? Color.white : AppDesign.ink)
                            .tint(message.isMine ? Color.white : accent)
                            .textSelection(.enabled)
                    }
                }
            }
            .padding(.horizontal, 12)
            .padding(.vertical, 10)
            .background(message.deleted || message.card != nil ? AppDesign.surface : message.isMine ? accent : AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
            .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusL).stroke(AppDesign.hairline, lineWidth: message.deleted || message.card != nil ? 1 : 0))
            if !message.isMine {
                meta(message, alignment: .leading)
                Spacer(minLength: 48)
            }
        }
        .accessibilityElement(children: .combine)
    }

    private func cardView(_ card: ChatCardItem) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack(spacing: 10) {
                FontAwesomeIcon(systemName: card.icon)
                    .font(.system(size: 20))
                    .foregroundStyle(accent)
                VStack(alignment: .leading, spacing: 2) {
                    Text(card.title).font(.body.weight(.bold)).foregroundStyle(AppDesign.ink)
                    if !card.subtitle.isEmpty {
                        Text(card.subtitle).font(.caption).foregroundStyle(AppDesign.inkSecondary)
                    }
                }
            }
            Button { onOpenTab(card.tabKey) } label: {
                Text(card.actionLabel)
                    .font(.subheadline.weight(.bold))
                    .foregroundStyle(.white)
                    .frame(maxWidth: .infinity, minHeight: 40)
                    .background(accent)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
            }
            .buttonStyle(.plain)
        }
        .frame(minWidth: 200)
    }

    /// 吹き出しの横に「既読」・時刻・送信取消を出す
    private func meta(_ message: ChatMessageItem, alignment: HorizontalAlignment) -> some View {
        VStack(alignment: alignment, spacing: 2) {
            if viewModel.isReadByStaff(message) && !message.deleted {
                Text("既読")
            }
            if let createdAt = message.createdAt {
                Text(ChatViewModel.timeLabel(createdAt))
            }
            if message.edited && !message.deleted {
                Text("編集済み")
            }
            if message.isMine && !message.deleted && message.createdAtRaw != nil {
                HStack(spacing: 8) {
                    if message.card == nil {
                        Button("編集") { viewModel.startEdit(message) }
                            .buttonStyle(.plain)
                            .underline()
                            .accessibilityLabel("メッセージを編集する")
                    }
                    Button("取消") { pendingDelete = message }
                        .buttonStyle(.plain)
                        .underline()
                        .accessibilityLabel("送信を取り消す")
                }
            }
        }
        .font(.caption2)
        .foregroundStyle(AppDesign.inkSecondary)
    }

    private var composer: some View {
        VStack(alignment: .leading, spacing: 6) {
            if let sendError = viewModel.sendError {
                Text(sendError)
                    .font(.footnote)
                    .foregroundStyle(AppDesign.danger)
            }
            if viewModel.editing != nil {
                HStack {
                    FontAwesomeLabel("メッセージを編集中", systemImage: "pencil")
                        .font(.footnote)
                        .foregroundStyle(AppDesign.inkSecondary)
                    Spacer()
                    Button("やめる") { viewModel.cancelEdit() }
                        .font(.footnote.weight(.semibold))
                        .accessibilityLabel("編集をやめる")
                }
                .padding(.horizontal, 10)
                .padding(.vertical, 6)
                .background(AppDesign.surfaceMuted)
                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusS))
            }
            HStack(spacing: 8) {
                TextField("メッセージを入力", text: Binding(get: { viewModel.draft }, set: { viewModel.draft = $0 }))
                    .submitLabel(.send)
                    .onSubmit { Task { await viewModel.send() } }
                    .padding(.horizontal, 12)
                    .frame(minHeight: 44)
                    .background(AppDesign.surfaceMuted)
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
                    .accessibilityLabel("メッセージ")
                Button {
                    Task { await viewModel.send() }
                } label: {
                    Group {
                        if viewModel.isSending {
                            ProgressView().tint(.white)
                        } else {
                            FontAwesomeIcon(systemName: viewModel.editing != nil ? "checkmark" : "paperplane.fill")
                        }
                    }
                    .foregroundStyle(.white)
                    .frame(width: 44, height: 44)
                    .background(accent.opacity(viewModel.canSend ? 1 : 0.4))
                    .clipShape(Circle())
                }
                .disabled(!viewModel.canSend)
                .accessibilityLabel(viewModel.isSending ? "送信中" : viewModel.editing != nil ? "保存" : "送信")
            }
            if let remaining = viewModel.remainingLabel {
                Text(remaining)
                    .font(.caption2)
                    .foregroundStyle(viewModel.trimmedDraft.count > FirebaseChatClient.textLimit ? AppDesign.danger : AppDesign.inkSecondary)
            }
        }
        .padding(.horizontal, 12)
        .padding(.vertical, 10)
        .background(AppDesign.surface)
        .overlay(alignment: .top) { Rectangle().fill(AppDesign.hairline).frame(height: 1) }
    }
}

/// Storage を有効にした運用で、店舗から届いた画像を表示する
struct ChatImageView: View {
    let client: FirebaseChatClient
    let path: String
    @State var image: UIImage?
    @State var failed = false

    var body: some View {
        Group {
            if let image {
                Image(uiImage: image).resizable().aspectRatio(contentMode: .fit)
            } else if failed {
                Text("画像を表示できません").font(.caption).foregroundStyle(AppDesign.inkSecondary)
            } else {
                ProgressView()
            }
        }
        .frame(maxWidth: 240, maxHeight: 320)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
        .task(id: path) {
            do {
                image = UIImage(data: try await client.fetchImage(path: path))
                failed = image == nil
            } catch {
                if !Task.isCancelled { failed = true }
            }
        }
    }
}
