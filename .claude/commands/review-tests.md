# /review-tests

Review Boardly test coverage for a feature, design, diff, or implementation.

Input:
$ARGUMENTS

Use when:
- a feature has domain rules, workflow transitions, permissions, async handlers, search projections, cache behavior, audit, or reporting logic;
- a PR needs a test coverage check before merge;
- regression risk must be identified.

Recommended agents:
- testing-security-reviewer;
- ddd-modeling when domain tests are involved;
- workflow-permissions when workflow/permission tests are involved;
- async-messaging when async tests are involved;
- search-read-models when projection/search tests are involved.

Recommended skills:
- testing-strategy;
- domain-modeling;
- workflow-design;
- permission-modeling;
- async-flow;
- search-indexing;
- cache-performance.

Rules:
- Do not rely only on controller/API tests.
- Domain rules should have fast unit tests.
- Application handlers should test orchestration and transaction behavior.
- Infrastructure adapters should have integration tests when behavior matters.
- Async handlers must be tested for duplicate messages and retry behavior.
- Permissions and visibility must have explicit tests.

Output format:
1. Summary
2. Critical behavior to protect
3. Current coverage assessment
4. Missing domain tests
5. Missing application/integration tests
6. Missing permission/workflow tests
7. Missing async/search/cache tests
8. Regression risks
9. Minimum quality gate before merge
