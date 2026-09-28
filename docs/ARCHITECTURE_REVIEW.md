# Architecture and maintainability review

Reviewed: 2026-09-27

## Executive summary

Reggit already has a meaningful application/domain/infrastructure split and strong feature-test coverage. The main maintainability risk is not missing layers; it is uneven adoption of those layers. Newer order, member, queue, reservation, coupon, and settings paths use domain contracts, while many application use cases still depend directly on Eloquent models and concrete `Services`. On the frontend, a small number of manager and entry-point files hold a disproportionate amount of state, DOM coordination, API access, and presentation behavior.

The recommended direction is incremental modularization by business capability. A repository-wide rewrite would discard useful tests and introduce unnecessary regression risk.

The end-user LIFF runtime is currently a minimal rebuild placeholder. Its API, authentication, business logic, and saved designer settings remain intact, while the former React UI, external browser SDKs, and legacy CSS are no longer loaded by `/liff`. Legacy UI sources remain temporarily because the admin preview imports parts of them and they are useful migration references.

## What is working well

- `app/Domain` has no dependency on Laravel HTTP classes, Eloquent models, or infrastructure implementations.
- Repository contracts for core transactional domains live inward of Eloquent implementations.
- Controllers are generally thin; read and mutation controllers are already separated in several admin areas.
- Tenant isolation and security boundaries have explicit feature tests.
- The test suite covers the main business flows, including ordering, points, stamps, coupons, reservations, queues, settings, and external-integration boundaries.
- TypeScript checking is available through `npm run typecheck`; extracted member and navigation modules additionally pass the full strict profile through `npm run typecheck:strict`.

## Priority findings

### Resolved: Settings had a wide interface

The former `ShopSettingRepository` exposed more than 100 methods. It has been removed, and consumers now depend on capability-focused contracts. The shared Eloquent adapter and `ShopSettingStore` remain intentionally consolidated behind those interfaces; persistence can be split later without changing consumers.

Split contracts by consumer capability, for example:

- `BrandingSettings`
- `FeatureFlagSettings`
- `LineConnectionSettings`
- `MemberCardSettings`
- `OrderSettings`
- `QueueSettings`
- `ReservationSettings`
- `WalletSettings`
- `FeatureSettings`
- `ProfileSettings`
- `RankSettings`
- `LiffDesignerSettings`
- `LineAdminSettings`
- `KitchenAccessSettings`
- `MemberSettings`
- `OperationalSettings`
- `OrderSettings`
- `ProjectSettings`
- `QueueAdminSettings`
- `SmaregiSettings`

Keep the current implementation as an adapter while migrating consumers, then split persistence only after the interfaces are stable.

### P1: Frontend orchestration is concentrated in god objects

`MemberManager.ts` is over 3,400 lines, `app.ts` is over 2,000 lines, and several feature managers exceed 600 lines. These classes mix API calls, state, DOM lookup, event wiring, navigation, formatting, and rendering. Changes therefore have a large blast radius and isolated tests are difficult.

Move one vertical slice at a time to the existing React feature structure:

1. Extract typed API clients and response DTOs.
2. Extract stateful feature hooks/controllers.
3. Keep DOM/bootstrap code only in the entry point.
4. Move pure formatting and policy decisions to functions with unit tests.
5. Delete manager methods only after the migrated screen is covered by an end-to-end test.

### P1: Application-layer dependency rules are inconsistent

Some use cases depend on domain repository contracts, while many depend directly on `App\Models` and concrete `App\Services`. Direct model use is pragmatic for simple queries, but concrete outbound dependencies (LINE, Smaregi, mail, wallet, push, payment) reduce substitutability and make application tests integration-heavy.

Introduce ports only at external or volatile boundaries. Do not create a repository for every Eloquent query. Prioritize notification delivery, external APIs, storage, payment, and time/identity providers.

### Resolved: Repository conventions are documented

The codebase contains both `app/Domain/*/Repositories` with `app/Infrastructure/Persistence/Eloquent/*` and `app/Repositories/Contracts` with `app/Repositories/Eloquent`. Both work, but contributors must guess where a new abstraction belongs.

The placement rule is recorded in `ADR-001-REPOSITORY-CONVENTIONS.md`: business aggregates use the domain/infrastructure convention, while the older application repository locations are reserved for identity, tenant-resolution, and non-domain read models.

### Resolved: Presentation fallbacks do not hide programming errors

View composition intentionally falls back on defaults for runtime failures. The extracted composers report those failures and catch `RuntimeException`, allowing programming errors such as type violations to surface.

### Resolved: Static quality gates are available

The repository now has PHP formatting, Larastan, dependency-boundary tests, TypeScript checking, ESLint with React Hooks rules, and focused frontend unit tests. The initial Larastan level is intentionally conservative and can be raised incrementally.

- `composer analyse`
- `npm run lint`
- `npm run typecheck:strict`
- `npm run test:unit:frontend`
- `tests/Unit/Architecture/LayerDependencyTest.php`

## Refactoring completed in this review

The service provider previously contained DI bindings, observer registration, admin layout queries, frontend settings assembly, fallback presentation data, and LIFF preview payload construction. Presentation responsibilities are now isolated in:

- `AdminLayoutComposer`
- `FrontendShellComposer`
- `DesignerPreviewMemberPayloadBuilder`

`AppServiceProvider` now registers those collaborators and remains focused on application bootstrapping. Constructor injection replaces service-location inside the presentation logic, making dependencies explicit and the preview builder independently testable.

The presentation layer no longer depends on the 100+ method `ShopSettingRepository`. It uses focused read contracts instead:

- `AdminAppearanceSettings`
- `FrontendShellSettings`
- `MemberCardPreviewSettings`
- `AppConfigSettings`
- `HomeSettingsRepository`
- `QueueSettings`
- `ReservationSettings`
- `LineConnectionSettings`
- `CouponNotificationSettings`
- `OrderNotificationSettings`
- `StampSettings`
- `WalletSettings`

The Eloquent adapter implements these contracts during the incremental migration. The app-config API, shared home-image reader, queue and reservation flows, LINE identity/webhook handling, and customer notification services have moved to focused contracts. Architecture tests prevent migrated consumers from returning to the broad dependency.

Member rank calculation and synchronization, rank-driven delivery forms, member profile validation, member-card reads, coupon presentation, and queue LINE-link flows now also use focused settings contracts. This removes the broad dependency from cross-cutting member workflows without changing the shared persistence adapter.

All application consumers have migrated away from `ShopSettingRepository`, and the broad contract has been deleted. Architecture tests prevent its reintroduction. Future application code must select a capability-focused contract.

Order creation now has one shared request and orchestration path for the legacy and v1 HTTP endpoints. Domain-safe order failures use `OrderOperationException`; unexpected exception details are no longer returned to clients.

Stripe confirmation now rolls back payment persistence if reward application fails. Domain events and external synchronization are dispatched after commit as best-effort follow-up work, so a delivery outage cannot turn a committed order into a misleading HTTP failure.

Post-commit event failure handling is centralized in `PostCommitDomainEventDispatcher` instead of being duplicated across order and payment flows. `OrderService` also uses framework-managed transaction closures, ensuring every `Throwable` rolls back consistently.

`GoogleWalletPassService` now declares member-data and card-appearance collaborators in its constructor. The previous service-locator lookups hid required dependencies and made isolated substitution harder; an architecture test prevents those lookups from returning.

The test harness clears shared cache state per test, and integration-dependent FCM and Smaregi webhook configuration is explicit in the affected suites. This prevents order-dependent rate-limit and environment failures.

Member profile and withdrawal endpoints now distinguish validation or external-link failures from unexpected failures. Provider diagnostics and local log paths remain server-side, while local withdrawal mutations run in one database transaction.

The first frontend extractions move Wallet/external-URL policy, member API payload normalization, and profile-form payload construction out of `MemberManager.ts`. The manager retains compatibility delegates while URL validation, HTTPS normalization, LIFF return URL construction, Google Wallet URL handling, error-message selection, supported member payload shapes, wallet-summary equality, split-name handling, choice fields, and empty profile values are independently covered by Node unit tests.

Guest, authentication-required, error, and loading-state member-card templates are now pure renderers outside `MemberManager.ts`. Dynamic copy and CSS class values are escaped at the rendering boundary and covered by focused injection-resistance tests.

Postal-code normalization, validation, and API address-shape conversion are also isolated from profile-edit DOM orchestration. Both snake-case and camel-case response shapes are covered by frontend unit tests.

Point-history normalization and rendering are isolated from API and DOM orchestration. Numeric strings, invalid values, empty results, dates, and untrusted descriptions are handled at one tested boundary.

Profile registration/edit form rendering is now isolated from member state and DOM orchestration. Field definitions, initial values, native notification copy, address grouping, forced-registration actions, and output escaping are covered by focused unit tests.

React screen modules are loaded at route boundaries with an accessible loading fallback. Home, membership, profile, feature, design-lab, and secondary screens no longer have to share one eagerly evaluated application chunk. The remaining mutually dependent compatibility managers are emitted as one stable cache group instead of inflating the entry chunk.

Application navigation policy is isolated from the `ReggitApp` orchestrator. URL and LIFF-state tabs, deep-link ownership, order mode, and designer-preview parameters are normalized by pure functions with hostile and malformed input coverage.

Legacy application bootstrap is also isolated from the orchestrator. LIFF readiness, persisted identity restoration, loading-state cleanup, and bounded startup fallbacks now live behind an injected application factory.

Rank-history rendering is also isolated as a pure formatter. API-provided labels and dates are escaped at that boundary and covered by focused unit tests. View composers now catch runtime exceptions rather than all PHP throwables, so programming errors such as type violations are no longer silently replaced with fallback presentation data.

Dead-code cleanup removed unreferenced compatibility aliases from the legacy frontend and two standalone debug HTML pages from the public web root. Feature sources that are still imported by the admin designer preview remain intentionally retained; runtime entry-point removal alone is not sufficient evidence that those modules are dead.

## Recommended sequence

1. Protect dependency direction with architecture tests and narrow the remaining graceful-fallback exception handling.
2. Split the settings interface by capability without changing storage.
3. Migrate `MemberManager.ts` one screen/use case at a time.
4. Reduce `frontend/app.ts` to composition and bootstrap.
5. Add outbound ports around LINE, Smaregi, push, wallet, and mail as those areas are touched.
6. Consolidate repository conventions after consumers have migrated.

Each step should leave the complete core PHP suite, TypeScript check, and affected Playwright flows green. Avoid a big-bang clean-architecture rewrite.
