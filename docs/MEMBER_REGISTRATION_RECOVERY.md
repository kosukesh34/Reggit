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

The project lock still spans external I/O during finalization, preserving existing
serialization. Removing that lock needs separate concurrency tests. Member reads
still have synchronization side effects. This change repairs remote/local failure
recovery without changing those existing freshness or locking contracts.

Validation uses a dedicated local MySQL server and mocked Smaregi transport. A
second PDO connection proves the attempt is committed before external creation.
Tests cover rollback/retry without duplicate POST, ambiguous responses, foreign
note2 conflicts, lookup connection failure, contract changes and read-only recovery.
Real Smaregi transport and production deployment are not exercised.
