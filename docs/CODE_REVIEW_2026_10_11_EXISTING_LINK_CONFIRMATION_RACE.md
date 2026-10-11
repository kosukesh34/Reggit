# Existing-member confirmation transaction boundary

## Confirmed defect

Confirmation held a database transaction while fetching the external customer. A separate DB connection could commit a contract change during that GET, but the confirmation still created a member linked from the old contract. Merely repeating a regular settings read within the existing MySQL repeatable-read transaction can retain its original snapshot.

A DatabaseTruncation regression uses the real contract resolver and another autocommit DB connection to change the contract during the mocked GET. Before the fix, the expected absence of a member fails (1 failed, 4 assertions, 6.19 s).

## Change

Validate the pending request and principal before external GET. Fetch the customer before starting the final transaction. Then lock the request and revalidate its token, project, user, target and saved contract, expiry, confirmation state, active project, principal, current contract, and target link availability. Preserve User→Member locking and the already-linked rejection before writing.

Keep the remote lookup out of the final transaction; the fresh transaction can observe the contract change committed during lookup. The same validation helper serves both boundaries without a new public interface.

## Verification and limits

Related confirmation and inbound tests: 39 passed, 181 assertions, 12.51 s. New tests also expire or change the target during lookup and verify no member creation and no confirmation mark; they assert zero transaction nesting during GET. Separate DB connections are real, but this is controlled interleaving in one process, not a separate-process HTTP race. External API responses are mocked.

Five-axis static review found no blockers. HTTP callers do not wrap this use case in an outer transaction; arbitrary caller-owned transaction snapshot guarantees are not claimed. Contract changes after the final check but before commit require a shared setting-writer locking contract and are not serialized by this patch. Immediate guest linking and response identity validation remain separate review candidates. Fresh exact-HEAD full local CI is mandatory before merge.
