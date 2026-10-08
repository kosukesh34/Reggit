# Order, reservation and notification consistency

## Deployment

Apply the normal application migration step (`php artisan migrate --force` in the app container). This adds `reservations.version` and the `order_notification_outbox` table. Keep the scheduler running; it now executes `orders:send-notifications` every minute. The existing Docker scheduler service already calls `schedule:run`.

Manual recovery: `php artisan orders:send-notifications --limit=100`.

Inspect pending deliveries in `order_notification_outbox` where `delivered_at IS NULL`. `attempts`, `available_at` and `last_error` identify failures. Delivery retries continue with a capped exponential delay, including after the request process or worker crashes. Do not delete pending rows to acknowledge an outage.

## Write boundaries

* `OrderStatusService` is the staff write boundary shared by admin and kitchen operations. It locks the order and invokes `Order::applyStaffStatus`. Pending Stripe orders can advance only through verified payment confirmation; staff cannot regress paid orders to pending or move completed orders backwards. Cash orders can still advance through staff confirmation. Completion benefits run transactionally and repeat completion is a no-op.
* `OrderRewardService` owns purchase points, visits, stamps, lottery entitlements and rank changes. Shared member benefit and visit writers live outside the admin layer. `OrderService` coordinates order creation, stock and cancellation rather than implementing every reward operation.
* Pending order/reservation DTOs carry the resolved store ID explicitly. Tenant/store global scopes remain defense in depth for queries. Outbox workers load their saved project/store and restore the caller's context after each delivery.
* Reservation editing, cancellation and admin state changes lock the reservation before validation. Aggregate persistence checks a monotonically increasing version so a stale aggregate cannot overwrite a newer operation. Admin detail edits increment the same version. Course snapshots are saved in the same transaction as member reservation creation/editing.
* Both the admin slot summary and the availability API count active reservations in the exact configured minute. Cancelled/completed reservations and adjacent slots are excluded.

## Notification delivery

Payment confirmation and staff state changes save notification work in the same transaction as the order. Customer native push, LINE and kitchen delivery have separate rows, so failure of one channel does not repeat successful channels. Immediate delivery occurs after the application transaction returns; the scheduler recovers failures and interrupted requests independently of the queue connection driver.

A unique order/event/channel key prevents repeated confirmation requests or completion clicks from creating duplicate work. Delivery leases prevent concurrent workers from normally sending the same row; expired leases can be reclaimed. External delivery is **at least once**: if a provider accepts a message and the process dies before recording success, a retry can duplicate that message. Exactly-once external delivery is not promised.

## Client origin boundary

The authenticated API client rejects cross-origin requests before adding credentials or calling `fetch`. Signed stamp URLs are also checked at parsing and submission. Relative and absolute same-origin API URLs remain supported. Links to external websites should use navigation rather than this credential-bearing API client.

## Merge and E2E policy

`AGENTS.md` requires local execution of browser/E2E checks and successful CI on the latest PR HEAD before merging. GitHub Actions runs PHP tests, frontend type checking/build and Pint; the `main` aggregate fails when a dependency fails or is cancelled. E2E jobs are not run in Actions.

Run browser checks against an isolated local application and test database, for example `E2E_BASE_URL=http://127.0.0.1:8019 E2E_BROWSER_CHANNEL=chrome npx playwright test --config=e2e/web/security-review.config.ts`. The real-store check requires a seeded test admin (override `E2E_ADMIN_LOGIN_ID` and `E2E_ADMIN_PASSWORD` as needed); it uses the native WebView entry point, while the public demo uses `/demo/liff`. Do not reuse another ongoing E2E run's database or port.

After user authorization to merge, use `scripts/merge-pr.sh <PR number>`. It refuses absent, failed or pending required checks and pins the merge to the validated HEAD. Configure GitHub branch protection requiring `main` when the repository plan supports it; local policy and the script cannot prevent a user with write access from bypassing them directly in GitHub.
