import Foundation
import Observation

/// アンケート画面。番号がなければ回答できる一覧、あれば回答フォームを扱う。
@MainActor
@Observable
final class SurveyViewModel {
    private(set) var surveys: [SurveyDetail] = []
    private(set) var survey: SurveyDetail?
    private(set) var isLoading = false
    private(set) var isSubmitting = false
    private(set) var errorMessage: String?
    /// 質問番号 => 項目のエラー
    private(set) var fieldErrors: [Int: String] = [:]
    /// 回答が終わったら、付与したポイント
    private(set) var grantedPoints: Int?
    var answers: [Int: SurveyAnswerValue] = [:]
    /// 質問番号 => 「その他」に入力した本文
    var otherTexts: [Int: String] = [:]

    /// 回答済みの質問数（進み具合の表示用）
    var answeredCount: Int {
        survey?.questions.indices.filter { resolvedAnswer(at: $0) != nil }.count ?? 0
    }

    private let service: any SurveyServing

    init(service: any SurveyServing) {
        self.service = service
    }

    func load(surveyID: Int?) async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            if let surveyID {
                survey = try await service.fetchSurvey(id: surveyID)
            } else {
                survey = nil
                surveys = try await service.fetchOpenSurveys()
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    /// 必須の未回答は送る前に知らせ、形式の誤りはサーバーの結果を質問ごとに出す
    func submit() async {
        guard let survey, !isSubmitting else { return }
        fieldErrors = [:]
        errorMessage = nil
        for (index, question) in survey.questions.enumerated() where question.required && resolvedAnswer(at: index) == nil {
            fieldErrors[index] = "「\(question.label)」に回答してください。"
        }
        guard fieldErrors.isEmpty else { return }
        isSubmitting = true
        defer { isSubmitting = false }
        do {
            let payload = survey.questions.indices.map(resolvedAnswer(at:))
            grantedPoints = try await service.submitSurveyAnswers(id: survey.id, answers: payload)
        } catch ReggitAPIError.validation(let errors) {
            for (key, message) in errors {
                if let index = Int(key.replacingOccurrences(of: "answers.", with: "")) {
                    fieldErrors[index] = message
                } else {
                    errorMessage = message
                }
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func resolvedAnswer(at index: Int) -> SurveyAnswerValue? {
        SurveyOtherAnswer.resolve(answers[index], otherText: otherTexts[index])
    }

    func isSelected(_ option: String, at index: Int) -> Bool {
        switch answers[index] {
        case .text(let value): return value == option
        case .selections(let values): return values.contains(option)
        default: return false
        }
    }

    func toggle(_ option: String, at index: Int) {
        var selected: [String] = []
        if case .selections(let current) = answers[index] { selected = current }
        if let position = selected.firstIndex(of: option) {
            selected.remove(at: position)
        } else {
            selected.append(option)
        }
        answers[index] = .selections(selected)
    }
}
