# Existing-member confirmation review

## Confirmed problem

An unlinked user can create multiple pending link requests. Confirming the newer request then the older request overwrote the first confirmed customer ID and points. Request creation rejects already-linked users, but confirmation did not repeat that check.

## Change

After retrieving the remote customer, lock the current project User and then its current Member. Reject a nonempty current customer ID using the existing already-linked error. Set the locked member relation explicitly before passing the user to the existing writer. The user lock also serializes same-user confirmations when no Member exists yet. Guest behavior is preserved.

## Evidence

A regression uses the real request and confirmation use cases. The baseline fails because confirmed ID 9002 becomes 9001 (1 failed, 4 assertions, 6.34 seconds). After the fix, related existing-link and inbound-sync tests pass: 33 tests, 149 assertions, 6.62 seconds. The regression verifies ID and point retention, rejection, and the older request remaining unconfirmed. This is a sequential regression, not a separate-process concurrency test. External API and mail are mocked.

Five-axis static review found no blockers. The existing remote GET remains within the confirmation transaction; changing that boundary and contract identity validation require separate evidence. Fresh full local CI for the exact PR HEAD is mandatory before merge.
