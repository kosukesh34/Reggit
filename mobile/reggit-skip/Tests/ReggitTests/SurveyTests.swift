import Foundation
import XCTest
@testable import Reggit

private final class StubSurveyService: SurveyServing, @unchecked Sendable {
    var detail: SurveyDetail
    var submitError: Error?
    private(set) var submitted: [SurveyAnswerValue?] = []

    init(detail: SurveyDetail, submitError: Error? = nil) {
        self.detail = detail
        self.submitError = submitError
    }

    func fetchOpenSurveys() async throws -> [SurveyDetail] { [detail] }
    func fetchSurvey(id: Int) async throws -> SurveyDetail { detail }
    func submitSurveyAnswers(id: Int, answers: [SurveyAnswerValue?]) async throws -> Int {
        submitted = answers
        if let submitError { throw submitError }
        return detail.rewardPoints
    }
}

@MainActor
final class SurveyTests: XCTestCase {
    private let json = """
    {"survey":{"id":7,"title":"ご来店アンケート","description":"","reward_points":30,"is_open":true,"answered":false,"questions":[
      {"type":"nps","label":"おすすめ度","required":true},
      {"type":"multi_choice","label":"目的","help":"いくつでも","options":["ランチ","ディナー"],"allow_other":true,"required":false},
      {"type":"future_type","label":"新しい形式","required":false},
      {"type":"text","label":"","required":false}
    ]}}
    """

    func testParseSurveyKeepsKnownTypesAndFallsBackToText() throws {
        let survey = try SurveyDetail.parseDetail(Data(json.utf8))

        XCTAssertEqual(survey.id, 7)
        XCTAssertEqual(survey.questions.map(\.kind), [.nps, .multiChoice, .text])
        XCTAssertEqual(survey.questions[1].options, ["ランチ", "ディナー"])
        XCTAssertEqual(survey.questions[1].help, "いくつでも")
        XCTAssertTrue(survey.questions[1].allowOther)
        XCTAssertFalse(survey.questions[0].allowOther)
        XCTAssertEqual(PromoPopup.Link(rawValue: "/survey?survey=7"), .survey(7))
        XCTAssertEqual(PromoPopup.Link(rawValue: "/survey"), .survey(nil))
    }

    func testSubmitChecksRequiredAndSendsAnswersInQuestionOrder() async throws {
        let service = StubSurveyService(detail: try SurveyDetail.parseDetail(Data(json.utf8)))
        let viewModel = SurveyViewModel(service: service)
        await viewModel.load(surveyID: 7)

        await viewModel.submit()
        XCTAssertEqual(viewModel.fieldErrors[0], "「おすすめ度」に回答してください。")
        XCTAssertNil(viewModel.grantedPoints)

        viewModel.answers[0] = .integer(9)
        viewModel.toggle("ディナー", at: 1)
        viewModel.toggle("ランチ", at: 1)
        viewModel.toggle("ディナー", at: 1)
        await viewModel.submit()

        XCTAssertEqual(service.submitted, [.integer(9), .selections(["ランチ"]), nil])
        XCTAssertEqual(viewModel.grantedPoints, 30)
    }

    func testServerValidationErrorsAreShownPerQuestion() async throws {
        let service = StubSurveyService(detail: try SurveyDetail.parseDetail(Data(json.utf8)), submitError: ReggitAPIError.validation(["answers.1": "「目的」の回答を確認してください。", "survey": "このアンケートは回答済みです。"]))
        let viewModel = SurveyViewModel(service: service)
        await viewModel.load(surveyID: 7)
        viewModel.answers[0] = .integer(3)

        await viewModel.submit()

        XCTAssertEqual(viewModel.fieldErrors[1], "「目的」の回答を確認してください。")
        XCTAssertEqual(viewModel.errorMessage, "このアンケートは回答済みです。")
    }

    func testOtherAnswerIsSentWithPrefixAndEmptyOtherIsDropped() async throws {
        let service = StubSurveyService(detail: try SurveyDetail.parseDetail(Data(json.utf8)))
        let viewModel = SurveyViewModel(service: service)
        await viewModel.load(surveyID: 7)
        viewModel.answers[0] = .integer(10)
        viewModel.toggle("ランチ", at: 1)
        viewModel.toggle(SurveyOtherAnswer.key, at: 1)
        XCTAssertEqual(viewModel.answeredCount, 2)

        viewModel.otherTexts[1] = "  テイクアウト "
        await viewModel.submit()

        XCTAssertEqual(service.submitted, [.integer(10), .selections(["ランチ", "その他：テイクアウト"]), nil])
        XCTAssertEqual(SurveyOtherAnswer.resolve(.selections([SurveyOtherAnswer.key]), otherText: " "), nil)
        XCTAssertEqual(SurveyOtherAnswer.resolve(.text(SurveyOtherAnswer.key), otherText: "駐車場"), .text("その他：駐車場"))
        XCTAssertEqual(SurveyOtherAnswer.resolve(.text(""), otherText: nil), nil)
    }
}
