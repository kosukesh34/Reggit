import Foundation

/// 会員アプリのアンケートの取得と回答。アンケート画面はこの境界だけに依存する。
protocol SurveyServing: Sendable {
    func fetchOpenSurveys() async throws -> [SurveyDetail]
    func fetchSurvey(id: Int) async throws -> SurveyDetail
    /// - Parameter answers: 質問の並びと同じ順の回答（未回答は nil）
    /// - Returns: 付与したポイント
    func submitSurveyAnswers(id: Int, answers: [SurveyAnswerValue?]) async throws -> Int
}
