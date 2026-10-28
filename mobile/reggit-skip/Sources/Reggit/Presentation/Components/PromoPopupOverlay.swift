import SwiftUI

/// 宣伝ポップアップの表示。形式（全画面・中央モーダル・下からシート・下部バナー）ごとに見た目だけを切り替える。
struct PromoPopupOverlay: View {
    let popup: PromoPopup
    let onClose: () -> Void
    /// 引数は2つ目のボタンを押したとき true
    let onAction: (Bool) -> Void

    var body: some View {
        switch popup.displayType {
        case .fullscreen: fullscreen
        case .modal: dimmed(alignment: .center) { card.padding(24) }
        case .bottomSheet: dimmed(alignment: .bottom) { card }
        case .banner: banner
        }
    }

    // MARK: - 形式ごとの外枠

    private var fullscreen: some View {
        ZStack(alignment: .topTrailing) {
            AppDesign.surface.ignoresSafeArea()
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    if let imageURL = popup.imageURL {
                        RemoteImageView(urlString: imageURL, contentMode: .fill)
                            .frame(height: 420)
                            .clipped()
                    }
                    VStack(alignment: .leading, spacing: 12) {
                        title
                        content
                    }
                    .padding(.horizontal, 20)
                    .padding(.top, popup.imageURL == nil ? 72 : 0)
                    .padding(.bottom, 24)
                }
            }
            closeButton(onImage: true)
                .padding(12)
        }
        .transition(.opacity)
    }

    private func dimmed<Panel: View>(alignment: Alignment, @ViewBuilder panel: () -> Panel) -> some View {
        ZStack(alignment: alignment) {
            Color.black.opacity(0.4)
                .ignoresSafeArea()
                .onTapGesture(perform: onClose)
                .accessibilityHidden(true)
            panel()
        }
        .transition(alignment == .bottom ? .move(edge: .bottom) : .opacity)
    }

    private var card: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(alignment: .top) {
                title
                Spacer(minLength: 8)
                closeButton(onImage: false)
            }
            if let imageURL = popup.imageURL {
                RemoteImageView(urlString: imageURL, contentMode: .fill, cornerRadius: AppDesign.radiusM)
                    .frame(height: 200)
            }
            content
        }
        .padding(20)
        .frame(maxWidth: 520)
        .background(AppDesign.surface)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
    }

    private var banner: some View {
        VStack {
            Spacer()
            HStack(spacing: 12) {
                // バナーは細いので、本体を押したときの1つ目のボタンだけを使う
                Button(action: { onAction(false) }) {
                    HStack(spacing: 12) {
                        if let imageURL = popup.imageURL {
                            RemoteImageView(urlString: imageURL, contentMode: .fill, cornerRadius: AppDesign.radiusS)
                                .frame(width: 56, height: 56)
                        }
                        VStack(alignment: .leading, spacing: 2) {
                            Text(popup.title)
                                .font(.subheadline.weight(.semibold))
                                .foregroundStyle(AppDesign.ink)
                            if !popup.body.isEmpty {
                                Text(popup.body)
                                    .font(.caption)
                                    .foregroundStyle(AppDesign.inkSecondary)
                                    .lineLimit(1)
                            }
                        }
                        Spacer(minLength: 0)
                    }
                    .frame(minHeight: 44)
                }
                .buttonStyle(.plain)
                closeButton(onImage: false)
            }
            .padding(10)
            .background(AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
            .shadow(color: Color.black.opacity(0.18), radius: 12, y: 4)
            .padding(.horizontal, 12)
            // 下部のタブバーに重ならない位置に出す
            .padding(.bottom, 84)
        }
        .transition(.move(edge: .bottom))
    }

    // MARK: - 共通の部品

    private var title: some View {
        Text(popup.title)
            .font(.title3.weight(.bold))
            .foregroundStyle(AppDesign.ink)
            .accessibilityAddTraits(.isHeader)
    }

    private var content: some View {
        VStack(alignment: .leading, spacing: 16) {
            if !popup.body.isEmpty {
                Text(popup.body)
                    .font(.subheadline)
                    .foregroundStyle(AppDesign.ink)
                    .fixedSize(horizontal: false, vertical: true)
            }
            AppPrimaryButton(title: popup.actionTitle, action: { onAction(false) })
            if let secondaryTitle = popup.secondaryButtonLabel {
                AppSecondaryButton(title: secondaryTitle, action: { onAction(true) })
            }
        }
    }

    private func closeButton(onImage: Bool) -> some View {
        Button(action: onClose) {
            FontAwesomeIcon(systemName: "xmark")
                .foregroundStyle(onImage ? Color.white : AppDesign.inkSecondary)
                .frame(width: 44, height: 44)
                .background(onImage ? Color.black.opacity(0.6) : Color.clear)
                .clipShape(Circle())
        }
        .buttonStyle(.plain)
        .accessibilityLabel(Text("閉じる"))
    }
}
