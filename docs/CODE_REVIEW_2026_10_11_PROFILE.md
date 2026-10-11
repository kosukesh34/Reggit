# Critical review: member profile commit and response

Reviewed base: origin/main 813dcd737. This review continues prior recorded rounds; it does not certify an error-free codebase.

## P2: external profile writes precede an outer commit

MemberService.updateProfile commits its inner transaction then immediately calls Smaregi. Under an outer transaction, a subsequent rollback restores the local profile after the external profile has changed. A thrown PATCH exception also turns an already-committed local update into a failed request. The update response calls getMemberData, whose inbound synchronization can re-import the old profile while an outbound write is deferred or failed.

The focused fix defers external preparation and PATCH until the outer commit, isolates external exceptions, and temporarily restores the saved project for contract lookup and designated-store mapping. The response reads local profile values without inbound synchronization. Other getMemberData callers retain their default behavior. No new module or dependency is required.

## Verification

The original implementation fails all four initial regression cases: rollback, outer commit, PATCH exception, and stale profile import. The fix passes these and existing profile/multistore checks (30 tests, 113 assertions before adding the project-switch case). Final project-switch, mutation and complete local CI results are recorded in the PR. Tests use transaction-free fixtures with a separate disposable MySQL container, offline application execution, read-only source/vendor and resource limits. Independent architecture and patch review found no blockers.

## Architecture candidates and continued work

1. Strong: profile synchronization should own saved intent and recovery behind one interface. This PR fixes commit/response semantics; durable retry is separate.
2. Strong, needs reproduction: points synchronization shares one member-wide cache boolean across outstanding order deltas. One success can clear another failure's pending state, allowing inbound reconciliation against a stale remote balance.
3. Worth exploring: align existing delivery modules' commit, lease, ACK and context contracts through behavioral tests before considering a shared implementation. A generic delivery framework is not currently justified.

Architecture visualization: temporary architecture-review-2026-10-11-profile.html (outside version control). GLOSSARY.md consulted; no docs/adr directory found.

## Scope and limits

Applied: i-have-adhd, Ponytail full, code-review-and-quality, improve-codebase-architecture, codebase-design, Laravel queue/error/architecture guidance. Security guidance was consulted for source review. React skill was loaded but React implementation review has not yet been performed.

No real Smaregi calls, production changes, schema changes or browser E2E. A failed PATCH is still not durably retried; a later ordinary member fetch may import the old remote profile. That pre-existing recovery defect remains a required follow-up. The review continues beyond this PR.
