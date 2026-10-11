# Existing-member pending link contract review

## Confirmed defect

A pending link request held the project, customer ID, and confirmation email but no Smaregi contract. After the project switched contracts, confirmation fetched the same customer ID from the new contract. A confirmation sent for the old contract could therefore link a different customer. A regression using the real contract resolver with a DB settings change reproduced the unwanted Member creation.

## Change

Store the contract actually used by the customer lookup with every email confirmation request. Return it alongside the matched customer from the private lookup method; do not resolve it again after GET. Confirmation rejects unknown or changed contracts and retrieves the customer using the saved contract.

Add a nullable contract_id column. Existing pending requests have unknown historical contracts and require a new request; do not backfill them from the current configuration. The current confirmation window is 30 minutes. Valid fixtures now identify their contract explicitly, while a dedicated legacy-null regression verifies rejection.

## Validation and scope

Baseline: 2 failed, 4 assertions, 6.67 seconds. After the fix: 36 related existing-link and inbound-sync tests passed, 164 assertions, 7.54 seconds. A data-driven case also changes the contract during the initial search and verifies that the request retains the searched contract. External API responses and mail are mocked; contract resolution and DB settings are real within an isolated test DB.

Five-axis static review found no blockers. Fresh exact-HEAD full local CI is required before merge. Changing contracts during the confirmation GET and immediate guest links without email are separate follow-up candidates; this patch does not claim to cover those paths. Actual external API integration, browser E2E, and separate-process concurrency are not verified here.
