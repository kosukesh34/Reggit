# Critical code and architecture review — 2026-10-12

## Scope and evidence

Reviewed member-facing write paths, their authorization and transaction boundaries,
benefit grants, withdrawal, existing-member linking and payment-session issuance.
The architecture exploration covered registration, membership and Apple Pass packaging.
A final static sweep also covered admin tenant/store authorization and representative
frontend state, API/CSRF and DOM output boundaries.
A second pass covered reservation-course authorization, legacy payment settlement and
order-notification retries after state changes. A third pass covered Apple/Google Wallet
HTTP authentication, update cursors, project branding and signing temporary files.
This is a focused critical review, not proof that every application path is defect-free.
Existing uncommitted landing-registration changes were preserved and excluded from patches.

## Confirmed findings

1. **High: authenticated snapshots remained usable after revocation.** Point and stamp
   grants, coupon issuance/redemption, lottery play, orders, queue admission, announcement
   reads, withdrawal and existing-member linking accepted an earlier User snapshot without
   checking its current authentication generation at the write boundary. Device transfer
   can revoke that snapshot while the request is still executing. Fix: use
   `CurrentMemberPrincipal` inside the caller's transaction; retain tenant scope, lock the
   current User then its current Member, compare authentication generations and reject
   deleted/revoked principals. Member-only operations also require a current Member.
2. **High: payment issuance discarded authentication generation.** Passing only user IDs
   made it impossible to reject a revoked snapshot, including saved-session reuse. Fix:
   pass the User snapshot to session issuance and validate it under Order → User → Member
   locks before reservation/reuse and after the gateway call, before publishing secrets.
   Stripe communication remains outside the transaction. A post-gateway rejection keeps
   the expired lease marker for an idempotent recovery; it never returns the new secret.
3. **Medium: device-coupon eligibility was checked before its transaction.** Device removal
   or coupon deactivation could occur after eligibility checks. Fix: recheck and lock the
   registered device and current coupon inside the grant transaction.
4. **Medium: signed stamp QR failures could strand a cache quota reservation.** A failure
   after saving the member benefit could roll back the DB but leave the quota consumed.
   Fix: release the reservation on pre-commit failures, preserve it after commit, and run
   rank synchronization after commit. A regression test injects an outcome-mapper failure
   and proves the same QR nonce can be retried successfully.

5. **High: branch admins could update project-common settings in multi-store projects.**
   The headquarters restriction omitted `/settings/*`, while branch ROLE_ADMIN users
   retain settings permissions. Profile definitions, ranks, linking, LINE/brand and
   payment settings, plus shared RFM criteria, could therefore bypass the documented headquarters boundary. Fix:
   classify settings mutations by route name, deny common settings to branch admins,
   use route URI templates for existing path classification so project keys such as
   `products` do not cause false denials, and retain store-owned Basic/Reservations/Queue,
   device enrollment and
   separately authorized staff routes. Headquarters and single-store behavior remain.

6. **High: shared reservation courses were exempted as store-owned settings.**
   Branch admins could create, reprice or deactivate courses used by every store.
   Fix: exempt only the exact operational reservation-settings update route; course
   mutations require headquarters in multi-store projects. Regression tests retain
   branch slot updates, headquarters course management and single-store behavior.
7. **Medium: legacy payment settlement did not validate currency.** Frontend confirmation
   and the global webhook could confirm a JPY order using a succeeded payment with the
   same numerical amount in another currency. Fix: require explicit JPY in both legacy
   and project-account paths. Tests reject USD and missing currency before rewards,
   order numbering, outbox recording or queue dispatch. No public-input exploit was demonstrated.
8. **Medium: notification retries could send obsolete order instructions.** A failed
   confirmation/ready notification retried after cancellation could tell the customer
   to collect an order or tell the kitchen to prepare it. Fix: check current order
   state before delivery, finish obsolete rows and suppress kitchen new-order notices
   once the order is ready. Failure → cancellation/completion → retry tests verify no
   provider call and no endless retry. This does not make a concurrent cancellation
   and an external provider request atomic.

9. **High: PassKit HTTP requests had no tenant binding but used tenant-scoped authentication reads.**
   Global HTTP middleware requires a project, while the four Apple Wallet endpoints
   receive no project header. Valid pass tokens were rejected by the fail-closed Member
   scope. Fix: explicitly bypass only ProjectScope for token-authorized lookups, retain
   SoftDeletes and match registration project IDs. After token verification, generate
   in the member's project context and restore the caller's context on exceptions.
   HTTP tests cover all four routes for two projects and reject another member's token
   and deleted members. Generic member API tenant requirements are unchanged.
10. **High: second-resolution Wallet tags lost same-second updates.** Timestamp overwrite
    plus a strict greater-than filter hid a later update in the same second; empty lists
    could also advance beyond unseen work. Fix: a per-pass-type durable revision counter
    and marker committed together, with a reader cursor limited to one observed snapshot.
    Legacy tags resync once, revision-zero rows survive migration, and unchanged passes
    return HTTP 204. See [Wallet rollout and invariants](WALLET_UPDATES.md).
11. **Medium: DER conversion leaked temporary files after a later signing failure.**
    Cleanup only followed successful conversion of certificate, key and WWDR. Fix:
    finally cleanup from the first conversion, private tempnam files outside certificate
    directories and explicit export failures. Tests generate real temporary certificates,
    sign/package successfully and inject an invalid key without leaving conversions.
12. **Medium: Google Wallet brand classes were shared across projects.** A project reused
    the first project's class name and logo. New issuance and sync now share project-specific
    class/object IDs. Legacy objects receive point/profile PATCHes without changing their
    IDs/classes; 404 means absent, while HTTP/connection failures remain failures even if
    the new object is updated. Existing passes need re-adding for corrected brand artwork;
    no production reclassification was attempted or assumed safe.

## Architecture decisions

**Implemented:** encapsulate current principal lookup, tenant scope, generation validation
and User → Member lock ordering in one small module. Callers own their transaction and
business policy; the module does not send external requests, create members or commit.
Existing Order → User → Member and LinkRequest → User → Member sequences stay intact.
Avoid changing all use cases into a single generic service or replacing Laravel's container.

**Implemented in the second pass:** retain the existing module interfaces and correct
three invariants at their current seams: route-based common/store authorization,
settlement currency validation and actionable outbox delivery. This improves locality
without adding new adapters or moving complexity between callers. Both settlement
entry points require JPY; outbox retry policy lives in the delivery module.

**Worth exploring, not a confirmed defect:** membership registration spans validation,
remote-customer acquisition and local persistence. A clearer acquisition/persistence seam
could improve independent testing. Retain existing behavior and interfaces until a concrete
change needs that seam; do not refactor merely to reduce file count.

**Third-pass correction:** Apple Pass packaging did reveal a conversion cleanup failure.
Fix the resource lifetime in the existing signing implementation; do not add a broad
package-builder abstraction solely to move filesystem code between files.

Large announcement delivery UI and ShopSettingStore implementations remain optional
seam candidates. File size alone does not demonstrate a defect. The inspected frontend
paths yielded no additional confirmed bugs.

## Limits and follow-up risks

Withdrawal still coordinates an external deletion and a local DB transaction without a
shared atomic commit. The current-principal lock prevents an invalid caller's deletion,
but cannot undo a successful provider deletion if the later local commit fails. A durable
withdrawal workflow/reconciliation strategy is a separate architecture improvement.

Authoritative payment confirmation/webhook paths remain independent of a browser principal.
A settled external payment must still reconcile if the initiating browser session expires.
External integrations and production behavior were not exercised. Browser E2E is unnecessary
for these backend-only changes; the local contract and regression tests cover their outputs.
Every PR must independently pass the repository's complete local merge gate on its final
committed HEAD. A timeout or failed run is not accepted as validation.

## Skills actually applied in the second pass

- `i-have-adhd` and `ponytail` full: concise progress, smallest complete fixes.
- `code-review-and-quality`: correctness, readability, architecture, authorization and
  performance review of all three diffs, including an independent read-only reviewer.
- `improve-codebase-architecture` and `codebase-design`: identify concrete friction,
  retain existing interfaces and adapters, document before/after structure in a temp HTML report.
- `laravel-best-practices`: existing route authorization, transaction and Eloquent patterns;
  targeted regression tests, formatting and static analysis.
- `vercel-react-best-practices`: limited static review of membership-hook cleanup and
  realtime subscription lifecycle. No confirmed frontend issue or React change in this pass.

Targeted validation: reservation authorization 31 tests / 151 assertions; payment
settlement 49 / 275; outbox regressions 13 / 65. In isolated copies, restoring the old
implementation failed all four new currency cases and all five new obsolete-notification
cases. Full merge-gate results and final HEADs are recorded on each PR.

## Wallet review validation and design

Applied `i-have-adhd`, `ponytail` full, `code-review-and-quality`,
`improve-codebase-architecture`, `codebase-design` and `laravel-best-practices`.
An independent read-only reviewer checked the lock order, tenant authentication,
legacy compatibility and cleanup. No frontend/React code was changed in this pass.

Targeted results: Wallet integration 20 tests / 72 assertions; final cursor/snapshot/HTTP
response regressions 9 / 28; signing 2 / 8; PassKit HTTP/context regressions 3 / 34.
Google branding had 17 related tests / 46 assertions, then the new connection-failure
case and payload assertions were independently rerun. Final full-gate counts and HEADs
are recorded on each PR. Old implementations reproduce same-second loss, conversion
leaks and shared Google IDs in isolated copies.

Revision allocation is internal to the existing Wallet update module. Callers retain
its interface; the counter adapter makes commit order and cursor locality explicit.
Google ID generation is shared by issuance and sync instead of duplicated literals.
Legacy/new object handling is an actual compatibility seam, not a hypothetical adapter.
See [Apple update tags](https://developer.apple.com/library/archive/documentation/UserExperience/Conceptual/PassKit_PG/Updating.html)
and [Google loyalty object fields](https://developers.google.com/wallet/reference/rest/v1/loyaltyobject).

New Wallet schema must precede coordinated application/worker rollout. Counter history
must be retained in production. API fakes and local certificates verify our code, not
Apple/Google production compatibility. No external API calls or browser E2E were run.

## 13. Wallet authentication accepted predictable tokens without keys (P2)

With both the Wallet secret and application key absent, token creation used an empty
secret or a known fallback constant. Refuse issuance and reject verification in this
misconfigured state. Configured custom/application keys preserve existing tokens.
This does not establish an authentication bypass in environments with a valid key.

Five isolated regression tests / 16 assertions pass; the old implementation fails
three missing-key cases while its two configured-key compatibility cases pass.
`security-audit` guidance mode was applied to the trust boundary and secret handling;
no production credentials or external integrations were used. PassKit plus layer
dependency regression checks also pass: 21 tests / 434 assertions.
