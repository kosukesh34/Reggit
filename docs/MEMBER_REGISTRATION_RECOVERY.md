# Member registration recovery

Registration reserves the member code and a random attempt key in a committed
local transaction before creating a Smaregi customer. Explicit registration still
requires external success. Local rollback retains the reservation for retry.

New customers receive `reggit-registration:<UUID>` in `note2`, with the owner's
approval. Recovery looks up the reserved code in the saved contract and requires
both that exact code and the exact marker. Existing note2 values are never patched,
appended to, or overwritten. A conflicting customer stops registration; matching a
code alone never proves ownership. Lookup failure also stops external creation.

A retry reuses a matching external customer and finalizes the local member and
benefits atomically. Optional guest registration may succeed locally after an
ambiguous POST response. Its member read performs a throttled lookup to attach an
already-created, marked customer; it never creates a customer on a read. Attempts
retain only identifiers, not registration profile data. Pending numbers remain
reserved, including when the configured counter changes.

Deploy the migration before the application changes. Do not roll back/drop the
attempt table while pending attempts exist: doing so loses recovery identity.
Contract changes require administrator reconciliation and block explicit retries.
Legacy external-only customers without a stored attempt cannot be automatically
recovered. Marker edits also require manual reconciliation. Remote fields from the
original creation are not silently rewritten during recovery.

The project lock protects code reservation and commits before external I/O.
Remote lookup/creation runs outside the finalization transaction. Finalization
locks the user to prevent duplicate local members. Concurrent same-user POSTs
use the same reserved customer code, whose provider uniqueness prevents duplicate
customers; a losing request may require retry after the winner completes. Different
users can contact the provider concurrently. Registrations without
a reservation retain the project lock through their local allocation and commit.
Pending codes remain reserved until the same transaction persists the member and
deletes the attempt, so concurrent allocators never observe a reuse window. A
second PDO connection acquires the project lock with NOWAIT inside the remote POST
callback, verifying that external I/O does not hold that lock. Member reads still
have synchronization side effects; the existing freshness contract is preserved.

Validation uses a dedicated local MySQL server and mocked Smaregi transport. A
second PDO connection proves the attempt is committed before external creation.
Tests cover rollback/retry without duplicate POST, ambiguous responses, foreign
note2 conflicts, lookup connection failure, contract changes and read-only recovery.
Real Smaregi transport and production deployment are not exercised.
