import XCTest
@testable import Reggit

final class NativeAPIModelsTests: XCTestCase {
    func testUnpaidStripeOrderDoesNotShowPickupNumber() {
        let unpaid = OrderSnapshot(id: 1, orderNumber: "202609241", totalAmount: 1200, status: "pending", paymentMethod: "stripe", createdAt: "", items: [])
        XCTAssertTrue(unpaid.isAwaitingCardPayment)
        XCTAssertFalse(unpaid.hasPickupNumber)
        XCTAssertEqual(unpaid.pickupNumberLabel, "決済確認中")

        let paid = OrderSnapshot(id: 1, orderNumber: "202609241", totalAmount: 1200, status: "confirmed", paymentMethod: "stripe", createdAt: "", items: [])
        XCTAssertFalse(paid.isAwaitingCardPayment)
        XCTAssertTrue(paid.hasPickupNumber)
    }
    func testAppleDeviceNames() {
        XCTAssertEqual(PlatformRuntime.appleDeviceName(identifier: "iPhone12,8"), "iPhone SE（第2世代）")
        XCTAssertEqual(PlatformRuntime.appleDeviceName(identifier: "iPhone18,3"), "iPhone 17")
        XCTAssertEqual(PlatformRuntime.appleDeviceName(identifier: "iPhone99,1"), "iPhone99,1")
    }
    func testAppConfigParsesNavigationHeaderImage() throws {
        let data = try JSONSerialization.data(withJSONObject: [
            "project_name": "Demo",
            "theme": ["primary_color": "#173F35"],
            "header": ["image_url": "https://example.com/header.webp", "background_color": "#F6F1E8"],
            "realtime": ["websocket_url": "wss://example.com/app/demo?protocol=7"],
            "home": [
                "menu_layout": "grid_3x2",
                "image_url": "https://example.com/home.webp",
                "image_urls": ["https://example.com/home.webp", "https://example.com/second.webp"],
            ],
            "products": ["display_layout": "grid_2_columns"],
            "member_screen": ["show_rank_progress": true, "show_point_history": false, "show_coupon_activity": true],
            "tabs": [["key": "home", "label": "ホーム"]],
            "navigation": [
                "header": [["key": "member", "label": "会員証", "sf_symbol": "person.crop.circle.fill"]],
                "footer": [["key": "home", "label": "ホーム", "sf_symbol": "house.fill"]],
                "hamburger": [["key": "terms", "label": "利用規約", "sf_symbol": "doc.text.fill", "url": "https://example.com/legal/default/terms"]],
            ],
        ])
        let config = try XCTUnwrap(AppConfigClient.parse(data))
        XCTAssertEqual(config.navigationHeaderImageURL, "https://example.com/header.webp")
        XCTAssertEqual(config.navigationHeaderBackgroundColor, "#F6F1E8")
        XCTAssertEqual(config.homeImageURL, "https://example.com/home.webp")
        XCTAssertEqual(config.homeImageURLs, ["https://example.com/home.webp", "https://example.com/second.webp"])
        XCTAssertEqual(config.realtimeWebSocketURL, "wss://example.com/app/demo?protocol=7")
        XCTAssertEqual(config.homeMenuLayout, "grid_3x2")
        XCTAssertEqual(config.productDisplayLayout, "grid_2_columns")
        XCTAssertEqual(config.headerItems.map(\.key), ["member"])
        XCTAssertEqual(config.footerItems.map(\.key), ["home"])
        XCTAssertEqual(config.hamburgerItems.first?.url, "https://example.com/legal/default/terms")
        XCTAssertTrue(config.hasNavigationConfiguration)
        XCTAssertFalse(config.memberShowPointHistory)
    }

    func testAppConfigFallsBackToSingleHomeImage() throws {
        let data = try JSONSerialization.data(withJSONObject: [
            "project_name": "Demo",
            "home": ["image_url": "https://example.com/legacy.webp"],
        ])
        let config = try XCTUnwrap(AppConfigClient.parse(data))
        XCTAssertEqual(config.homeImageURLs, ["https://example.com/legacy.webp"])
    }

    func testAPIDateFormattingHidesTimezoneSuffix() {
        XCTAssertEqual(AppFormat.dateTime("2026-09-19T12:34:56+09:00"), "2026/09/19 12:34")
        XCTAssertEqual(AppFormat.date("2026-09-19T12:34:56+09:00"), "2026/09/19")
    }

    func testParseMemberProfile() throws {
        let json = """
        {
          "is_member": true,
          "user_id": 42,
          "member": {"id": 24, "member_number": "M-001"},
          "points": 120,
          "current_rank_label": "ゴールド",
          "next_rank_label": "プラチナ",
          "points_to_next_rank": 550,
          "show_rank_progress": true,
          "member_card_code_type": "barcode",
          "member_registration_mode": "force",
          "profile": { "name": "山田", "phone": "09011112222" },
          "member": { "member_number": "M-001" }
        }
        """.data(using: .utf8)!

        let snapshot = try MemberProfileSnapshot.parse(json)
        XCTAssertTrue(snapshot.isMember)
        XCTAssertEqual(snapshot.userId, 42)
        XCTAssertEqual(snapshot.memberId, 24)
        XCTAssertEqual(snapshot.name, "山田")
        XCTAssertEqual(snapshot.phone, "09011112222")
        XCTAssertEqual(snapshot.memberNumber, "M-001")
        XCTAssertEqual(snapshot.points, 120)
        XCTAssertEqual(snapshot.rankLabel, "ゴールド")
        XCTAssertEqual(snapshot.nextRankLabel, "プラチナ")
        XCTAssertEqual(snapshot.pointsToNextRank, 550)
        XCTAssertTrue(snapshot.showRankProgress)
        XCTAssertEqual(snapshot.cardCodeType, "barcode")
        XCTAssertEqual(snapshot.registrationMode, .force)
        XCTAssertTrue(snapshot.registrationMode.requiresRegistrationGate)
    }

    func testOrderProgressAndCouponTabs() {
        XCTAssertEqual(OrderProgress.index(for: "pending"), 0)
        XCTAssertEqual(OrderProgress.index(for: "preparing"), 1)
        XCTAssertEqual(OrderProgress.index(for: "ready"), 2)
        XCTAssertEqual(OrderProgress.index(for: "completed"), 3)
        XCTAssertTrue(OrderProgress.ACTIVE_STATUSES.contains("ready"))

        let available = CouponItem(
            id: 1, name: "A", status: "available", description: "",
            validUntil: "", useDisplay: "", imageURL: nil
        )
        let used = CouponItem(
            id: 2, name: "B", status: "used", description: "",
            validUntil: "", useDisplay: "", imageURL: nil
        )
        XCTAssertTrue(available.isAvailableTab)
        XCTAssertEqual(available.statusLabel, "利用可能")
        XCTAssertTrue(used.isArchiveTab)
        XCTAssertEqual(used.statusLabel, "利用済み")
    }

    func testRegistrationGateOnlyForForceAndPrompt() {
        XCTAssertTrue(MemberRegistrationMode.force.requiresRegistrationGate)
        XCTAssertTrue(MemberRegistrationMode.prompt.requiresRegistrationGate)
        XCTAssertFalse(MemberRegistrationMode.optional.requiresRegistrationGate)
        XCTAssertFalse(MemberRegistrationMode.popup.requiresRegistrationGate)
        XCTAssertFalse(MemberRegistrationMode.force.canSkipRegistration)
        XCTAssertTrue(MemberRegistrationMode.prompt.canSkipRegistration)
    }

    func testParseCoupons() throws {
        let json = """
        {
          "coupons": [
            {
              "id": 3,
              "coupon_name": "10%OFF",
              "status": "available",
              "description": "全品",
              "valid_until": "2026-12-31",
              "use_display": "提示",
              "image_url": "https://example.com/c.png"
            }
          ]
        }
        """.data(using: .utf8)!

        let coupons = try CouponListResponse.parse(json).coupons
        XCTAssertEqual(coupons.count, 1)
        XCTAssertEqual(coupons[0].id, 3)
        XCTAssertTrue(coupons[0].isUsable)
        XCTAssertEqual(coupons[0].imageURL, "https://example.com/c.png")
    }

    func testParseCouponRedemptionBarcode() throws {
        let json = """
        {
          "success": true,
          "data": {
            "id": 3,
            "coupon_name": "10%OFF",
            "status": "used",
            "used_at": "2026-09-19T10:00:00+09:00",
            "use_display": "barcode",
            "code": "APP300"
          }
        }
        """.data(using: .utf8)!

        let result = try CouponRedemptionResult.parse(json)
        XCTAssertEqual(result.code, "APP300")
        XCTAssertTrue(result.shouldShowBarcode)
    }

    func testParseProductsAndOrder() throws {
        let productsJSON = """
        {
          "products": [
            { "id": 1, "name": "コーヒー", "price": 400, "is_available": true, "description": "", "category": "drink", "image_url": "https://example.com/p.png" }
          ]
        }
        """.data(using: .utf8)!
        let products = try ProductListResponse.parse(productsJSON).products
        XCTAssertEqual(products[0].price, 400)
        XCTAssertEqual(products[0].imageURL, "https://example.com/p.png")

        let orderJSON = """
        {
          "message": "ok",
          "order": {
            "id": 9,
            "order_number": "A-9",
            "total_amount": 800,
            "status": "pending",
            "payment_method": "cash",
            "items": [
              { "product_name": "コーヒー", "quantity": 2, "price": 400 }
            ]
          }
        }
        """.data(using: .utf8)!
        let order = try OrderCreateResponse.parse(orderJSON).order
        XCTAssertEqual(order.orderNumber, "A-9")
        XCTAssertEqual(order.pickupNumberLabel, "A-9")
        XCTAssertEqual(order.totalAmount, 800)
        XCTAssertEqual(order.items.count, 1)
        XCTAssertTrue(order.isActive)

        let unpaidJSON = """
        {
          "order": {
            "id": 10,
            "order_number": null,
            "total_amount": 500,
            "status": "pending",
            "payment_method": "stripe",
            "items": []
          }
        }
        """.data(using: .utf8)!
        let unpaid = try OrderCreateResponse.parse(unpaidJSON).order
        XCTAssertFalse(unpaid.hasPickupNumber)
        XCTAssertEqual(unpaid.pickupNumberLabel, "決済確認中")

        for (stored, displayed) in [
            ("202604201", "201"),
            ("2026042020", "2020"),
            ("202604202022", "202022"),
        ] {
            let numbered = OrderSnapshot(
                id: 11, orderNumber: stored, totalAmount: 500, status: "preparing",
                paymentMethod: "cash", createdAt: "", items: []
            )
            XCTAssertEqual(numbered.pickupNumberLabel, displayed)
            XCTAssertEqual(numbered.orderNumber, stored)
        }

        let listJSON = """
        {
          "data": [
            {
              "id": 9,
              "order_number": "A-9",
              "total_amount": "800.00",
              "status": "preparing",
              "payment_method": "cash",
              "items": [
                { "product_name": "コーヒー", "quantity": 2, "price": "400" }
              ]
            }
          ]
        }
        """.data(using: .utf8)!
        let list = try OrderListResponse.parse(listJSON).orders
        XCTAssertEqual(list.count, 1)
        XCTAssertEqual(list[0].statusLabel, "準備中")
    }

    func testParsePaymentCheckout() throws {
        let json = """
        { "checkout_url": "https://checkout.stripe.com/c/pay/test", "session_id": "cs_123" }
        """.data(using: .utf8)!
        let checkout = try PaymentCheckoutSession.parse(json)
        XCTAssertEqual(checkout.checkoutURL, "https://checkout.stripe.com/c/pay/test")
        XCTAssertEqual(checkout.sessionId, "cs_123")
    }

    func testParseCurrentQueueStatus() throws {
        let json = """
        {
          "queue_entry": {
            "id": 5,
            "formatted_queue_number": "A-12",
            "status": "waiting",
            "party_size": 2,
            "waiting_count": 3,
            "estimated_wait_minutes": 15
          },
          "updated_at": "2026-01-01T00:00:00Z"
        }
        """.data(using: .utf8)!
        let snapshot = try CurrentQueueSnapshot.parse(json)
        XCTAssertEqual(snapshot.entry?.queueNumber, "A-12")
        XCTAssertEqual(snapshot.entry?.statusLabel, "待ち")
    }

    func testApiHealthURL() {
        let url = ReggitAPIClient.apiHealthURL(baseURL: "https://example.com/", projectKey: "default")
        XCTAssertEqual(url, "https://example.com/api/v1/app-config?project_key=default")
    }

    func testMediaURLResolvesRelativePath() {
        let absolute = MediaURL.resolve("/images/products/a.jpg", baseURL: "https://example.com")
        XCTAssertEqual(absolute?.absoluteString, "https://example.com/images/products/a.jpg")

        let already = MediaURL.resolve("https://cdn.example.com/x.png", baseURL: "https://example.com")
        XCTAssertEqual(already?.absoluteString, "https://cdn.example.com/x.png")

        XCTAssertNil(MediaURL.resolve(nil, baseURL: "https://example.com"))
        XCTAssertNil(MediaURL.resolve("", baseURL: "https://example.com"))
        XCTAssertNil(MediaURL.resolve("/images/a.jpg", baseURL: ""))
    }

    func testMediaURLRebasesLoopbackAssetForAndroidCompatibleAPIHost() {
        let resolved = MediaURL.resolve(
            "http://localhost/storage/navigation_headers/1/header.webp",
            baseURL: "http://10.0.2.2:8000"
        )
        XCTAssertEqual(resolved?.absoluteString, "http://10.0.2.2:8000/storage/navigation_headers/1/header.webp")
    }

    func testWalletPassDownloadURLBuilder() throws {
        let issue = "https://example.com/wallet-add?pass_token=abc123&project_key=demo"
        let url = try WalletPassURLBuilder.downloadURL(
            fromIssueURL: issue,
            baseURL: "https://api.example.com",
            projectKey: "demo"
        )
        XCTAssertEqual(
            url,
            "https://api.example.com/api/member-wallet/pass?token=abc123&project_key=demo"
        )
        XCTAssertThrowsError(
            try WalletPassURLBuilder.downloadURL(
                fromIssueURL: "https://example.com/wallet-add",
                baseURL: "https://api.example.com",
                projectKey: "demo"
            )
        )
    }

    func testFlattenProfilePayloadSkipsImageFields() {
        let payload = MemberProfileSnapshot.flattenProfilePayload([
            "name": "山田",
            "phone": "09011112222",
            "email": "a@example.com",
            "profile_image_url": "https://example.com/x.png",
            "has_profile_image": true,
            "sex": 1,
        ])
        XCTAssertEqual(payload["name"], "山田")
        XCTAssertEqual(payload["phone"], "09011112222")
        XCTAssertEqual(payload["email"], "a@example.com")
        XCTAssertEqual(payload["sex"], "1")
        XCTAssertNil(payload["profile_image_url"])
        XCTAssertNil(payload["has_profile_image"])
    }

    func testParseLotteryStateAndPlayResult() throws {
        let state = try LotteryStateSnapshot.parse(Data("""
        {
          "gacha_enabled": true,
          "scratch_enabled": true,
          "roulette_enabled": true,
          "entitlements": [
            {"id": 41, "reward_type": "gacha", "expires_at": null},
            {"id": 42, "reward_type": "scratch", "expires_at": "2026-09-30T12:00:00+09:00"}
          ],
          "prize_labels": {"gacha": ["ドリンク無料", "ハズレ"], "scratch": ["10% OFF"], "roulette": ["100pt", "ハズレ"]},
          "eligibility": {"grant_trigger": "min_amount", "min_amount": 3000, "entitlement_expires_hours": 24}
        }
        """.utf8))
        XCTAssertTrue(state.gachaEnabled)
        XCTAssertTrue(state.scratchEnabled)
        XCTAssertTrue(state.rouletteEnabled)
        XCTAssertEqual(state.pending(for: "gacha")?.id, 41)
        XCTAssertEqual(state.scratchPrizeLabels, ["10% OFF"])
        XCTAssertEqual(state.roulettePrizeLabels, ["100pt", "ハズレ"])
        XCTAssertEqual(state.minimumPurchaseAmount, 3_000)
        XCTAssertEqual(state.entitlementExpiresHours, 24)

        let result = try LotteryPlayResult.parse(Data("""
        {"success": true, "data": {
          "entitlement_id": 41,
          "reward_type": "gacha",
          "is_win": true,
          "coupon": {"id": 9, "name": "ドリンク無料", "description": "1杯無料", "image_url": null}
        }}
        """.utf8))
        XCTAssertTrue(result.isWin)
        XCTAssertEqual(result.coupon?.name, "ドリンク無料")
    }
}

@MainActor
final class NativeRootViewModelTests: XCTestCase {
    func testFilterNativeTabsMapsLegacyKeys() {
        let tabs = [
            AppTabItem(id: "membership", key: "membership", label: "会員", icon: "fa-user", sfSymbol: "person.fill"),
            AppTabItem(id: "mobileOrder", key: "mobileOrder", label: "注文", icon: "fa-utensils", sfSymbol: "cart.fill"),
            AppTabItem(id: "home", key: "home", label: "ホーム", icon: "fa-home", sfSymbol: "house.fill"),
            AppTabItem(id: "coupon", key: "coupon", label: "クーポン", icon: "fa-ticket", sfSymbol: "heart.fill"),
            AppTabItem(id: "designlab", key: "designlab", label: "あそび", icon: "fa-dice", sfSymbol: "dice.fill"),
        ]
        let filtered = NativeRootViewModel.filterNativeTabs(tabs)
        XCTAssertEqual(filtered.map(\.key), ["member", "products", "home", "coupons"])
    }

    func testDeviceIdentifierPersists() {
        let defaults = UserDefaults(suiteName: "reggit.tests.device")!
        defaults.removePersistentDomain(forName: "reggit.tests.device")
        let provider = DefaultNativeDeviceContextProvider(defaults: defaults)
        let first = provider.deviceIdentifier
        let second = provider.deviceIdentifier
        XCTAssertEqual(first, second)
        XCTAssertFalse(first.isEmpty)
    }
}
