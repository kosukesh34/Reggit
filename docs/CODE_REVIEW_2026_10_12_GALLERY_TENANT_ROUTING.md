# Gallery category tenant boundary and route arguments

## Confirmed findings

- P1: Gallery item creation accepted a category ID belonging to another project. The raw `exists:gallery_categories,id` validator bypassed the model project scope and persisted the cross-project association. This is a data integrity and tenant boundary defect; disclosure of another project category content was not demonstrated.
- P1: Gallery item update/delete and category update/delete did not accept the leading `{project_key}` route argument. Real HTTP requests passed the project key as the integer target ID and failed with TypeError. The update category-boundary case was initially masked by this routing failure.

## Change

`Rule::exists(...)->where('project_id', currentAdminProjectId)` validates category ownership before image storage. Nullable category remains supported. The four ID-based actions now accept `string $projectKey` before `int $id`, matching existing admin controller conventions. UseCase/model tenant scopes remain in place. These UseCases have no other production callers, so no additional service abstraction is required.

## Verification

- Before: nine real admin HTTP cases, seven failures and two passes, 20 assertions, 5.60s. The foreign category was actually persisted on creation. Normal updates and the three other mutations reproduced route TypeErrors.
- After: nine new cases plus the existing upload and public-gallery tests, 13 passes, 59 assertions, 7.46s. Cases cover foreign category rejection before image storage, same-project/null category creation and update, and item deletion/category update/category deletion.
- Pint for the three changed PHP files and full PHPStan are required; results and the exact committed HEAD full-local-CI gate are recorded in the PR.
- Independent static review found no blocker. No browser E2E or external service was needed for these server-side validation/dispatch changes.

The tests do not establish filesystem/database atomicity, production load behavior, or a comprehensive security audit.
