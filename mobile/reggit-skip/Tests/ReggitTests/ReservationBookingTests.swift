import XCTest
@testable import Reggit

final class ReservationBookingTests: XCTestCase {
    func testAvailabilityParsesSlotsCoursesAndMaxPartySize() throws {
        let data = try JSONSerialization.data(withJSONObject: [
            "available_dates": [["date": "2026-07-11", "display": "2026年07月11日(土)", "time_slots": ["12:00", "18:00"]]],
            "courses": [["id": 3, "name": "ランチ", "price": 3000]],
            "max_party_size": 6,
        ])
        let availability = try ReservationAvailability.parse(data)
        XCTAssertEqual(availability.dates, [ReservationDateOption(date: "2026-07-11", display: "07月11日(土)", timeSlots: ["12:00", "18:00"])])
        XCTAssertEqual(availability.courses.first?.name, "ランチ")
        XCTAssertEqual(availability.maxPartySize, 6)
    }

    func testStoreDateTimeUsesServerTimeRegardlessOfDeviceTimeZone() {
        let value = ReservationStoreDateTime(iso: "2026-07-11T12:00:00+09:00")
        XCTAssertEqual(value.date, "2026-07-11")
        XCTAssertEqual(value.time, "12:00")
        XCTAssertEqual(ReservationStoreDateTime(iso: nil).date, "")
        XCTAssertEqual(ReservationStoreDateTime.requestValue(date: "2026-07-11", time: "12:00"), "2026-07-11T12:00")
    }

    func testChangeNoteShowsDeadlineOrStoreContact() throws {
        let data = try JSONSerialization.data(withJSONObject: ["reservations": [
            ["id": 1, "status": "pending", "reserved_at": "2026-07-11T12:00:00+09:00", "change_deadline": "2026-07-10T12:00:00+09:00", "can_change": true, "reservation_course_id": 3],
            ["id": 2, "status": "confirmed", "reserved_at": "2026-07-11T12:00:00+09:00", "can_change": false],
            ["id": 3, "status": "cancelled", "reserved_at": "2026-07-11T12:00:00+09:00", "can_change": false],
        ]])
        let items = try ReservationListResponse.parse(data).reservations
        XCTAssertEqual(items[0].changeNote, "7月10日 12:00 まで変更・キャンセルできます")
        XCTAssertEqual(items[0].courseId, 3)
        XCTAssertTrue(items[0].isCancellable)
        XCTAssertEqual(items[1].changeNote, "変更・キャンセルは店舗へ直接ご連絡ください")
        XCTAssertFalse(items[1].isCancellable)
        XCTAssertNil(items[2].changeNote)
    }
}
