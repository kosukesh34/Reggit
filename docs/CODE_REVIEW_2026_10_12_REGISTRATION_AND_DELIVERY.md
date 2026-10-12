# Critical review: registration and delivery execution context

## Scope

Reviewed landing-registration issuance, stale request models, notification retry jobs,
tenant scopes, and the existing TenantContext execution interface. Prior review findings
and recent main changes were checked to avoid reporting already-fixed defects.
This is a focused continuation, not proof that every application path is defect-free.
Existing uncommitted landing UI changes are preserved outside these patches.

## Confirmed findings

1. **Medium — stale landing registration submission returns a server error.**
   SubmitLandingRegistrationUseCase checked submitted_at on the caller's snapshot before
   its transaction. Two snapshots of one pending registration both entered provisioning.
   The admin email unique constraint prevented a second commit, but the second operation
   failed with UniqueConstraintViolationException instead of returning the issued shop.
   Fix: reload and lock the registration in the transaction, decide whether to issue there,
   return the existing completion without a password on repeat, and send ready mail once.
   The original implementation fails the new stale-model regression with that constraint
   violation. The test exercises the public use-case interface; it does not simulate
   simultaneous MySQL connections.

2. **Medium — announcement retry can silently complete without reaching its audience.**
   SendAnnouncementNotificationJob fetched the announcement without tenant scopes but
   called its notifier in the caller's tenant context. Member and device audience queries
   combine the saved project with the current ProjectScope. With project A context and a
   project B announcement, they resolve zero targets; the job marks notification_pending
   false. Fix: use the existing TenantContext::runForProject interface around notification
   execution. Tests query scoped members during delivery, assert completion, and verify
   caller-context restoration and pending retention after a provider failure.
   The same saved-project execution seam also surrounds scheduled announcement audience
   resolution/coupon issuance and scheduled coupon delivery. Their real audience and
   issuance operations are tested under another project context.

## Architecture assessment

The strong improvement is to concentrate execution-context switching at the existing
TenantContext seam. It provides locality (restoration knowledge has one implementation)
and leverage (multiple job adapters can use the same guarantee). No generic retry module
is added because order, coupon and announcement delivery policies differ.

Registration keeps its small handle(input) interface and hides current-state lookup,
serialization and provisioning behind it. Adding another repository adapter would not
improve this change. Existing project-provisioning behavior is retained.

Worth exploring: reuse CurrentMemberPrincipal in remaining profile/device paths that
repeat generation and lock-order rules. Preserve each caller's original member-identity
invariant; no defect-free or equivalence claim is made for a wholesale replacement.

Withdrawal's external deletion and local transaction still lack a shared atomic commit.
A durable reconciliation workflow needs separate design; rearranging modules alone does
not solve it. File size alone is not a reason for broad rewrites.

## Verification and limits

Targeted isolated validation: registration 1 test / 8 assertions; delivery 4 tests /
16 assertions. Restoring the original implementations failed all five cases (the
registration email constraint and the delivery tenant-context errors). A dedicated
no-network MySQL fixture and read-only source/toolchain mounts were used; provider
traffic was mocked. A resource-constrained exploratory repeat timed out and is not
counted as successful validation.

Each PR must pass scripts/merge-pr.sh on its final committed HEAD with latest main included.
That gate runs local contract tests, Pint, PHPStan, core PHP tests, TypeScript, ESLint,
frontend unit tests and build in isolated environments. Final HEADs and logs are recorded
on the PRs. GitHub Actions remains disabled. Backend-only changes do not require browser
E2E. External providers, real deliveries and production state were not exercised.

Applied: i-have-adhd, ponytail full, code-review-and-quality, laravel-best-practices,
security-audit guidance, improve-codebase-architecture and codebase-design. Architecture
was independently explored by a read-only subagent; code patches were checked against
correctness, simplicity, tenant isolation, retry behavior and tests.
