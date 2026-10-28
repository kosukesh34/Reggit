import Foundation
import XCTest
@testable import Reggit

private struct StubPromoPopupSource: PromoPopupFetching {
    var popups: [PromoPopup] = []
    var shouldFail = false

    func fetchPromoPopups() async throws -> [PromoPopup] {
        if shouldFail { throw ReggitAPIError.invalidResponse }
        return popups
    }

    func recordPromoPopupEvent(id: Int, event: PromoPopup.Event) async throws {}
}

private final class InMemoryPromoPopupSeenStore: PromoPopupSeenStoring {
    var days: [String: String] = [:]

    func lastSeenDay(projectKey: String, popupKey: String) -> String? { days["\(projectKey):\(popupKey)"] }
    func markSeen(projectKey: String, popupKey: String, day: String) { days["\(projectKey):\(popupKey)"] = day }
}

@MainActor
final class PromoPopupTests: XCTestCase {
    private func popup(_ id: Int, _ frequency: PromoPopup.Frequency, version: Int = 1) -> PromoPopup {
        PromoPopup(id: id, title: "告知\(id)", body: "", imageURL: nil, displayType: .modal, buttonLabel: nil, link: .none, secondaryButtonLabel: "あとで", secondaryLink: .tab("menu"), frequency: frequency, version: version)
    }

    func testParseListSkipsUnknownTypesAndReadsLinks() throws {
        let json = """
        {"popups":[
          {"id":1,"title":"全画面","display_type":"fullscreen","frequency":"once","link_url":"/coupon?x=1","secondary_button_label":"あとで","secondary_link_url":"/menu","version":10},
          {"id":2,"title":"外部","display_type":"bottom_sheet","frequency":"every_open","link_url":"https://example.com/sale"},
          {"id":3,"title":"不明","display_type":"carousel","frequency":"once"},
          {"id":4,"title":"危険","display_type":"banner","frequency":"daily","link_url":"javascript:alert(1)"}
        ]}
        """
        let popups = try PromoPopup.parseList(Data(json.utf8))

        XCTAssertEqual(popups.map(\.id), [1, 2, 4])
        XCTAssertEqual(popups[0].displayType, .fullscreen)
        XCTAssertEqual(popups[0].link, .tab("coupon"))
        XCTAssertEqual(popups[0].seenKey, "1:10")
        XCTAssertEqual(popups[0].secondaryButtonLabel, "あとで")
        XCTAssertEqual(popups[0].secondaryLink, .tab("menu"))
        XCTAssertNil(popups[1].secondaryButtonLabel)
        XCTAssertEqual(popups[1].displayType, .bottomSheet)
        XCTAssertEqual(popups[1].link, .external(URL(string: "https://example.com/sale")!))
        XCTAssertEqual(popups[2].link, .none)
        XCTAssertEqual(popups[2].actionTitle, "閉じる")
        XCTAssertEqual(PromoPopup.Link(rawValue: "//evil.example.com"), .none)
    }

    func testPolicyFollowsFrequency() {
        let today = "2026-10-27"
        XCTAssertTrue(PromoPopupDisplayPolicy.shouldShow(.once, lastSeenDay: nil, today: today))
        XCTAssertFalse(PromoPopupDisplayPolicy.shouldShow(.once, lastSeenDay: "2026-10-01", today: today))
        XCTAssertFalse(PromoPopupDisplayPolicy.shouldShow(.daily, lastSeenDay: today, today: today))
        XCTAssertTrue(PromoPopupDisplayPolicy.shouldShow(.daily, lastSeenDay: "2026-10-26", today: today))
        XCTAssertTrue(PromoPopupDisplayPolicy.shouldShow(.everyOpen, lastSeenDay: today, today: today))
        let picked = PromoPopupDisplayPolicy.next(
            from: [popup(1, .once), popup(2, .daily)],
            today: today,
            shownThisLaunch: [],
            lastSeenDay: { $0.id == 1 ? "2026-10-01" : nil }
        )
        XCTAssertEqual(picked?.id, 2)
    }

    func testViewModelShowsOnePopupPerLaunchAndRemembersIt() async {
        let store = InMemoryPromoPopupSeenStore()
        let source = StubPromoPopupSource(popups: [popup(1, .once), popup(2, .everyOpen)])
        let fixedDate = Date(timeIntervalSince1970: 1_793_000_000)
        let viewModel = PromoPopupViewModel(seenStore: store, now: { fixedDate })

        await viewModel.load(from: source, projectKey: "demo")
        XCTAssertEqual(viewModel.current?.id, 1)
        XCTAssertEqual(viewModel.takeAction(), .none)
        XCTAssertNil(viewModel.current)

        // 同じ起動中は再取得しない
        await viewModel.load(from: source, projectKey: "demo")
        XCTAssertNil(viewModel.current)

        // 次の起動では「1人1回だけ」は出さず、「起動のたび」を出す
        let relaunched = PromoPopupViewModel(seenStore: store, now: { fixedDate })
        await relaunched.load(from: source, projectKey: "demo")
        XCTAssertEqual(relaunched.current?.id, 2)
        XCTAssertEqual(relaunched.takeAction(secondary: true), .tab("menu"))

        // 編集して版が変わったら、もう一度出す
        let edited = PromoPopupViewModel(seenStore: store, now: { fixedDate })
        await edited.load(from: StubPromoPopupSource(popups: [popup(1, .once, version: 2)]), projectKey: "demo")
        XCTAssertEqual(edited.current?.id, 1)
    }

    func testViewModelIgnoresFetchFailure() async {
        let viewModel = PromoPopupViewModel(seenStore: InMemoryPromoPopupSeenStore())
        await viewModel.load(from: StubPromoPopupSource(shouldFail: true), projectKey: "demo")
        XCTAssertNil(viewModel.current)
    }
}
