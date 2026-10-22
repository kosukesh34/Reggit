import SwiftUI

/// ログイン・会員登録の直後に出す機能案内。横スワイプか「OK」で1枚ずつ進む。
/// 認証直後は閉じられず、メニューの「アプリの使い方」から開いたときだけ閉じられる。
/// 画像は使わず、お店の色とサーバー設定（points_guide）の数値・ランク色で描く。
struct FeatureTourView: View {
    let pointsGuide: PointsGuide
    let storeName: String
    var onClose: (() -> Void)? = nil
    let onFinish: () -> Void
    @State var index = 0
    @State var dragOffset: CGFloat = 0
    @Environment(\.brandAccent) var accent
    @Environment(\.brandForeground) var accentForeground

    struct Page: Identifiable {
        let id: String
        let title: String
        let body: String
    }

    private var pages: [Page] {
        var pages = [Page(id: "card", title: "会員証", body: "お会計のときに\n画面を見せるだけ")]
        if pointsGuide.usePoints {
            pages.append(Page(id: "earn", title: "ポイントが貯まる", body: "\(pointsGuide.earnYenPerPoint)円ごとに1ポイント"))
            pages.append(Page(id: "use", title: "1ポイント＝1円", body: pointsGuide.usageText))
        }
        if pointsGuide.ranks.count > 1 {
            pages.append(Page(id: "rank", title: "ランク", body: "ご利用に応じてランクが上がります"))
        }
        pages.append(Page(id: "news", title: "クーポン・お知らせ", body: "おトクな情報が届きます"))
        return pages
    }

    var body: some View {
        let pages = pages
        let isLast = index >= pages.count - 1
        VStack(spacing: 0) {
            HStack {
                Spacer()
                if let onClose {
                    Button("閉じる", action: onClose)
                        .font(.subheadline.weight(.semibold))
                        .foregroundStyle(AppDesign.inkSecondary)
                }
            }
            .frame(height: 44)
            .padding(.horizontal, 20)

            GeometryReader { geometry in
                let width = geometry.size.width
                HStack(spacing: 0) {
                    ForEach(pages) { page in
                        pageView(page).frame(width: width)
                    }
                }
                .offset(x: -CGFloat(index) * width + dragOffset)
                .gesture(
                    DragGesture()
                        .onChanged { dragOffset = $0.translation.width }
                        .onEnded { value in
                            let threshold = width / 5
                            withAnimation(.easeOut(duration: 0.3)) {
                                if value.translation.width < -threshold, index < pages.count - 1 {
                                    index += 1
                                } else if value.translation.width > threshold, index > 0 {
                                    index -= 1
                                }
                                dragOffset = 0
                            }
                        }
                )
            }
            .clipped()

            VStack(spacing: 24) {
                HStack(spacing: 8) {
                    ForEach(0..<pages.count, id: \.self) { dot in
                        Capsule()
                            .fill(dot == index ? accent : AppDesign.hairline)
                            .frame(width: dot == index ? 22 : 8, height: 8)
                    }
                }
                AppPrimaryButton(title: isLast ? "はじめる" : "OK") {
                    if isLast {
                        onFinish()
                    } else {
                        withAnimation(.easeInOut(duration: 0.3)) { index += 1 }
                    }
                }
            }
            .padding(.horizontal, 24)
            .padding(.top, 8)
            .padding(.bottom, 24)
            .background(AppDesign.surface.ignoresSafeArea(edges: .bottom))
        }
        // 絵の部分はお店の色を薄く敷く。文字とボタンの部分は白で上書きする
        .background(accent.opacity(0.1).background(AppDesign.surface).ignoresSafeArea())
    }

    private func pageView(_ page: Page) -> some View {
        VStack(spacing: 0) {
            visual(for: page.id)
                .padding(.horizontal, 24)
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .accessibilityHidden(true)
            VStack(spacing: 10) {
                Text(page.title)
                    .font(.title.weight(.bold))
                    .foregroundStyle(AppDesign.ink)
                Text(page.body)
                    .font(.body.weight(.medium))
                    .foregroundStyle(AppDesign.inkSecondary)
                    .multilineTextAlignment(.center)
                    .fixedSize(horizontal: false, vertical: true)
            }
            .padding(.horizontal, 24)
            .padding(.top, 28)
            .padding(.bottom, 12)
            // 本文の行数が違っても白い部分の高さを揃え、スワイプ中に段差を出さない
            .frame(maxWidth: .infinity, minHeight: 150, alignment: .top)
            .background(AppDesign.surface)
        }
    }

    @ViewBuilder private func visual(for id: String) -> some View {
        switch id {
        case "card": memberCard
        case "earn": earnVisual
        case "use": useVisual
        case "rank": rankVisual
        default: newsVisual
        }
    }

    /// ランクの色。未設定ならお店の色
    private func rankFill(_ rank: PointsGuide.Rank?) -> Color {
        rank?.colorHex?.toNativeColor(fallback: accent) ?? accent
    }

    private func rankForeground(_ rank: PointsGuide.Rank?) -> Color {
        guard let hex = rank?.colorHex else { return accentForeground }
        return BrandPalette(hex: hex).usesDarkForeground ? AppDesign.ink : Color.white
    }

    /// 会員タブのカードと同じく、最初のランクの色で描く
    private var memberCard: some View {
        let rank = pointsGuide.ranks.first
        return VStack(spacing: 0) {
            HStack {
                VStack(alignment: .leading, spacing: 4) {
                    Text(storeName)
                        .font(.subheadline.weight(.semibold))
                        .lineLimit(1)
                        .opacity(0.75)
                    Text("会員証").font(.title2.weight(.bold))
                }
                Spacer(minLength: 8)
                if let rank {
                    Text(rank.label)
                        .font(.caption.weight(.bold))
                        .padding(.horizontal, 10)
                        .padding(.vertical, 5)
                        .background(Color.white.opacity(0.85))
                        .foregroundStyle(AppDesign.ink)
                        .clipShape(Capsule())
                }
            }
            .foregroundStyle(rankForeground(rank))
            .padding(18)
            VStack(spacing: 8) {
                MemberCodeView(value: "1234567890", height: 60)
                Text("1234567890")
                    .font(.system(.footnote, design: .monospaced).weight(.semibold))
                    .tracking(1.5)
                    .foregroundStyle(AppDesign.ink)
            }
            .padding(16)
            .frame(maxWidth: .infinity)
            .background(AppDesign.surface)
        }
        .background(rankFill(rank))
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusXL, style: .continuous))
        .shadow(color: Color.black.opacity(0.12), radius: 18, x: 0, y: 10)
        .frame(maxWidth: 340)
    }

    private func coin(_ value: String, unit: String, filled: Bool = true, size: CGFloat = 124) -> some View {
        HStack(alignment: .firstTextBaseline, spacing: 2) {
            Text(value).font(.system(size: size * 0.4, weight: .heavy, design: .rounded))
            Text(unit).font(.system(size: size * 0.18, weight: .bold))
        }
        .foregroundStyle(filled ? accentForeground : AppDesign.ink)
        .frame(width: size, height: size)
        .background(Circle().fill(filled ? accent : AppDesign.surface))
        .shadow(color: Color.black.opacity(0.1), radius: 12, x: 0, y: 6)
    }

    private var earnVisual: some View {
        HStack(spacing: 14) {
            VStack(spacing: 2) {
                Text("お買い物").font(.subheadline.weight(.medium)).foregroundStyle(AppDesign.inkSecondary)
                Text("\(pointsGuide.earnYenPerPoint)円")
                    .font(.system(size: 34, weight: .heavy, design: .rounded))
                    .foregroundStyle(AppDesign.ink)
                    .lineLimit(1)
                    .minimumScaleFactor(0.6)
            }
            .padding(.horizontal, 18)
            .padding(.vertical, 16)
            .background(AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
            .shadow(color: Color.black.opacity(0.08), radius: 12, x: 0, y: 6)
            FontAwesomeIcon(systemName: "chevron.right", size: 22)
                .foregroundStyle(accent)
            coin("1", unit: "pt")
        }
    }

    private var useVisual: some View {
        VStack(spacing: 24) {
            HStack(spacing: 14) {
                coin("1", unit: "pt", size: 108)
                Text("=").font(.system(size: 40, weight: .heavy)).foregroundStyle(accent)
                coin("1", unit: "円", filled: false, size: 108)
            }
            HStack(spacing: 10) {
                ForEach(pointsGuide.usagePlaces, id: \.self) { place in
                    HStack(spacing: 6) {
                        FontAwesomeIcon(systemName: "checkmark", size: 13).foregroundStyle(accent)
                        Text(place).font(.subheadline.weight(.bold)).foregroundStyle(AppDesign.ink)
                    }
                    .padding(.horizontal, 14)
                    .padding(.vertical, 10)
                    .background(Capsule().fill(AppDesign.surface))
                }
            }
        }
    }

    /// 下のランクから順に段が高くなる。各段はランクの色
    private var rankVisual: some View {
        let ranks = pointsGuide.ranks
        return HStack(alignment: .bottom, spacing: 8) {
            ForEach(0..<ranks.count, id: \.self) { position in
                let rank = ranks[position]
                Text(rank.label)
                    .font(.footnote.weight(.bold))
                    .foregroundStyle(rankForeground(rank))
                    .lineLimit(2)
                    .minimumScaleFactor(0.7)
                    .multilineTextAlignment(.center)
                    .padding(.horizontal, 4)
                    .padding(.top, 12)
                    .frame(maxWidth: .infinity)
                    .frame(height: 72 + CGFloat(position) * 120 / CGFloat(max(1, ranks.count - 1)), alignment: .top)
                    .background(rankFill(rank))
                    .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous))
                    .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusM, style: .continuous)
                        .stroke(Color.black.opacity(0.08), lineWidth: 1))
            }
        }
        .frame(maxWidth: 340)
    }

    private func noticeRow(icon: String, title: String, detail: String) -> some View {
        HStack(spacing: 14) {
            FontAwesomeIcon(systemName: icon, size: 20)
                .foregroundStyle(accentForeground)
                .frame(width: 48, height: 48)
                .background(Circle().fill(accent))
            VStack(alignment: .leading, spacing: 2) {
                Text(title).font(.headline).foregroundStyle(AppDesign.ink)
                Text(detail).font(.subheadline).foregroundStyle(AppDesign.inkSecondary)
            }
            Spacer(minLength: 0)
        }
        .padding(16)
        .background(AppDesign.surface)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL, style: .continuous))
        .shadow(color: Color.black.opacity(0.08), radius: 12, x: 0, y: 6)
    }

    private var newsVisual: some View {
        VStack(spacing: 12) {
            noticeRow(icon: "ticket.fill", title: "クーポン", detail: "お会計で使える特典")
            noticeRow(icon: "bell.fill", title: "お知らせ", detail: "お店からの最新情報")
        }
        .frame(maxWidth: 340)
    }
}
