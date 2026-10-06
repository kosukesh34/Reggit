import XCTest
@testable import Reggit

final class ReorderCartPlanTests: XCTestCase {
    private func product(stock: Int = 10, available: Bool = true) -> ProductItem {
        ProductItem(id: 1, name: "コーヒー", description: "", price: 600, isAvailable: available,
                    stock: stock, category: "", imageURL: nil,
                    optionGroups: [ProductOptionGroupItem(id: 1, name: "サイズ", selectionType: "single", isRequired: true,
                        options: [ProductOptionItem(id: 8, name: "L", priceDelta: 100)])])
    }

    private func order(optionIds: [Int]? = [8], notes: String? = "L / 砂糖なし", quantity: Int = 2) -> OrderSnapshot {
        OrderSnapshot(id: 1, orderNumber: "1", totalAmount: 1000, status: "completed", paymentMethod: "cash", createdAt: "",
                      items: [OrderLineItem(id: 0, productName: "コーヒー", quantity: quantity, price: 500,
                                            productId: 1, optionIds: optionIds, notes: notes)])
    }

    func testRestoresQuantityOptionsAndCustomNotesAtCurrentPrice() throws {
        let current = product()
        let plan = try ReorderCartPlan.make(order: order(), products: [current])
        XCTAssertEqual(plan.quantities, [1: 2])
        XCTAssertEqual(plan.options, [1: [8]])
        XCTAssertEqual(plan.notes, [1: "砂糖なし"])
        XCTAssertEqual(current.price + current.optionGroups[0].options[0].priceDelta, 700)
    }

    func testRestoresLegacyOptionNames() throws {
        let plan = try ReorderCartPlan.make(order: order(optionIds: nil), products: [product()])
        XCTAssertEqual(plan.options, [1: [8]])
        XCTAssertEqual(plan.notes[1], "砂糖なし")
    }

    func testRejectsUnavailableStockAndRemovedOrMissingOptions() {
        XCTAssertThrowsError(try ReorderCartPlan.make(order: order(), products: []))
        XCTAssertThrowsError(try ReorderCartPlan.make(order: order(), products: [product(available: false)]))
        XCTAssertThrowsError(try ReorderCartPlan.make(order: order(), products: [product(stock: 1)]))
        XCTAssertThrowsError(try ReorderCartPlan.make(order: order(optionIds: [99]), products: [product()]))
        XCTAssertThrowsError(try ReorderCartPlan.make(order: order(optionIds: []), products: [product()]))
        XCTAssertThrowsError(try ReorderCartPlan.make(order: order(optionIds: nil, notes: "旧サイズ"), products: [product()]))
    }

    func testParsingKeepsProductIDsOptionsAndNotes() throws {
        let parsed = try OrderSnapshot.parse(Data("""
        {"id":1,"items":[{"product_id":1,"quantity":2,"option_ids":[8],"notes":"L / 砂糖なし"}]}
        """.utf8))
        XCTAssertEqual(parsed.items.first?.productId, 1)
        XCTAssertEqual(parsed.items.first?.optionIds, [8])
        XCTAssertEqual(parsed.items.first?.notes, "L / 砂糖なし")
    }
}
