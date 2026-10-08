# Architecture review and decomposition plan

## Current findings

The application has several files whose size is a symptom of mixed responsibilities. The largest files are template catalogs, settings persistence, wallet integrations, and member workflows. Splitting them by arbitrary line ranges would only relocate complexity, so each extraction must preserve the existing public seam and add focused tests first.

## Decomposition order

1. `ShopSettingStore` into business, LIFF, feature flag, member, and wallet setting modules.
2. `WalletPassService` into asset building, certificate resolution, manifest signing, and archive creation modules.
3. `GoogleWalletPassService` into credentials/JWT, Google API access, and loyalty object mapping modules.
4. `MemberService` into profile, registration, rank, image, and Smaregi synchronization modules.
5. `LiffAppStudioTemplates` and `LiffSaaSTemplateKit` into one catalog plus one file per industry preset.

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

