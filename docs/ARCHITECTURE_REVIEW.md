# Architecture review and decomposition plan

## Current findings

The application has several files whose size is a symptom of mixed responsibilities. The largest files are template catalogs, settings persistence, wallet integrations, and member workflows. Splitting them by arbitrary line ranges would only relocate complexity, so each extraction must preserve the existing public seam and add focused tests first.

## Decomposition order

1. `ShopSettingStore` into business, LIFF, feature flag, member, and wallet setting modules.
2. Done: certificate resolution moved to `AppleWallet\PassCertificates`, manifest signing and archive creation to `AppleWallet\PassPackager`, image assets to `AppleWallet\PassAssetWriter`, and pass.json to `AppleWallet\PassJsonWriter`. `WalletPassService` keeps availability checks and member lookup.
3. Done: credentials and OAuth2 token moved to `GoogleWallet\GoogleWalletCredentials`; LoyaltyClass access checks, creation and label updates moved to `GoogleWallet\GoogleWalletLoyaltyClassApi`, which hands failure reasons back through `takeError()` so `getLastError()` keeps its messages. Loyalty object mapping stays in `GoogleWalletPassService`.
4. Done: `MemberService` was split into `Member\MemberDataReader` (member card data), `Member\MemberProfileUpdater` (profile and image), `Member\MemberRegistrar` (registration), and `Member\MemberNumberAssigner` (member codes).
5. `LiffAppStudioTemplates` into one catalog plus one file per industry preset.
6. Done: `OrderService` keeps order placement only. Item validation moved to `OrderItemPreparer`, paid-order rewards to `PaidOrderRewardApplier`, and cancellation reversal to `OrderCancellationReverser`; the unused `applyCashOrderLotteryIfNeeded` pass-through was removed.
7. Done: `SmaregiInboundAwardApplier` delegates points to `SmaregiInboundPointAwarder` and stamps to `SmaregiInboundStampAwarder` (12 collaborators to 4; the unused `MemberAdminService` was dropped). `SmaregiCustomerPointsInboundSyncService` moved customer field mapping to `SmaregiInboundCustomerProfileApplier`.

## Rules for each extraction

- Keep the existing public API until all callers migrate.
- Extract one domain seam at a time; do not create pass-through wrappers.
- Add behavior tests before changing the implementation behind the seam.
- Keep external API calls behind an adapter so unit tests do not require network access.
- Remove the old private implementation only after reference search and the full test suite pass.

## Completed in this pass

- Client debug logging is disabled unless explicitly enabled.
- Enabling it now requires a configured bearer token.
- Input fields have bounded validation to prevent log flooding.
- Feature tests cover disabled, unauthorized, valid, and oversized requests.

