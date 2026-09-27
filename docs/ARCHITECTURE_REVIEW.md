# Architecture and maintainability review

Reviewed: 2026-09-27

## Executive summary

Reggit already has a meaningful application/domain/infrastructure split and strong feature-test coverage. The main maintainability risk is not missing layers; it is uneven adoption of those layers. Newer order, member, queue, reservation, coupon, and settings paths use domain contracts, while many application use cases still depend directly on Eloquent models and concrete `Services`. On the frontend, a small number of manager and entry-point files hold a disproportionate amount of state, DOM coordination, API access, and presentation behavior.

The recommended direction is incremental modularization by business capability. A repository-wide rewrite would discard useful tests and introduce unnecessary regression risk.

## What is working well

- `app/Domain` has no dependency on Laravel HTTP classes, Eloquent models, or infrastructure implementations.
- Repository contracts for core transactional domains live inward of Eloquent implementations.
- Controllers are generally thin; read and mutation controllers are already separated in several admin areas.
- Tenant isolation and security boundaries have explicit feature tests.
- The test suite covers the main business flows, including ordering, points, stamps, coupons, reservations, queues, settings, and external-integration boundaries.
- TypeScript strict checking is available through `npm run typecheck`.

## Priority findings

### P1: Settings has a wide interface and implementation

`ShopSettingRepository` and `EloquentShopSettingRepository` each expose more than 100 methods, and `ShopSettingStore` exceeds 1,200 lines. This violates interface segregation and makes unrelated settings changes share one dependency and one regression surface.

Split contracts by consumer capability, for example:

- `BrandingSettings`
- `FeatureFlagSettings`
- `LineConnectionSettings`
- `MemberCardSettings`
- `OrderSettings`
- `QueueSettings`
- `ReservationSettings`
- `WalletSettings`

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

### P2: Two repository conventions coexist

The codebase contains both `app/Domain/*/Repositories` with `app/Infrastructure/Persistence/Eloquent/*` and `app/Repositories/Contracts` with `app/Repositories/Eloquent`. Both work, but contributors must guess where a new abstraction belongs.

Adopt the domain/infrastructure convention for business aggregates. Reserve application-local contracts for non-domain read models, and document that rule in `AGENTS.md` or a short architecture decision record.

### P2: Exceptions are hidden at presentation composition boundaries

View composition intentionally falls back on defaults. The extracted composers now report failures before applying those defaults; catch expected exceptions more narrowly as failure modes become explicit.

### P2: Static quality gates are incomplete

PHP formatting and TypeScript type checking exist, but there is no PHP static analyzer, dependency-boundary checker, ESLint rule set, or frontend unit-test runner. Add gates gradually:

- PHPStan/Larastan at a conservative initial level.
- ESLint with React hooks and import-boundary rules.
- Vitest for pure functions, hooks, and API adapters.
- Automated architecture tests preventing `Domain` from importing Laravel, `Models`, `Http`, or `Infrastructure`.

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

The Eloquent adapter implements these contracts during the incremental migration. The app-config API and shared home-image reader have also moved to focused read contracts. Architecture tests prevent migrated consumers from returning to the broad dependency.

The first frontend extractions move Wallet/external-URL policy, member API payload normalization, and profile-form payload construction out of `MemberManager.ts`. The manager retains compatibility delegates while URL validation, HTTPS normalization, LIFF return URL construction, Google Wallet URL handling, error-message selection, supported member payload shapes, wallet-summary equality, split-name handling, choice fields, and empty profile values are independently covered by Node unit tests.

Rank-history rendering is also isolated as a pure formatter. API-provided labels and dates are escaped at that boundary and covered by focused unit tests. View composers now catch runtime exceptions rather than all PHP throwables, so programming errors such as type violations are no longer silently replaced with fallback presentation data.

## Recommended sequence

1. Protect dependency direction with architecture tests and narrow the remaining graceful-fallback exception handling.
2. Split the settings interface by capability without changing storage.
3. Migrate `MemberManager.ts` one screen/use case at a time.
4. Reduce `frontend/app.ts` to composition and bootstrap.
5. Add outbound ports around LINE, Smaregi, push, wallet, and mail as those areas are touched.
6. Consolidate repository conventions after consumers have migrated.

Each step should leave the complete core PHP suite, TypeScript check, and affected Playwright flows green. Avoid a big-bang clean-architecture rewrite.
