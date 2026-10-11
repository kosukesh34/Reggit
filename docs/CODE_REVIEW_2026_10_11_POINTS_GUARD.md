# Critical review: persisted order points guard

Base: origin/main 813dcd737. Continues the profile and prior delivery reviews.

## P2: one successful order clears another order's pending protection

SmaregiPointsSyncService sends a delta through POST /point/add. Order A fails to send +100 and sets a member-wide cache flag. Order B sends +50 successfully and clears that flag although A remains unsynced. Inbound synchronization then reads remote 50 against local 150 and inserts an artificial debit of 100 before requesting A again. A later retry may recover the displayed balance, but the artificial ledger movement and intermediate balance/rank are incorrect. Cache eviction has the same weakness.

A regression using actual member/order persistence and mocked external calls reproduced local 50 instead of expected 150. The fix makes inbound reconciliation respect persisted eligible orders as well as the existing cache flag. The project-wide boolean query removes only StoreAccessScope, retaining explicit project and user filters. It excludes net-zero orders. The existing recovery search retains its store filtering and original eligibility; both queries share one implementation of the status/payment filter.

## Validation

Related 36 tests / 110 assertions pass with Pint on five changed PHP files. Tests cover interleaved failed/successful orders, no artificial reconciliation transaction, another store's pending work with an empty cache, cancelled and unpaid online orders, cash pending, confirmed Stripe, net-zero deltas and completed synchronization. Removing the new guard in temporary source makes the two new inbound tests fail (2 failed / 34 passed). Independent review found no behavioral blockers and identified a PHPDoc attachment error, which was corrected before final verification.

Full local CI, exact PR HEAD and logs are recorded in the PR by the required merge script. Actions remain disabled. No real API calls, production migration or browser E2E.

## Architecture and scope

Persisted order completion already exists and provides a more reliable interface than duplicating state in cache. Reusing the eligibility implementation improves locality without a new delivery framework or schema. The cache remains for non-order awards; this PR does not establish durable ownership for those.

This guard covers already-outstanding orders. Atomic reconciliation against a remote snapshot while new local work starts, external-success/local-ACK failure, and recovery of other stores' orders from a scoped fetch remain separate review work. A pending order in another store protects the shared balance, but its existing scoped fetch retry may need to run in that store. No claim of exactly-once external point addition is made.

Applied: i-have-adhd, Ponytail full, code-review-and-quality, Laravel query/architecture guidance, and architecture vocabulary from codebase-design. This is a focused repair, not a complete application review.
