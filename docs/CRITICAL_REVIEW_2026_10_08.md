# Critical review: 2026-10-08

Scope: recent member, payment, reservation and notification changes on main at
72b630340. Reviewed callers, persistence, transactions and regression coverage;
this is not a claim that every file or external integration is defect-free.

## Confirmed and corrected

- P2: Profile User and Member writes could partially persist on failure. Local
  writes now share a transaction and row locks; custom fields merge against the
  locked current row, preventing stale instances from discarding other edits.
- P2: Custom select/checkbox validation split comma-containing choices. Laravel
  `Rule::in` now preserves each complete configured value.
- P1: Distinct users could allocate the same sequential member code. Registration
  now locks the project before snapshot reads and holds it through persistence.
- P2: Sequential overflow silently wrapped digits, random allocation returned an
  unchecked fallback, and soft-deleted members were omitted despite the database
  unique constraint retaining their codes. Allocation now fails explicitly on
  exhaustion and checks all reserved codes within the explicit project.
- P1: Registration and coupon notifications could execute before the transaction
  committed. Both jobs now use `afterCommit`; rollback discards the callbacks.

PR #286 contains the profile fixes. Registration fixes are a separate small PR.
Regression tests fail against the preceding implementation and pass with fixes.
Independent PHP processes verify that concurrent users receive 0001 and 0002.

## Architecture findings still requiring work

### P1: External registration is not atomic with local registration

`MemberService::registerMember` creates a Smaregi customer before persisting the
member and local registration benefits. Later rollback, process death, or an
ambiguous provider response can leave an external-only customer. A project lock
fixes local code collisions but does not roll back an external write.

The registration module should retain a durable registration attempt and reserved
code, reconcile external creation through the adapter, and resume local
finalization on retry. The existing `requireSmaregi` contract requires synchronous
external success for explicit registration; changing to unconditional asynchronous
registration would change behavior. Do not automatically delete or attach an
external customer based only on a matching code without verifying ownership.

### P2: Read paths perform writes and external synchronization

`MemberService::getMemberData` calls Smaregi synchronization and ledger repair.
Its interface hides network latency and mutations from callers. Separate explicit
synchronization from the read projection only after preserving the current points
freshness contract with behavior tests. This would improve locality and make read
failures independently testable.

### P2: Registration holds a project lock during external I/O

The correctness fix serializes registration per project, including Smaregi I/O.
It does not serialize different projects. A durable attempt/reservation module
would allow releasing the lock before network I/O without losing code ownership.
Sequential allocation still scans existing codes; measure this with realistic
member counts before replacing the existing compatibility scan.

`docs/ARCHITECTURE.md` explicitly preserves thin Admin UseCases; deleting them
would conflict with the established architecture. File length alone is not a
reason to introduce additional pass-through modules.

## Related existing work

PR #262 already implements announcement recovery and scheduled coupon retries.
It remains separate work and is not counted as merged or verified in this review.
The shared coupon `afterCommit` fix overlaps its observer change intentionally;
no second retry implementation was added here.

## Validation

- Dedicated local MySQL 8.4, PHP 8.4: 1,333 core tests, 5,779 assertions passed.
  Smaregi real integration group excluded; external API behavior was not tested.
- PHPStan and Pint passed.
- Frontend typecheck, ESLint and 54 unit tests passed.
- Three local Chrome component/security/accessibility tests passed. Full browser
  workflows, iOS/Android builds, production performance and external providers
  remain unverified.
- GitHub branch protection is unavailable on this private repository's plan.
  Latest-HEAD test/lint/main checks and scripts/merge-pr.sh remain the merge gate.

Applied skills: i-have-adhd, ponytail (full), code-review-and-quality,
improve-codebase-architecture, codebase-design, laravel-best-practices,
vercel-react-best-practices, security-audit (guidance), playwright-best-practices.
