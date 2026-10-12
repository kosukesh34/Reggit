# Customer API named route targets

## Confirmed defect and smallest fix

The project-key customer v1 routes supply `{project_key}` before `{id}` or `{coupon}`. Eight shared controller methods accepted a positional integer target: Product show, Order show/cancelUnpaid, Coupon use, Announcement markRead, DeviceOnceCoupon claim, and MemberLottery play/notifyWin. String project keys raised TypeError; numeric project keys reached business operations with the project key as their target ID.

Use Request-only controller methods and named `route('id')` / `route('coupon')` values, following the existing queue controller pattern. All eleven project route variants, legacy v1 variants, and the shared device/lottery LIFF routes retain their URLs and business modules. No direct controller callers were found; the public HTTP contracts stay unchanged. Reservation update/cancel have only legacy routes and are not changed.

Add `whereNumber` to affected route definitions, including shared legacy routes, so malformed targets such as `501garbage` are rejected before dispatch instead of being cast into a valid record ID. Authorization, principal resolution, tenant/store scope and exception handling remain in their existing modules.

## Verification

The corrected real HTTP dispatcher regression uses actual API tokens, tenant middleware and a valid LINE identity. Against origin/main controller and route sources in a disposable archive: **22 failed / 16 passed, 92 assertions**. All string/numeric project variants failed; all eleven legacy v1 and five shared LIFF cases passed. An earlier invalid LINE fixture caused unrelated failures and is excluded from this evidence.

Final focused isolated MySQL/Docker validation: **123 tests / 625 assertions passed**. This includes 38 HTTP target cases, 38 malformed-target rejection cases, three real coupon redemptions proving only the requested coupon changes while a numeric-project-key decoy stays unused, plus existing product/order/coupon/announcement/device/lottery tests. Targeted Pint passes for eight changed PHP files; git diff check is clean. Logs are retained outside the repository at `/private/tmp/reggit-customer-route-final.log` and `/private/tmp/reggit-customer-route-baseline-corrected.log`.

The HTTP dispatch tests mock business modules to assert exact arguments while exercising the real router, authentication, tenant middleware and controllers. The real coupon tests additionally validate persisted state. Existing business regression tests cover the unchanged downstream behavior. This is not a full business-state or authorization audit. No external API connections or browser E2E were run. Fresh final-HEAD full local CI and the repository merge gate remain required before merging.

Applied: i-have-adhd, Ponytail full, code-review-and-quality, architecture exploration/codebase-design, Laravel routing guidance. No generic adapter, controller wrapper or speculative compatibility layer was added.
