import Foundation

/// 会員アプリのアンケート（GET /api/surveys・/api/surveys/{id}）。
struct SurveyQuestion: Equatable, Sendable {
    enum Kind: String, Sendable {
        case nps
        case choice
        case multiChoice = "multi_choice"
        case rating
        case number
        case date
        case text
    }

    let kind: Kind
    let label: String
    /// 質問の下に出す補足
    let help: String
    let options: [String]
    /// 選択肢に「その他（自由入力）」を出すか
    let allowOther: Bool
    let required: Bool
}

struct SurveyDetail: Identifiable, Equatable, Sendable {
    let id: Int
    let title: String
    let description: String
    let questions: [SurveyQuestion]
    let rewardPoints: Int
    let isOpen: Bool
    let answered: Bool

    static func parse(_ row: [String: Any]) -> SurveyDetail? {
        guard let id = row["id"] as? Int else { return nil }
        let questions = ((row["questions"] as? [[String: Any]]) ?? []).compactMap { question -> SurveyQuestion? in
            // 新しい形式がアプリより先に追加されても、知らない質問は自由記述として答えられるようにする
            let kind = SurveyQuestion.Kind(rawValue: (question["type"] as? String) ?? "") ?? .text
            guard let label = (question["label"] as? String)?.trimmedNonEmpty else { return nil }
            return SurveyQuestion(
                kind: kind,
                label: label,
                help: (question["help"] as? String) ?? "",
                options: (question["options"] as? [String]) ?? [],
                allowOther: question["allow_other"] as? Bool ?? false,
                required: question["required"] as? Bool ?? false
            )
        }
        return SurveyDetail(
            id: id,
            title: (row["title"] as? String) ?? "",
            description: (row["description"] as? String) ?? "",
            questions: questions,
            rewardPoints: (row["reward_points"] as? Int) ?? 0,
            isOpen: row["is_open"] as? Bool ?? false,
            answered: row["answered"] as? Bool ?? false
        )
    }

    static func parseList(_ data: Data) throws -> [SurveyDetail] {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any] else { throw ReggitAPIError.invalidResponse }
        return ((json["surveys"] as? [[String: Any]]) ?? []).compactMap(parse)
    }

    static func parseDetail(_ data: Data) throws -> SurveyDetail {
        guard let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
              let row = json["survey"] as? [String: Any], let survey = parse(row) else { throw ReggitAPIError.invalidResponse }
        return survey
    }
}

/// 1問分の回答。数値・日付は文字のまま送り、サーバーで検証する。
enum SurveyAnswerValue: Equatable, Sendable {
    case integer(Int)
    case text(String)
    case selections([String])

    var isEmpty: Bool {
        switch self {
        case .integer: return false
        case .text(let value): return value.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
        case .selections(let values): return values.isEmpty
        }
    }

    var jsonValue: Any {
        switch self {
        case .integer(let value): return value
        case .text(let value): return value
        case .selections(let values): return values
        }
    }
}

/// 選択肢の「その他（自由入力）」。画面では印として選び、送るときに「その他：本文」へ置き換える。
enum SurveyOtherAnswer {
    static let key = "__other__"
    static let label = "その他（自由入力）"
    static let prefix = "その他："
    /// サーバーは「その他：」を含めて200文字まで受け付ける
    static let maxLength = 200 - 4

    /// 送信用の回答。「その他」を選んで本文が空なら、その分は未回答として扱う。
    static func resolve(_ value: SurveyAnswerValue?, otherText: String?) -> SurveyAnswerValue? {
        let trimmed = (otherText ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let other: String? = trimmed.isEmpty ? nil : prefix + String(trimmed.prefix(maxLength))
        switch value {
        case .text(key):
            return other.map { .text($0) }
        case .selections(let values):
            let resolved = values.compactMap { $0 == key ? other : $0 }
            return resolved.isEmpty ? nil : .selections(resolved)
        case .some(let answer):
            return answer.isEmpty ? nil : answer
        case .none:
            return nil
        }
    }
}
