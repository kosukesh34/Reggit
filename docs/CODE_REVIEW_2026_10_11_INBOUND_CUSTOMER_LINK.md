# Inbound customer-code linking review

## Confirmed defect

An inbound fallback starts a customer-code lookup while the local member has no Smaregi customer ID. While that GET is in flight, the real existing-member confirmation use case can assign a different customer ID and its points. The old lookup response previously saved its ID through a stale model, replacing the confirmed link and allowing subsequent point/profile synchronization against the wrong customer.

## Change

Keep the external lookup outside database locks. Before saving its result, lock the current User and Member in that order and verify the project, owner, empty customer ID, original member number, current contract, and absence of a pending registration attempt. Discard a response whose local identity changed. Publish the link-success log only after persistence succeeds.

## Evidence and limits

A regression invokes ConfirmExistingMemberLinkUseCase from the mocked lookup callback. Before the fix it fails because confirmed-customer becomes old-code-customer (1 failed, 5 assertions, 5.95 seconds). With the fix, the confirmed customer and 50 points are retained and the old synchronization returns false. Related registration, member API, and inbound synchronization tests passed: 46 tests, 196 assertions, 81.85 seconds. The test controls ordering in one process; it is not a separate-process concurrency or external API integration test.

Five-axis static review found no merge blockers. Fresh exact-HEAD full local CI remains required by scripts/merge-pr.sh before merging.
