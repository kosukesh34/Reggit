# Critical review: 2026-10-09

Scope: member state, rank transitions, scheduled notifications and migration rollback, plus source inspection of authentication and client credential handling. This continues the 2026-10-08 review; it does not certify every integration or every file.

## Confirmed defects

- P2, PR #294: an older visit through a stale Member instance could regress the latest visit timestamp. The owning recorder now uses a conditional database update, retaining tenant/store scopes, then refreshes the caller.
- P2, PR #294: admin custom profile select/radio/checkbox rules split comma-containing configured values. The customer counterpart was fixed upstream in #286; the admin rules now also preserve complete values through Rule::in.
- P1, PR #295: rank/history could commit before rank-up coupons or scheduled delivery records, so a later local failure permanently suppressed retry. These local writes now share the rank transaction.
- P1, PR #295: nesting rank work in payment transactions could send scheduled notifications or Smaregi rank assignment before the outer commit. Both wait for the outer commit. Announcement failure retains the existing retry markers and does not stop later deliveries.
- P2, PR #296: reverting the Smaregi product option migration could drop the only supporting project foreign-key index. Restore the single-column project index before removing the composite unique index. The forward migration is unchanged.

## Architecture assessment

The main friction is hidden ordering constraints across writes and network calls. Fixing an invariant in its owning module provides more locality than splitting large files by line count. The rank interface now owns its local transaction; callers no longer need to coordinate rank/history/benefit persistence. Existing announcement retry state is reused instead of adding a parallel notification framework.

Existing thin Admin UseCases are an intentional convention in docs/ARCHITECTURE.md. File length or pass-through calls alone do not justify replacing that convention. Wallet signing, settings and UI catalogs merit focused decomposition only when a specific change and behavior test establish leverage.

Smaregi rank assignment is explicitly best effort and currently has no durable retry state. A provider failure can leave its rank stale until another synchronization. A durable implementation would need a member generation, retry time and lease, and must send the current rank rather than replay an old history. This remains an architectural limitation, not a guarantee supplied by the local consistency fix.

MemberService::getMemberData still performs registration recovery, Smaregi refresh and ledger reconciliation. Moving these operations requires preserving the current freshness contract; simply removing writes from the read method would change user-visible behavior.

## Verification

- PR #294: latest-HEAD test/lint/main succeeded and scripts/merge-pr.sh merged it. Isolated local full suite: 1,363 tests and 5,954 assertions passed; final targeted checks: 5 tests and 15 assertions.
- PR #295: 25 targeted tests and 123 assertions passed after updating the existing notification-failure expectation. Tests cover local rollback/retry, outer transaction rollback/commit, and the first notification failing without stopping the second. Full-suite and latest-HEAD CI results are recorded in the PR before merge.
- PR #296: focused rollback test passed (8 assertions). Test forces the missing single-column index and checks foreign-key preservation and down/up/down. Final results are recorded in its PR before merge.
- Test containers use scratch source, read-only installed dependencies, an isolated network namespace, a dedicated test database and resource limits. No existing development database is used.
- Browser E2E and real external provider calls were not run for these backend fixes. No browser/E2E jobs were added to Actions.

Applied: i-have-adhd, ponytail full, code-review-and-quality, improve-codebase-architecture, codebase-design, laravel-best-practices, vercel-react-best-practices for client source inspection, security-audit guidance. Playwright instructions were inspected for verification planning; browser checks are not claimed as executed.
