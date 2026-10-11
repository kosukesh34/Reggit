# Critical code and architecture review — 2026-10-12

## Scope and evidence

Reviewed member-facing write paths, their authorization and transaction boundaries,
benefit grants, withdrawal, existing-member linking and payment-session issuance.
The architecture exploration covered registration, membership and Apple Pass packaging.
A final static sweep also covered admin tenant/store authorization and representative
frontend state, API/CSRF and DOM output boundaries.
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
   payment settings could therefore bypass the documented headquarters boundary. Fix:
   classify settings mutations by route name, deny common settings to branch admins,
   and explicitly retain store-owned Basic/Reservations/Queue, device enrollment and
   separately authorized staff routes. Headquarters and single-store behavior remain.

## Architecture decisions

**Implemented:** encapsulate current principal lookup, tenant scope, generation validation
and User → Member lock ordering in one small module. Callers own their transaction and
business policy; the module does not send external requests, create members or commit.
Existing Order → User → Member and LinkRequest → User → Member sequences stay intact.
Avoid changing all use cases into a single generic service or replacing Laravel's container.

**Worth exploring, not a confirmed defect:** membership registration spans validation,
remote-customer acquisition and local persistence. A clearer acquisition/persistence seam
could improve independent testing. Retain existing behavior and interfaces until a concrete
change needs that seam; do not refactor merely to reduce file count.

**Worth exploring, not a confirmed defect:** Apple Pass packaging has filesystem, signing
and archive concerns that could be hidden behind a package-builder boundary. No packaging
failure was demonstrated during this review, so no speculative rewrite was made.

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
