# Apple Wallet update revisions

`updated_at` is retained for diagnostics. PassKit uses `v2:<revision>` tags because
second-resolution timestamps can lose successive updates within one second.

Each pass type has one durable counter. Writers lock that counter, increment it and
save the member's update marker in the same transaction. The lock lasts until the
outermost commit, so a later committed marker cannot carry an earlier revision.
Readers use one SELECT snapshot of registered markers and advance only to revisions
they observed. They do not lock the counter or publish its unseen high-water mark.
This also avoids reversing registration → counter lock order.

Legacy timestamp and invalid tags trigger one full resync, including existing markers
whose new revision defaults to zero. Valid unchanged tags return HTTP 204; later
updates return HTTP 200 with changed serials. Empty reads preserve the input revision.
See [Apple's update protocol](https://developer.apple.com/library/archive/documentation/UserExperience/Conceptual/PassKit_PG/Updating.html)
and [the serial-list response contract](https://developer.apple.com/documentation/walletpasses/get-the-list-of-updatable-passes?changes=l_1_3).

Deploy the additive migration before the new application and update all workers
together: old workers only write timestamps and cannot interpret v2 tags. Do not run
mixed old/new Wallet workers. The migration does not delete existing passes or markers.
Production recovery should use a forward fix retaining counters and revisions;
dropping/resetting them loses the cursor history already held by devices. Migration
`down()` removes that history and is suitable only for disposable/test databases.

Tests cover same-second writes, multiple members, later registrations, empty reads,
legacy rows, invalid tags, rollback, two-connection snapshots and the HTTP response.
