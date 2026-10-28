import Foundation

/// 起動時に表示する宣伝ポップアップ（GET /api/promo-popups）。
struct PromoPopup: Identifiable, Equatable, Sendable {
    enum DisplayType: String, Sendable {
        case fullscreen
        case modal
        case bottomSheet = "bottom_sheet"
        case banner
    }

    enum Frequency: String, Sendable {
        case once
        case daily
        case everyOpen = "every_open"
    }

    /// アプリから送る反応（集計用）
    enum Event: String, Sendable {
        case view
        case click
        case secondaryClick = "secondary_click"
        case close
    }

    /// ボタン・お知らせから開く遷移先
    enum Link: Equatable, Sendable {
        case none
        /// アプリ内パス（/coupon など）の先頭セグメント
        case tab(String)
        /// アンケート。番号がなければ回答できる一覧
        case survey(Int?)
        case external(URL)

        init(rawValue: String?) {
            let value = rawValue?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
            if value.hasPrefix("https://"), let url = URL(string: value) {
                self = .external(url)
            } else if value.hasPrefix("/"), !value.hasPrefix("//"),
                      let tab = value.dropFirst().split(whereSeparator: { $0 == "?" || $0 == "/" }).first {
                if tab == "survey" {
                    let query = URLComponents(string: value)?.queryItems ?? []
                    self = .survey(query.first(where: { $0.name == "survey" })?.value.flatMap { Int($0) })
                } else {
                    self = .tab(String(tab))
                }
            } else {
                self = .none
            }
        }
    }

    let id: Int
    let title: String
    let body: String
    let imageURL: String?
    let displayType: DisplayType
    let buttonLabel: String?
    let link: Link
    /// 2つ目のボタン（任意）。文言が無ければ出さない
    let secondaryButtonLabel: String?
    let secondaryLink: Link
    let frequency: Frequency
    /// 内容を編集したら表示済みを引き継がないための版
    let version: Int

    /// 表示済みの記録に使うキー。版を含めて、編集後は再表示する
    var seenKey: String { "\(id):\(version)" }

    var actionTitle: String {
        if let buttonLabel = buttonLabel?.trimmedNonEmpty { return buttonLabel }
        return link == .none ? "閉じる" : "詳しく見る"
    }

    static func parseList(_ data: Data) throws -> [PromoPopup] {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw ReggitAPIError.invalidResponse
        }
        return ((json["popups"] as? [[String: Any]]) ?? []).compactMap { row in
            guard let id = row["id"] as? Int,
                  let displayType = DisplayType(rawValue: (row["display_type"] as? String) ?? ""),
                  let frequency = Frequency(rawValue: (row["frequency"] as? String) ?? "") else {
                return nil
            }
            return PromoPopup(
                id: id,
                title: (row["title"] as? String) ?? "",
                body: (row["body"] as? String) ?? "",
                imageURL: (row["image_url"] as? String)?.trimmedNonEmpty,
                displayType: displayType,
                buttonLabel: row["button_label"] as? String,
                link: Link(rawValue: row["link_url"] as? String),
                secondaryButtonLabel: (row["secondary_button_label"] as? String)?.trimmedNonEmpty,
                secondaryLink: Link(rawValue: row["secondary_link_url"] as? String),
                frequency: frequency,
                version: (row["version"] as? Int) ?? 0
            )
        }
    }
}
