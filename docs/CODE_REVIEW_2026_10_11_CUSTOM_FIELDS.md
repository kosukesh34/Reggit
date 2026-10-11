# Critical review: partial custom-field PATCH

## P2: omitted custom fields are serialized as clearing requests

SmaregiCustomerUpdatePayloadBuilder.buildSmaregiCustomFields enumerated every configured field and emitted value=null when it was absent from both the supplied profile and profile_extra. A partial nickname update therefore also sent company=null. The local writer correctly preserves untouched values, but its external PATCH did not.

The smallest correction omits absent fields, while retaining explicit null at either location. It preserves top-level precedence, array formatting, registration placeholders and persisted admin profile_extra values. Both builder call paths use the same corrected implementation; no additional interface or dependency is introduced.

Verification: the original code fails the partial-update regression; all 10 builder tests / 21 assertions pass after correction. Independent read-only review traced member registration and admin editing and found no blocking regressions. Pint, mutation, full local CI and exact HEAD/logs are recorded in the PR. No external API calls, migration, production change or browser E2E.

Remaining profile work: durably save/retry desired profile state; protect against inbound GET fetched before outbound ACK; preserve current desired values when a stale worker finishes. Do not merge historical ACKed PATCH bodies indiscriminately, because legitimate inbound edits can make retained values obsolete. Current omission of blank core fields is a separate external-contract question and is not changed here.

Applied: i-have-adhd, Ponytail full, code-review-and-quality, Laravel architecture guidance and architecture reasoning from codebase-design. This is a focused correction, not certification of the entire application.
