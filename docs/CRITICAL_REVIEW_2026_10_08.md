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

PR #286 contains the profile fixes; PR #287 contains the registration fixes. Both are merged.
Regression tests fail against the preceding implementation and pass with fixes.
Independent PHP processes verify that concurrent users receive 0001 and 0002.

## Architecture findings

### P1 corrected: External registration recovery identity

A durable attempt now reserves the code and contract before external creation.
New external customers receive a random attempt marker in note2 (owner approved).
Retry requires matching contract, code and marker, and never overwrites existing
note2. Local rollback retains the identity. See MEMBER_REGISTRATION_RECOVERY.md
for limitations, deployment order and failure behavior.

### P2: Read paths perform writes and external synchronization

`MemberService::getMemberData` calls Smaregi synchronization and ledger repair.
Its interface hides network latency and mutations from callers. Separate explicit
synchronization from the read projection only after preserving the current points
freshness contract with behavior tests. This would improve locality and make read
failures independently testable.

### P2 corrected: Registration held a project lock during external I/O

The durable reservation transaction commits before network I/O. Finalization of a
reserved code locks the user. External lookup/creation happens before that
transaction, allowing different users to contact Smaregi without holding local
transaction locks. An independent PDO connection verifies this
with `FOR UPDATE NOWAIT` inside the external creation callback; the check fails
against the old implementation. Local-only allocation retains the project lock.
Sequential allocation still scans existing codes; measure realistic member counts
before replacing the compatibility scan.

`docs/ARCHITECTURE.md` explicitly preserves thin Admin UseCases; deleting them
would conflict with the established architecture. File length alone is not a
reason to introduce additional pass-through modules.

## Related existing work

PR #262 is merged after latest-HEAD CI and local regression checks. It recovers
announcement delivery and coupon batches, and fences expired announcement workers
so they cannot overwrite a newer retry's state. At-least-once delivery may still
duplicate successful recipients after partial provider failures; exactly-once
external delivery is not guaranteed.

## Validation

- Dedicated local MySQL 8.4, PHP 8.4: 1,352 core tests, 5,880 assertions passed.
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
