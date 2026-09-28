# ADR-001: Repository placement conventions

Status: Accepted
Date: 2026-09-27

## Decision

Business aggregates use contracts under `app/Domain/<Context>/Repositories` and implementations under `app/Infrastructure/Persistence/Eloquent/<Context>`.

The older `app/Repositories/Contracts` and `app/Repositories/Eloquent` locations are reserved for application-level identity, tenant-resolution, and read-model queries that do not represent a domain aggregate. New business persistence abstractions must not be added there.

Simple, single-consumer Eloquent reads may remain in an application service or use case. A repository is introduced when a query is shared, represents aggregate persistence, or must be substituted at an external boundary.

## Consequences

- New contributors have one default location for domain persistence.
- Existing application-level repositories can migrate incrementally; no big-bang move is required.
- Domain contracts remain independent from Laravel and Eloquent.
- Architecture tests continue to protect the inward dependency direction.
